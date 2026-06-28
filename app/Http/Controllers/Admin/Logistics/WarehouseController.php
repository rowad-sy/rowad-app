<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Logistics\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\Warehouse,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Logistics\Warehouse,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\Warehouse,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Logistics\Warehouse,delete')->only(['destroy']);
    }

    public function index()
    {
        $warehouses = Warehouse::with('center')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.logistics.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();

        return view('admin.logistics.warehouses.form', compact('centers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => 'required|exists:centers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        Warehouse::create($validated);

        return redirect()->route('admin.logistics.warehouses.index')
            ->with('success', 'تم إضافة المستودع بنجاح');
    }

    public function edit(Warehouse $warehouse)
    {
        $centers = Center::orderBy('name')->get();

        return view('admin.logistics.warehouses.form', compact('warehouse', 'centers'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => 'required|exists:centers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $warehouse->update($validated);

        return redirect()->route('admin.logistics.warehouses.index')
            ->with('success', 'تم تحديث المستودع بنجاح');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->items()->delete();
        $warehouse->delete();

        return redirect()->route('admin.logistics.warehouses.index')
            ->with('success', 'تم حذف المستودع بنجاح');
    }
}
