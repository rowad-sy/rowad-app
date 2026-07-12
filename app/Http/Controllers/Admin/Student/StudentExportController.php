<?php

namespace App\Http\Controllers\Admin\Student;

use App\Exports\Students\StudentExport;
use App\Exports\Students\StudentFullExport;
use App\Imports\Students\StudentImport;
use App\Imports\Students\StudentFullImport;
use App\Http\Controllers\Controller;
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

        Excel::import(new StudentImport, $request->file('file'));

        return redirect()->back()->with('success', 'تم استيراد بيانات الطلاب بنجاح');
    }

    public function importFull(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new StudentFullImport, $request->file('file'));

        return redirect()->back()->with('success', 'تم استيراد جميع بيانات الطلاب بنجاح');
    }
}
