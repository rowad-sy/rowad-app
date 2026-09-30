<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Exports\Logistics\AssetExport;
use App\Exports\Logistics\PurchaseRequestExport;
use App\Exports\Logistics\WarehouseExport;
use App\Http\Controllers\Controller;
use App\Imports\Logistics\AssetImport;
use App\Imports\Logistics\PurchaseRequestImport;
use App\Imports\Logistics\WarehouseImport;
use App\Imports\Logistics\AssetImportRejected;
use App\Models\Admin\Logistics\Asset;
use App\Services\AuditLogger;
use App\Support\RecordAccess;
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
        abort_unless(RecordAccess::hasAny(auth()->user(), Asset::class, 'view'), 403);

        return Excel::download(AssetExport::all(), 'assets.xlsx');
    }

    public function importAssets(Request $request)
    {
        abort_unless(RecordAccess::hasAny(auth()->user(), Asset::class, 'create'), 403);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        try {
            Excel::import(new AssetImport, $request->file('file'));
        } catch (AssetImportRejected $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            // الكتابة ذرية (تراجع كامل) ولا يُسجَّل حدث نجاح
            report($e);

            return redirect()->back()->with('error', 'تعذّر الاستيراد: بيانات الملف لا تكفي لإنشاء الأصول (حقول مطلوبة في قاعدة البيانات مثل المركز والكود والنوع غير موجودة في تنسيق الملف الحالي). لم يُحفظ أي أصل.');
        }

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
