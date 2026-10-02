<?php

namespace App\Http\Controllers\Admin\Student;

use App\Exports\Students\StudentExport;
use App\Exports\Students\StudentFullExport;
use App\Helpers\PermissionHelper;
use App\Imports\Students\StudentImport;
use App\Imports\Students\StudentFullImport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Student\Student;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Student,view')->only(['export', 'exportFull']);
        $this->middleware('permission:App\Models\Admin\Student\Student,create')->only(['import', 'importFull']);
    }

    /*
     * المعرّفات المسموح تصديرها حسب نطاق الصلاحية — null تعني "كل السجلات".
     * تُستخدم inProjects() لاحترام جدول project_student (الوسيط المعتمد فعلياً).
     */
    private function allowedStudentIds(): ?array
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Student');

        if ($scope['sees_all']) {
            return null;
        }

        return Student::query()
            ->when(!empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
            ->when(!empty($scope['project_ids']), fn($q) => $q->inProjects($scope['project_ids']))
            ->when(!empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
            ->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    private function resolveIds(Request $request): ?array
    {
        $ids = $request->input('ids') ? array_map('intval', (array) $request->input('ids')) : null;
        $allowed = $this->allowedStudentIds();

        if ($allowed === null) {
            return $ids;
        }

        return $ids ? array_values(array_intersect($ids, $allowed)) : $allowed;
    }

    public function export(Request $request)
    {
        $ids = $this->resolveIds($request);
        $export = $ids === null ? StudentExport::all() : StudentExport::fromIds($ids ?: [-1]);
        return Excel::download($export, 'students.xlsx');
    }

    public function exportFull(Request $request)
    {
        $ids = $this->resolveIds($request);
        $export = new StudentFullExport($ids === null ? null : ($ids ?: [-1]));
        return Excel::download($export, 'students_full.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        $beforeCount = \App\Models\Admin\Student\Student::count();

        try {
            Excel::import(new StudentImport, $request->file('file'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }

        $afterCount = \App\Models\Admin\Student\Student::count();
        $imported = $afterCount - $beforeCount;

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد بيانات طلاب من ملف: {$fileName} — تم استيراد {$imported} طالب",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'students_basic', 'imported_count' => $imported],
        );

        if ($imported > 0) {
            return redirect()->back()->with('success', "تم استيراد {$imported} طالب بنجاح من ملف: {$fileName}");
        }

        return redirect()->back()->with('warning', 'تم قراءة الملف لكن لم يتم استيراد أي طالب. تأكد من أن الملف يحتوي على بيانات صحيحة.');
    }

    public function importFull(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        $beforeCount = \App\Models\Admin\Student\Student::count();

        try {
            $errors = \App\Imports\Students\ImportPreflight::check($request->file('file')->getRealPath());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'تعذّر قراءة الملف للتحقق: ' . $e->getMessage());
        }

        if ($errors) {
            return redirect()->back()
                ->with('preflight_errors', $errors)
                ->with('error', 'الملف يحتاج تجهيزاً قبل الاستيراد — عالج الرسائل التفصيلية أدناه ثم أعد المحاولة.');
        }

        $import = new StudentFullImport;

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }

        $afterCount = \App\Models\Admin\Student\Student::count();
        $imported = $afterCount - $beforeCount;

        $parts = [];
        if ($import->signersImported) {
            $parts[] = "موقعون: {$import->signersImported}";
        }
        if ($import->setsImported) {
            $parts[] = "مجموعات توقيع: {$import->setsImported}";
        }
        if ($import->certificatesCreated || $import->certificatesUpdated) {
            $parts[] = "شهادات: جديد {$import->certificatesCreated}، محدّث {$import->certificatesUpdated}";
        }
        $summary = $parts ? ' — ' . implode(' | ', $parts) : '';

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد جميع بيانات الطلاب من ملف: {$fileName} — تم استيراد {$imported} طالب{$summary}",
            oldValues: null,
            newValues: [
                'file' => $fileName,
                'type' => 'students_full',
                'imported_count' => $imported,
                'signers_imported' => $import->signersImported,
                'sets_imported' => $import->setsImported,
                'certificates_created' => $import->certificatesCreated,
                'certificates_updated' => $import->certificatesUpdated,
            ],
        );

        if ($imported > 0 || $summary) {
            return redirect()->back()->with('success', "تم استيراد {$imported} طالب بنجاح{$summary} من ملف: {$fileName}");
        }

        return redirect()->back()->with('warning', 'تم قراءة الملف لكن لم يتم استيراد أي طالب. تأكد من أن الملف يحتوي على بيانات صحيحة.');
    }
}
