<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EmployeeExport;
use App\Exports\FullExport\EmployeeFullExport;
use App\Helpers\PermissionHelper;
use App\Imports\EmployeeImport;
use App\Imports\FullImport\EmployeeFullImport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\Employee,view')->only(['employees', 'employeesFullExport']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,create')->only(['importEmployees', 'importEmployeesFull']);
    }

    /*
     * المعرّفات المسموح تصديرها حسب نطاق الصلاحية — null تعني "كل السجلات".
     */
    private function allowedEmployeeIds(): ?array
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Hr\Employee');

        if ($scope['sees_all']) {
            return null;
        }

        return Employee::query()
            ->when(!empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
            ->when(!empty($scope['project_ids']), fn($q) => $q->whereIn('project_id', $scope['project_ids']))
            ->when(!empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
            ->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    public function employees(Request $request)
    {
        $ids = $request->input('ids') ? array_map('intval', (array) $request->input('ids')) : null;
        $allowed = $this->allowedEmployeeIds();

        if ($allowed !== null) {
            $ids = $ids ? array_values(array_intersect($ids, $allowed)) : $allowed;
            return Excel::download(EmployeeExport::fromIds($ids), 'employees.xlsx');
        }

        $export = $ids ? EmployeeExport::fromIds($ids) : EmployeeExport::all();
        return Excel::download($export, 'employees.xlsx');
    }

    public function employeesFullExport(Request $request)
    {
        $ids = $request->input('ids') ? array_map('intval', (array) $request->input('ids')) : null;
        $allowed = $this->allowedEmployeeIds();

        if ($allowed !== null) {
            $ids = $ids ? array_values(array_intersect($ids, $allowed)) : $allowed;

            return Excel::download(new EmployeeFullExport($ids ?: [-1]), 'employees_full.xlsx');
        }

        return Excel::download(new EmployeeFullExport($ids ?: null), 'employees_full.xlsx');
    }

    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new EmployeeImport, $request->file('file'));

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد بيانات موظفين من ملف: {$fileName}",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'employees_basic'],
        );

        return redirect()->back()->with('success', 'تم استيراد البيانات بنجاح');
    }

    public function importEmployeesFull(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new EmployeeFullImport, $request->file('file'));

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد جميع بيانات الموظفين من ملف: {$fileName}",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'employees_full'],
        );

        return redirect()->back()->with('success', 'تم استيراد جميع البيانات بنجاح');
    }
}
