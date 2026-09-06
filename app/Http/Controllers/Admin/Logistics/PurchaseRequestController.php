<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\ApprovalRule;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestApproval;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,edit')->only(['priceForm', 'price', 'managerDecide', 'pm2Decide', 'financeDecide', 'execute']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['user', 'center', 'project', 'items'])->withCount('items');

        if ($request->filled('status') && $request->status !== 'all') {
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

        $statuses = array_keys(PurchaseRequest::STATUSES);
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

        $employee = Employee::where('user_id', auth()->id())->first();
        $officerCenterId = $employee?->center_id;
        $officerProjectId = $employee?->project_id;
        $officerCohortId = $employee?->cohort_id;

        $candidates = $this->cycleCandidates();
        $defaultLogisticsId = $this->defaultLogisticsId($officerCenterId);
        $defaultDirectManagerId = $this->defaultDirectManagerId($officerProjectId);

        return view('admin.logistics.purchase-requests.form', compact(
            'centers', 'projects',
            'candidates', 'defaultLogisticsId', 'defaultDirectManagerId',
            'officerCenterId', 'officerProjectId', 'officerCohortId'
        ));
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
            'refer_to_logistics_id' => 'nullable|exists:users,id',
            'refer_to_direct_manager_id' => 'nullable|exists:users,id',
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
        $purchaseRequest->logWorkflow('create', $purchaseRequest->refer_to_logistics_id, 'تم إنشاء طلب الشراء');

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم إضافة طلب الشراء بنجاح');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'user', 'center', 'project', 'approvals.user', 'items',
            'logisticsStaff', 'directManager', 'pm2User', 'financeUser', 'lockedByUser',
            'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $candidates = $this->cycleCandidates();

        $tentativePm2Id = $purchaseRequest->refer_to_pm2_id
            ?? Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
                ->where('can_view', 1)
                ->whereNull('center_id')
                ->whereNull('project_id')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->value('user_id')
            ?? $candidates->first()?->id;

        $tentativeFinanceId = $purchaseRequest->refer_to_finance_id
            ?? User::where('type', 'super-admin')->value('id')
            ?? $candidates->first()?->id;

        return view('admin.logistics.purchase-requests.show', compact(
            'purchaseRequest', 'candidates', 'tentativePm2Id', 'tentativeFinanceId'
        ));
    }

    /*
     * (قسم 14.3) واجهة التسعير المقيد — اللوجستي فقط
     * يسمح بتحديث unit_price/total_price للبنود ورقم الميزانية؛
     * حقول البنود الأخرى (وصف/كمية/وحدة) غير مرسلة أصلاً.
     */
    public function priceForm(PurchaseRequest $purchaseRequest)
    {
        $this->authorizePricing($purchaseRequest);
        $purchaseRequest->load('items');

        return view('admin.logistics.purchase-requests.price', compact('purchaseRequest'));
    }

    public function price(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizePricing($purchaseRequest);

        $validated = $request->validate([
            'budget_number' => 'nullable|string|max:60',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $itemIds = $purchaseRequest->items()->pluck('id')->all();

        foreach ($validated['items'] as $item) {
            if (! in_array((int) $item['id'], $itemIds, true)) {
                return back()->with('error', 'بند غير تابع لهذا الطلب');
            }
        }

        $grandTotal = 0;
        foreach ($validated['items'] as $item) {
            $row = $purchaseRequest->items()->findOrFail($item['id']);
            $total = $row->quantity * $item['unit_price'];
            $grandTotal += $total;
            $row->update([
                'unit_price' => $item['unit_price'],
                'total_price' => $total,
            ]);
        }

        $purchaseRequest->update([
            'budget_number' => $validated['budget_number'] ?? null,
            'expected_total_price' => $grandTotal,
            'status' => 'priced',
        ]);

        $purchaseRequest->logWorkflow(
            'priced',
            $purchaseRequest->refer_to_direct_manager_id,
            trim(($validated['budget_number'] ?? null) ? 'رقم الميزانية: ' . $validated['budget_number'] : ''),
            'priced'
        );

        return redirect()->route('admin.logistics.purchase-requests.show', $purchaseRequest)
            ->with('success', 'تم التسعير وإحالة الطلب إلى المدير المباشر للتوقيع');
    }

    /*
     * (قسم 14.4/14.5) قرار المدير المباشر — عند الموافقة يُقفل الطلب نهائياً
     * (locked_at/locked_by) ويُحال إلى مدير المشاريع.
     */
    public function managerDecide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeRecipient($purchaseRequest, 'priced', 'refer_to_direct_manager_id');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
            'refer_to_pm2_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من المدير المباشر');
        }

        $purchaseRequest->update([
            'status' => 'pm_approved',
            'locked_at' => now(),
            'locked_by' => auth()->id(),
            'refer_to_pm2_id' => $validated['refer_to_pm2_id'],
        ]);

        $purchaseRequest->logWorkflow(
            'pm_approved',
            $validated['refer_to_pm2_id'],
            $validated['note'] ?? null,
            'pm_approved'
        );

        return redirect()->back()->with('success', 'تمت الموافقة وقفل الطلب نهائياً، وإحالته إلى مدير المشاريع');
    }

    public function pm2Decide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeRecipient($purchaseRequest, 'pm_approved', 'refer_to_pm2_id');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
            'refer_to_finance_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من مدير المشاريع');
        }

        $purchaseRequest->update([
            'status' => 'pm2_approved',
            'refer_to_finance_id' => $validated['refer_to_finance_id'],
        ]);

        $purchaseRequest->logWorkflow(
            'pm2_approved',
            $validated['refer_to_finance_id'],
            $validated['note'] ?? null,
            'pm2_approved'
        );

        return redirect()->back()->with('success', 'تمت الموافقة وإحالة الطلب إلى مدير المالية');
    }

    public function financeDecide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeRecipient($purchaseRequest, 'pm2_approved', 'refer_to_finance_id');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من مدير المالية');
        }

        $purchaseRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $purchaseRequest->logWorkflow('approved', null, $validated['note'] ?? null, 'approved');

        return redirect()->back()->with('success', 'تم اعتماد طلب الشراء نهائياً');
    }

    /*
     * (قسم 14.2) تنفيذ اللوجستي بعد الاعتماد — العرض يكون رؤية فقط،
     * والتغيير الوحيد المتاح هو تنفيذ الطلب.
     */
    public function execute(PurchaseRequest $purchaseRequest, Request $request)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && $user->id !== $purchaseRequest->refer_to_logistics_id) {
            abort(403, 'أنت لست لوجستي هذا الطلب');
        }

        if ($purchaseRequest->status !== 'approved') {
            return back()->with('error', 'لا يمكن تنفيذ الطلب قبل اعتماده');
        }

        $purchaseRequest->update(['status' => 'executed']);
        $purchaseRequest->logWorkflow('executed', null, $request->note, 'executed');

        return redirect()->back()->with('success', 'تم تنفيذ طلب الشراء');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->isLocked()) {
            abort(403, 'الطلب مقفول بعد موافقة مدير المشروع — لا يمكن حذفه');
        }

        $purchaseRequest->delete();

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم حذف طلب الشراء بنجاح');
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

    /*
     * التسعير متاح للوجستي المحدَّد في الطلب فقط (أو السوبر أدمن).
     */
    private function authorizePricing(PurchaseRequest $purchaseRequest): void
    {
        $user = auth()->user();

        if ($user->type !== 'super-admin' && $user->id !== $purchaseRequest->refer_to_logistics_id) {
            abort(403, 'أنت لست اللوجستي المسؤول عن تسعير هذا الطلب');
        }

        if ($purchaseRequest->status !== 'pending') {
            abort(403, 'هذا الطلب ليس بانتظار التسعير');
        }
    }

    /*
     * فحص "صلاحية الطرف المستقبِل" (الدراسة 14.5): صاحب الخطوة الحالية فقط.
     */
    private function authorizeRecipient(PurchaseRequest $purchaseRequest, string $expectedStatus, string $recipientColumn): void
    {
        $user = auth()->user();

        if ($user->type !== 'super-admin' && $user->id !== (int) $purchaseRequest->{$recipientColumn}) {
            abort(403, 'هذه الخطوة ليست موجهة إليك');
        }

        if ($purchaseRequest->status !== $expectedStatus) {
            abort(403, 'حالة الطلب لا تسمح بهذه الخطوة');
        }
    }

    private function cycleCandidates(): \Illuminate\Support\Collection
    {
        return User::whereIn('type', ['employee', 'super-admin'])
            ->with('jobTitle')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'job_title_id']);
    }

    /*
     * افتراضي "لوجستي المركز" (قسم 14.7): من يملك صلاحية تسعير/اعتماد
     * على طلبات الشراء بنطاق مركز المسؤول — قابل للتغيير يدوياً في الفورم.
     */
    private function defaultLogisticsId(?int $centerId): ?int
    {
        return Permission::whereJsonContains('model_names', 'App\Models\Admin\Logistics\PurchaseRequest')
            ->where('can_edit', 1)
            ->where('can_view', 1)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    /*
     * افتراضي "مدير المشروع" (قسم 14.7): مَن يملك صلاحية لوحة مدير المشروع
     * بنطاق مشروع المسؤول (أو بلا نطاق) — قابل للتغيير يدوياً.
     */
    private function defaultDirectManagerId(?int $projectId): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
            ->where('can_view', 1)
            ->when($projectId, fn ($q) => $q->where(fn ($s) => $s->whereNull('project_id')->orWhere('project_id', $projectId)))
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }
}
