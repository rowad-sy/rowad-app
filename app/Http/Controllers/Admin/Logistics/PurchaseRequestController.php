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
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,view')->only(['index', 'show', 'help']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,edit')->only(['priceForm', 'price', 'managerDecide', 'pm2Decide', 'financeDecide', 'executiveDecide', 'execute', 'refer']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['user', 'center', 'project', 'items'])->withCount('items');

        $user = auth()->user();
        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere(fn ($s) => $s->where('refer_to_logistics_id', $user->id)
                        ->orWhere('refer_to_direct_manager_id', $user->id)
                        ->orWhere('refer_to_pm2_id', $user->id)
                        ->orWhere('refer_to_finance_id', $user->id)
                        ->orWhere('refer_to_executive_id', $user->id))
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
        }

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

    public function help()
    {
        return view('admin.logistics.purchase-requests.help');
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
            'items.*.budget_line' => 'nullable|numeric|min:0',
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
                'budget_line' => $item['budget_line'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $this->createApprovals($purchaseRequest);

        $this->pushReferral($purchaseRequest, 'logistics', $purchaseRequest->refer_to_logistics_id, null);
        $this->pushReferral($purchaseRequest, 'direct_manager', $purchaseRequest->refer_to_direct_manager_id, null);

        $purchaseRequest->logWorkflow('create', $purchaseRequest->refer_to_logistics_id, 'تم إنشاء طلب الشراء');

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم إضافة طلب الشراء بنجاح');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && !$purchaseRequest->isVisibleToUserId($user->id)) {
            abort(403, 'هذا الطلب ليس موجهًا إليك');
        }

        $purchaseRequest->load([
            'user', 'center', 'project', 'approvals.user', 'items',
            'logisticsStaff', 'directManager', 'pm2User', 'financeUser', 'executiveUser', 'lockedByUser',
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
            ?? Permission::whereJsonContains('model_names', 'App\Models\Admin\Logistics\PurchaseRequest')
                ->where('can_edit', 1)
                ->whereNotNull('user_id')
                ->whereNull('center_id')
                ->whereNull('project_id')
                ->orderBy('id')
                ->value('user_id')
            ?? $candidates->first()?->id;

        $tentativeExecutiveId = $purchaseRequest->refer_to_executive_id
            ?? User::where('type', 'super-admin')->value('id')
            ?? $candidates->first()?->id;

        return view('admin.logistics.purchase-requests.show', compact(
            'purchaseRequest', 'candidates', 'tentativePm2Id', 'tentativeFinanceId', 'tentativeExecutiveId'
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
            'items.*.budget_line' => 'nullable|numeric|min:0',
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
                'budget_line' => $item['budget_line'] ?? null,
            ]);
        }

        $purchaseRequest->update([
            'budget_number' => $validated['budget_number'] ?? null,
            'expected_total_price' => $grandTotal,
            'status' => 'priced',
        ]);

        $purchaseRequest->completeReferral('logistics');

        $noteParts = [];
        if (! empty($validated['budget_number'] ?? null)) {
            $noteParts[] = 'رقم الميزانية: ' . $validated['budget_number'];
        }
        $budgetLines = collect($validated['items'])->pluck('budget_line')->filter();
        if ($budgetLines->isNotEmpty()) {
            $noteParts[] = 'خط الميزانية: ' . $budgetLines->implode(', ');
        }

        $purchaseRequest->logWorkflow(
            'priced',
            $purchaseRequest->refer_to_direct_manager_id,
            trim(implode(' | ', $noteParts)),
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
        $this->authorizeStep($purchaseRequest, 'direct_manager', 'priced');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
            'refer_to_pm2_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->completeReferral('direct_manager');
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من المدير المباشر');
        }

        $purchaseRequest->update([
            'status' => 'pm_approved',
            'refer_to_pm2_id' => $validated['refer_to_pm2_id'],
        ]);

        $purchaseRequest->completeReferral('direct_manager');
        $this->pushReferral($purchaseRequest, 'pm2', $validated['refer_to_pm2_id'], $validated['note'] ?? null);

        $purchaseRequest->logWorkflow(
            'pm_approved',
            $validated['refer_to_pm2_id'],
            $validated['note'] ?? null,
            'pm_approved'
        );

        return redirect()->back()->with('success', 'تمت الموافقة وإحالة الطلب إلى مدير المشاريع');
    }

    public function pm2Decide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeStep($purchaseRequest, 'pm2', 'pm_approved');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
            'refer_to_finance_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->completeReferral('pm2');
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من مدير المشاريع');
        }

        $purchaseRequest->update([
            'status' => 'pm2_approved',
            'refer_to_finance_id' => $validated['refer_to_finance_id'],
        ]);

        $purchaseRequest->completeReferral('pm2');
        $this->pushReferral($purchaseRequest, 'finance', $validated['refer_to_finance_id'], $validated['note'] ?? null);

        $purchaseRequest->logWorkflow(
            'pm2_approved',
            $validated['refer_to_finance_id'],
            $validated['note'] ?? null,
            'pm2_approved'
        );

        return redirect()->back()->with('success', 'تمت الموافقة وإحالة الطلب إلى المسؤول المالي');
    }

    public function financeDecide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeStep($purchaseRequest, 'finance', 'pm2_approved');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
            'refer_to_executive_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->completeReferral('finance');
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من المسؤول المالي');
        }

        $purchaseRequest->update([
            'status' => 'finance_approved',
            'refer_to_executive_id' => $validated['refer_to_executive_id'],
            'finance_at' => now(),
        ]);

        $purchaseRequest->completeReferral('finance');
        $this->pushReferral($purchaseRequest, 'executive', $validated['refer_to_executive_id'], $validated['note'] ?? null);

        $purchaseRequest->logWorkflow(
            'finance_approved',
            $validated['refer_to_executive_id'],
            $validated['note'] ?? null,
            'finance_approved'
        );

        return redirect()->back()->with('success', 'تمت موافقة المالية وإحالة الطلب إلى المدير التنفيذي');
    }

    /*
     * الاعتماد النهائي — المدير التنفيذي: هنا يُقفل الطلب نهائياً
     * (locked_at/locked_by) ويصبح معتمداً.
     */
    public function executiveDecide(PurchaseRequest $purchaseRequest, Request $request)
    {
        $this->authorizeStep($purchaseRequest, 'executive', 'finance_approved');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validated['decision'] === 'reject') {
            $purchaseRequest->update(['status' => 'rejected']);
            $purchaseRequest->completeReferral('executive');
            $purchaseRequest->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return redirect()->back()->with('error', 'تم رفض طلب الشراء من المدير التنفيذي');
        }

        $purchaseRequest->update([
            'status' => 'approved',
            'locked_at' => now(),
            'locked_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $purchaseRequest->completeReferral('executive');
        $purchaseRequest->logWorkflow('approved', null, $validated['note'] ?? null, 'approved');

        return redirect()->back()->with('success', 'تم اعتماد طلب الشراء نهائياً وقفله');
    }

    /*
     * إعادة الإحالة: يقوم بها المستلَم الحالي للخطوة فقط، ويمكن أن يعيد
     * إحالة الخطوة لشخص آخر (تبديل منفذ الخطوة دون تغيير الحالة).
     */
    public function refer(PurchaseRequest $purchaseRequest, Request $request)
    {
        $validated = $request->validate([
            'step' => 'required|in:logistics,direct_manager,pm2,finance,executive',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->authorizeHolder($purchaseRequest, $validated['step']);

        $this->pushReferral($purchaseRequest, $validated['step'], $validated['to_user_id'], $validated['note'] ?? null);
        $purchaseRequest->logWorkflow('referred', $validated['to_user_id'], $validated['note'] ?? null);

        return redirect()->back()->with('success', 'أُعيدت إحالة الطلب بنجاح');
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

    private const STEP_COLUMNS = [
        'logistics' => 'refer_to_logistics_id',
        'direct_manager' => 'refer_to_direct_manager_id',
        'pm2' => 'refer_to_pm2_id',
        'finance' => 'refer_to_finance_id',
        'executive' => 'refer_to_executive_id',
    ];

    /*
     * فحص "صاحب الخطوة الحالية" (الإحالات): المستلَم الحالي للخطوة فقط،
     * مع التحقق من حالة الطلب المطلوبة للخطوة.
     */
    private function authorizeStep(PurchaseRequest $purchaseRequest, string $step, string $expectedStatus): void
    {
        $this->authorizeHolder($purchaseRequest, $step);

        if ($purchaseRequest->status !== $expectedStatus) {
            abort(403, 'حالة الطلب لا تسمح بهذه الخطوة');
        }
    }

    /*
     * المستلَم الحالي للخطوة (عبر الإحالة النشطة أو العمود القديم) —
     * يستخدم للتصرف في الخطوة ولإعادة الإحالة.
     */
    private function authorizeHolder(PurchaseRequest $purchaseRequest, string $step): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return;
        }

        $column = self::STEP_COLUMNS[$step] ?? null;

        $isHolder = $purchaseRequest->isCurrentRecipient($user->id)
            || ($column !== null && (int) $purchaseRequest->{$column} === (int) $user->id);

        if (! $isHolder) {
            abort(403, 'هذه الخطوة ليست موجهة إليك');
        }
    }

    /*
     * تسجيل إحالة (عقد حالي) في جدول referrals وتحديث العمود القديم معاً
     * ليبقى كلاهما متوافقين (الإحالة هي المرجع في الرؤية والتحقق).
     */
    private function pushReferral(PurchaseRequest $purchaseRequest, string $step, ?int $toUserId, ?string $note): void
    {
        $column = self::STEP_COLUMNS[$step] ?? null;

        if ($column !== null) {
            $purchaseRequest->update([$column => $toUserId]);
        }

        if ($toUserId !== null) {
            $purchaseRequest->referTo($toUserId, $step, $note);
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
