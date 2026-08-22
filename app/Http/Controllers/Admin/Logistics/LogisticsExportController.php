<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Exports\Logistics\AssetExport;
use App\Exports\Logistics\PurchaseRequestExport;
use App\Exports\Logistics\WarehouseExport;
use App\Http\Controllers\Controller;
use App\Imports\Logistics\AssetImport;
use App\Imports\Logistics\PurchaseRequestImport;
use App\Imports\Logistics\WarehouseImport;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LogisticsExportController extends Controller
{
    public function exportPurchaseRequests()
    {
        return Excel::download(PurchaseRequestExport::all(), 'purchase_requests.xlsx');
    }

    public function importPurchaseRequests(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new PurchaseRequestImport, $request->file('file'));

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد طلبات الشراء من ملف: {$fileName}",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'purchase_requests'],
        );

        return redirect()->back()->with('success', 'تم استيراد طلبات الشراء بنجاح');
    }

    public function exportWarehouses()
    {
        return Excel::download(WarehouseExport::all(), 'warehouses.xlsx');
    }

    public function importWarehouses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new WarehouseImport, $request->file('file'));

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد المستودعات من ملف: {$fileName}",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'warehouses'],
        );

        return redirect()->back()->with('success', 'تم استيراد المستودعات بنجاح');
    }

    public function exportAssets()
    {
        return Excel::download(AssetExport::all(), 'assets.xlsx');
    }

    public function importAssets(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new AssetImport, $request->file('file'));

        AuditLogger::recordEvent(
            modelClass: \App\Models\User::class,
            modelId: auth()->id(),
            event: 'imported',
            description: "استيراد الأصول من ملف: {$fileName}",
            oldValues: null,
            newValues: ['file' => $fileName, 'type' => 'assets'],
        );

        return redirect()->back()->with('success', 'تم استيراد الأصول بنجاح');
    }
}
