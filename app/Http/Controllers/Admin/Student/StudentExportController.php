<?php

namespace App\Http\Controllers\Admin\Student;

use App\Exports\Students\StudentExport;
use App\Exports\Students\StudentFullExport;
use App\Imports\Students\StudentImport;
use App\Imports\Students\StudentFullImport;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentExportController extends Controller
{
    public function export(Request $request)
    {
        $ids = $request->input('ids');
        $export = $ids ? StudentExport::fromIds($ids) : StudentExport::all();
        return Excel::download($export, 'students.xlsx');
    }

    public function exportFull(Request $request)
    {
        $ids = $request->input('ids');
        $export = new StudentFullExport($ids ?: null);
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
            Excel::import(new StudentFullImport, $request->file('file'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }

        $afterCount = \App\Models\Admin\Student\Student::count();
        $imported = $afterCount - $beforeCount;

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد جميع بيانات الطلاب من ملف: {$fileName} — تم استيراد {$imported} طالب",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'students_full', 'imported_count' => $imported],
        );

        if ($imported > 0) {
            return redirect()->back()->with('success', "تم استيراد {$imported} طالب بنجاح من ملف: {$fileName}");
        }

        return redirect()->back()->with('warning', 'تم قراءة الملف لكن لم يتم استيراد أي طالب. تأكد من أن الملف يحتوي على بيانات صحيحة.');
    }
}
