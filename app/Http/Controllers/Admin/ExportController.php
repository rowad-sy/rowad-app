<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EmployeeExport;
use App\Exports\FullExport\EmployeeFullExport;
use App\Imports\EmployeeImport;
use App\Imports\FullImport\EmployeeFullImport;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function employees(Request $request)
    {
        $ids = $request->input('ids');
        $export = $ids ? EmployeeExport::fromIds($ids) : EmployeeExport::all();
        return Excel::download($export, 'employees.xlsx');
    }

    public function employeesFullExport(Request $request)
    {
        $ids = $request->input('ids');
        $export = new EmployeeFullExport($ids ?: null);
        return Excel::download($export, 'employees_full.xlsx');
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
