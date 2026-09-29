<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\DeletedItem;
use App\Models\Admin\Logistics\Warehouse;
use App\Models\Admin\Logistics\WarehouseItem;
use Illuminate\Http\Request;

class WarehouseItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\WarehouseItem,view')->only(['index', 'deleted']);
        $this->middleware('permission:App\Models\Admin\Logistics\WarehouseItem,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\WarehouseItem,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Logistics\WarehouseItem,delete')->only(['destroy']);
    }

    public function index(Warehouse $warehouse)
    {
        $items = WarehouseItem::where('warehouse_id', $warehouse->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.logistics.warehouse-items.index', compact('warehouse', 'items'));
    }

    public function create(Warehouse $warehouse)
    {
        return view('admin.logistics.warehouse-items.form', compact('warehouse'));
    }

    public function store(Warehouse $warehouse, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $validated['warehouse_id'] = $warehouse->id;

        WarehouseItem::create($validated);

        return redirect()->route('admin.logistics.warehouses.items.index', $warehouse->id)
            ->with('success', 'تم إضافة الصنف بنجاح');
    }

    public function edit(Warehouse $warehouse, WarehouseItem $item)
    {
        return view('admin.logistics.warehouse-items.form', compact('warehouse', 'item'));
    }

    public function update(Warehouse $warehouse, WarehouseItem $item, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $item->update($validated);

        return redirect()->route('admin.logistics.warehouses.items.index', $warehouse->id)
            ->with('success', 'تم تحديث الصنف بنجاح');
    }

    public function destroy(Warehouse $warehouse, WarehouseItem $item, Request $request)
    {
        $request->validate([
            'delete_reason' => 'required|string|max:1000',
        ]);

        DeletedItem::create([
            'warehouse_id' => $warehouse->id,
            'item_name' => $item->name,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'delete_reason' => $request->delete_reason,
        ]);

        $item->delete();

        return redirect()->route('admin.logistics.warehouses.items.index', $warehouse->id)
            ->with('success', 'تم حذف الصنف ونقله إلى المواد المحذوفة');
    }

    public function deleted(Warehouse $warehouse)
    {
        $deletedItems = DeletedItem::where('warehouse_id', $warehouse->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.logistics.warehouse-items.deleted', compact('warehouse', 'deletedItems'));
    }
}
