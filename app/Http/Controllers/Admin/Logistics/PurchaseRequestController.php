<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Logistics\ApprovalRule;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestApproval;
use App\Models\Admin\Project;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['user', 'center', 'project', 'items'])->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $purchaseRequests = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->only(['status', 'center_id', 'project_id']));

        $statuses = ['pending', 'approved', 'rejected', 'executed'];
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.logistics.purchase-requests.index', compact(
            'purchaseRequests', 'statuses', 'centers', 'projects'
        ));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.logistics.purchase-requests.form', compact('centers', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'center_id' => 'required|exists:centers,id',
            'project_id' => 'required|exists:projects,id',
            'notes' => 'nullable|string|max:1000',
            'signature_data_url' => 'nullable|string',
            'signature_image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:2000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $totalPrice += $item['quantity'] * $item['unit_price'];
        }
        $validated['expected_total_price'] = $totalPrice;

        if ($request->hasFile('signature_image')) {
            $validated['signature_path'] = $request->file('signature_image')->store('signatures', 'public');
        } elseif ($request->filled('signature_data_url')) {
            $validated['signature_path'] = $request->signature_data_url;
        }

        unset($validated['signature_data_url'], $validated['signature_image']);

        $lastRequest = PurchaseRequest::where('request_number', 'like', 'PR-' . date('Y') . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -5);
            $nextId = $lastNumber + 1;
        } else {
            $nextId = 1;
        }

        $validated['request_number'] = 'PR-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $purchaseRequest = PurchaseRequest::create($validated);

        foreach ($items as $item) {
            $purchaseRequest->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $this->createApprovals($purchaseRequest);

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم إضافة طلب الشراء بنجاح');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['user', 'center', 'project', 'approvals.user', 'items']);

        return view('admin.logistics.purchase-requests.show', compact('purchaseRequest'));
    }

    private function createApprovals(PurchaseRequest $purchaseRequest): void
    {
        $total = $purchaseRequest->expected_total_price;

        $rule = ApprovalRule::with('approvers')
            ->where('min_amount', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->where('max_amount', '>=', $total)->orWhereNull('max_amount');
            })
            ->first();

        if (!$rule || $rule->approvers->isEmpty()) {
            return;
        }

        foreach ($rule->approvers as $approver) {
            PurchaseRequestApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'user_id' => $approver->id,
                'status' => 'pending',
            ]);
        }
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم حذف طلب الشراء بنجاح');
    }
}
