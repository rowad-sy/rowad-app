<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Exports\Logistics\PurchaseRequestFormExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestItem;
use App\Models\Admin\Logistics\PurchaseRequestSignature;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,view')->only(['index', 'show', 'help', 'print', 'exportExcel']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,edit')->only(['edit', 'update', 'approve', 'reject', 'refer', 'executeItems']);
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['user', 'center', 'project', 'items'])->withCount('items');

        $user = auth()->user();
        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere(fn ($s) => $s->where('refer_to_approver1_id', $user->id)
                        ->orWhere('refer_to_approver2_id', $user->id)
                        ->orWhere('refer_to_approver3_id', $user->id)
                        ->orWhere('refer_to_logistics_id', $user->id))
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $type = $request->get('type', 'all');
        if (array_key_exists($type, PurchaseRequest::TYPES)) {
            $query->where('request_type', $type);
        } else {
            $type = 'all';
        }

        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $perPage = (int) ($request->get('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;

        $purchaseRequests = $query->orderByDesc('pr_date')->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->only(['status', 'center_id', 'project_id', 'per_page', 'type']));

        $statuses = array_keys(PurchaseRequest::STATUSES);
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $status = $request->get('status', 'all');
        $centerId = $request->get('center_id');
        $projectId = $request->get('project_id');

        return view('admin.logistics.purchase-requests.index', compact(
            'purchaseRequests', 'statuses', 'centers', 'projects',
            'status', 'centerId', 'projectId', 'perPage', 'type'
        ));
    }

    public function create(Request $request)
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();
        $departments = Department::where('is_active', 1)->orderBy('name_ar')->get();

        $employee = Employee::where('user_id', auth()->id())->first();
        $defaultApproverId = $this->defaultApproverId();
        $requestType = array_key_exists((string) $request->get('type'), PurchaseRequest::TYPES)
            ? (string) $request->get('type')
            : 'purchase';

        return view('admin.logistics.purchase-requests.form', compact(
            'centers', 'projects', 'users', 'departments',
            'defaultApproverId', 'employee', 'requestType'
        ));
    }

    public function help()
    {
        return view('admin.logistics.purchase-requests.help');
    }

    public function store(Request $request)
    {
        $request->validate([
            'signature_image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $validated = $this->validateRequest($request);

        $employee = Employee::where('user_id', auth()->id())->first();

        $approver1 = $validated['refer_to_approver1_id'] ?? $this->defaultApproverId();
        if ($approver1 !== null && (int) $approver1 === (int) auth()->id()) {
            return back()->withInput()->withErrors(['refer_to_approver1_id' => 'لا يمكن إحالة الطلب إلى نفسك — اختر موافقًا آخر.']);
        }

        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $totalPrice += $item['quantity'] * $item['unit_price'];
        }

        $purchaseRequest = PurchaseRequest::create([
            'request_number' => $validated['request_number'],
            'request_type' => ($validated['request_type'] ?? null) === 'maintenance' ? 'maintenance' : 'purchase',
            'user_id' => auth()->id(),
            'center_id' => ($validated['center_id'] ?? null) ?: $employee?->center_id,
            'project_id' => ($validated['project_id'] ?? null) ?: $employee?->project_id,
            'pr_date' => $validated['pr_date'],
            'required_date' => $validated['required_date'] ?? null,
            'management_unit' => $validated['management_unit'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'specifications' => $this->specsSummary($validated['items']),
            'quantity' => count($validated['items']),
            'unit' => 'بند',
            'expected_total_price' => $totalPrice,
            'refer_to_approver1_id' => $approver1,
            'status' => 'review',
        ]);

        $this->syncItems($purchaseRequest, $validated['items']);

        $this->storeRequestedSignature($request, $purchaseRequest);

        $purchaseRequest->logWorkflow('create', $approver1, 'تم إنشاء ' . $purchaseRequest->typeLabel() . ' وإحالته للموافقة', 'review');

        if ($approver1 !== null) {
            $purchaseRequest->referTo($approver1, 'approver1');
        }

        return redirect()->route('admin.logistics.purchase-requests.show', $purchaseRequest)
            ->with('success', 'تم إنشاء ' . $purchaseRequest->typeLabel() . ' وإحالته للموافقة');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && !$purchaseRequest->isVisibleToUserId($user->id)) {
            abort(403, 'هذا الطلب ليس موجهًا إليك');
        }

        $purchaseRequest->load([
            'user.jobTitle', 'center', 'project', 'items.executor',
            'approver1User', 'approver2User', 'approver3User', 'logisticsStaff', 'lockedByUser',
            'signatures.user', 'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.logistics.purchase-requests.show', compact('purchaseRequest', 'users'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeEditable($purchaseRequest);

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();
        $departments = Department::where('is_active', 1)->orderBy('name_ar')->get();

        $purchaseRequest->load('items');
        $defaultApproverId = $purchaseRequest->refer_to_approver1_id;
        $employee = Employee::where('user_id', auth()->id())->first();
        $requestType = $purchaseRequest->request_type ?: 'purchase';

        return view('admin.logistics.purchase-requests.form', compact(
            'purchaseRequest', 'centers', 'projects', 'users', 'departments',
            'defaultApproverId', 'employee', 'requestType'
        ));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorizeEditable($purchaseRequest);

        $validated = $this->validateRequest($request, $purchaseRequest);

        $approver1 = $validated['refer_to_approver1_id'] ?? $purchaseRequest->refer_to_approver1_id;
        if ((int) $approver1 === (int) $purchaseRequest->user_id) {
            return back()->withInput()->withErrors(['refer_to_approver1_id' => 'لا يمكن إحالة الطلب إلى منشئه.']);
        }

        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $totalPrice += $item['quantity'] * $item['unit_price'];
        }

        if ($approver1 !== null && (int) $approver1 !== (int) $purchaseRequest->refer_to_approver1_id) {
            $purchaseRequest->completeReferral('approver1');
            $purchaseRequest->update(['refer_to_approver1_id' => $approver1]);
            $purchaseRequest->referTo($approver1, 'approver1');
            $purchaseRequest->logWorkflow('referred', $approver1, 'غيّر المنشئ وجهة الإحالة عند التعديل');
        }

        $purchaseRequest->update([
            'center_id' => $validated['center_id'] ?? $purchaseRequest->center_id,
            'project_id' => $validated['project_id'] ?? $purchaseRequest->project_id,
            'pr_date' => $validated['pr_date'],
            'required_date' => $validated['required_date'] ?? null,
            'management_unit' => $validated['management_unit'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'specifications' => $this->specsSummary($validated['items']),
            'quantity' => count($validated['items']),
            'expected_total_price' => $totalPrice,
        ]);

        $this->syncItems($purchaseRequest, $validated['items']);

        return redirect()->route('admin.logistics.purchase-requests.show', $purchaseRequest)
            ->with('success', 'تم تحديث طلب الشراء.');
    }

    /*
     * الموافقة — خطوة واحدة تتقدم بالحالة. الموقع الإلزامي صورة مرفوعة،
     * ومع كل موافقة تُختار الوجهة التالية من قائمة قابلة للبحث.
     * الحصرية مطلقة: المستلم الحالي فقط، ولا استثناء لـ super-admin.
     */
    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        $step = $purchaseRequest->currentStep();
        abort_if(! in_array($step, ['approver1', 'approver2', 'approver3'], true), 403, 'لا توجد موافقة مطلوبة في هذا الطور.');
        $this->authorizeHolderStrict($purchaseRequest, $step);

        $isFinal = $step === 'approver3';

        $validated = $request->validate([
            'signature_image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'note' => 'nullable|string|max:1000',
            'next_approver_id' => ['required_without:logistics_user_id', 'nullable', 'exists:users,id'],
            'logistics_user_id' => [$isFinal ? 'required' : 'nullable', 'exists:users,id'],
        ], [], ['next_approver_id' => 'الموافق التالي', 'logistics_user_id' => 'مدير اللوجستي']);

        $nextId = $isFinal ? $validated['logistics_user_id'] : ($validated['next_approver_id'] ?? null);
        if ($nextId === null) {
            return back()->withErrors(['next_approver_id' => 'اختر الشخص التالي من القائمة.']);
        }

        if ((int) $nextId === (int) auth()->id()) {
            return back()->withErrors(['next_approver_id' => 'لا يمكن تمرير الطلب لنفسك.']);
        }

        $signaturePath = $request->file('signature_image')->store('pr-signatures', 'public');
        $user = $request->user();

        PurchaseRequestSignature::updateOrCreate(
            ['purchase_request_id' => $purchaseRequest->id, 'role' => $step],
            [
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $user->jobTitle?->title_ar ?? ($isFinal ? 'المدير التنفيذي' : null),
                'signed_at' => now(),
                'signature_path' => $signaturePath,
            ]
        );

        $column = match ($step) {
            'approver1' => 'refer_to_approver2_id',
            'approver2' => 'refer_to_approver3_id',
            'approver3' => 'refer_to_logistics_id',
        };

        $updates = [$column => $nextId];

        if ($isFinal) {
            $updates['locked_at'] = now();
            $updates['locked_by'] = $user->id;
            $updates['approved_at'] = now();
        }

        $newStatus = match ($step) {
            'approver1' => 'approved1',
            'approver2' => 'approved2',
            'approver3' => 'approved',
        };
        $updates['status'] = $newStatus;

        $purchaseRequest->update($updates);
        $purchaseRequest->completeReferral($step);
        $purchaseRequest->referTo($nextId, $isFinal ? 'logistics' : ($step === 'approver1' ? 'approver2' : 'approver3'), $validated['note'] ?? null);
        $purchaseRequest->logWorkflow('approved', $nextId, $validated['note'] ?? null, $newStatus);

        return back()->with('success', 'تمت الموافقة والتوقيع وإحالة الطلب للجهة التالية.');
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $step = $purchaseRequest->currentStep();
        abort_if($step === null || $step === 'logistics', 403, 'لا توجد موافقة قائمة يمكن رفضها.');
        $this->authorizeHolderStrict($purchaseRequest, $step);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $purchaseRequest->update(['status' => 'rejected', 'notes' => trim(($purchaseRequest->notes.PHP_EOL.'رفض: '.$validated['reason']))]);
        $purchaseRequest->completeReferral($step);
        $purchaseRequest->logWorkflow('rejected', null, $validated['reason'], 'rejected');

        return back()->with('error', 'تم رفض طلب الشراء.');
    }

    /*
     * إعادة الإحالة: المستلم الحالي للخطوة فقط يمررها لغيره.
     */
    public function refer(Request $request, PurchaseRequest $purchaseRequest)
    {
        $step = $purchaseRequest->currentStep();
        abort_if($step === null, 403, 'الطلب منجز أو مرفوض.');
        $this->authorizeHolderStrict($purchaseRequest, $step);

        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        if ((int) $validated['to_user_id'] === (int) auth()->id()) {
            return back()->withErrors(['to_user_id' => 'أنت المستلم الحالي.']);
        }

        $column = match ($step) {
            'approver1' => 'refer_to_approver1_id',
            'approver2' => 'refer_to_approver2_id',
            'approver3' => 'refer_to_approver3_id',
            'logistics' => 'refer_to_logistics_id',
        };

        $purchaseRequest->completeReferral($step);
        $purchaseRequest->update([$column => $validated['to_user_id']]);
        $purchaseRequest->referTo($validated['to_user_id'], $step, $validated['note'] ?? null);
        $purchaseRequest->logWorkflow('referred', $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت إحالة الطلب.');
    }

    /*
     * تنفيذ اللوجستي: تعليم كل بند منفَّذ/غير منفَّذ — لا توقيع هنا.
     * يكتمل الطلب تلقائياً عند تعليم كل البنود.
     */
    public function executeItems(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_if($purchaseRequest->status !== 'approved', 403, 'الطلب لم يصل بعد لمرحلة التنفيذ.');
        $this->authorizeHolderStrict($purchaseRequest, 'logistics');

        $validated = $request->validate([
            'executed_ids' => 'nullable|array',
            'executed_ids.*' => 'integer|exists:logistics_purchase_request_items,id',
        ]);

        $itemIds = $purchaseRequest->items()->pluck('id')->all();
        $selected = array_map('intval', $validated['executed_ids'] ?? []);

        foreach ($itemIds as $id) {
            $item = PurchaseRequestItem::find($id);
            $shouldBeExecuted = in_array((int) $id, $selected, true);

            if ($shouldBeExecuted && $item->executed_at === null) {
                $item->update(['executed_at' => now(), 'executed_by' => auth()->id()]);
            } elseif (! $shouldBeExecuted && $item->executed_at !== null) {
                $item->update(['executed_at' => null, 'executed_by' => null]);
            }
        }

        $remaining = $purchaseRequest->items()->whereNull('executed_at')->count();

        if ($remaining === 0) {
            $purchaseRequest->update(['status' => 'executed']);
            $purchaseRequest->completeReferral('logistics');
            $purchaseRequest->logWorkflow('executed', null, 'تم تعليم كل البنود كمنفذة', 'executed');

            return back()->with('success', 'تم تنفيذ كل بنود الطلب وأُغلق الطلب.');
        }

        return back()->with('success', 'تم تحديث حالات البنود — بقي '.$remaining.' بنداً للتنفيذ.');
    }

    public function printForm(PurchaseRequest $purchaseRequest)
    {
        $this->ensureVisible($purchaseRequest);

        return view('admin.logistics.purchase-requests.print', $this->printData($purchaseRequest));
    }

    public function exportExcel(PurchaseRequest $purchaseRequest)
    {
        $this->ensureVisible($purchaseRequest);
        $data = $this->printData($purchaseRequest);

        $filename = 'PR-'.$data['purchaseRequest']->request_number.'.xlsx';

        return Excel::download(new PurchaseRequestFormExport(
            $data['purchaseRequest'],
            $data['rows'],
            $data['totals'],
            $data['signatures'],
        ), $filename);
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->isLocked()) {
            abort(403, 'الطلب معتمد نهائياً — لا يمكن حذفه');
        }

        $purchaseRequest->delete();

        return redirect()->route('admin.logistics.purchase-requests.index')
            ->with('success', 'تم حذف طلب الشراء بنجاح');
    }

    /* ------------------------------------------------------------ helpers */

    private function printData(PurchaseRequest $purchaseRequest): array
    {
        $purchaseRequest->load(['items', 'project', 'center', 'user.jobTitle', 'signatures.user']);

        $rows = [];
        foreach ($purchaseRequest->items as $i => $item) {
            $rows[] = [
                'n' => $i + 1,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'currency' => strtoupper((string) $item->currency),
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'budget_line' => $item->budget_line,
                'executed' => $item->executed_at !== null,
            ];
        }

        $signatures = [];
        $creator = $purchaseRequest->user;
        $requested = $purchaseRequest->signatures->firstWhere('role', 'requested_by');
        $signatures['requested_by'] = [
            'name' => $requested?->name ?? $creator?->name ?? '',
            'position' => $requested?->position ?? $creator?->jobTitle?->title_ar ?? '',
            'date' => $requested?->signed_at ?? $purchaseRequest->pr_date,
            'image' => $requested?->signature_path,
        ];

        foreach (['approver1' => 'direct_manager', 'approver2' => 'finance', 'approver3' => 'ceo'] as $role => $key) {
            $sig = $purchaseRequest->signatures->firstWhere('role', $role);
            $signatures[$key] = [
                'name' => $sig?->name ?? '',
                'position' => $sig?->position ?? '',
                'date' => $sig?->signed_at,
                'image' => $sig?->signature_path,
            ];
        }

        return [
            'purchaseRequest' => $purchaseRequest,
            'rows' => $rows,
            'totals' => $purchaseRequest->totalsByCurrency(),
            'signatures' => $signatures,
        ];
    }

    private function ensureVisible(PurchaseRequest $purchaseRequest): void
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $purchaseRequest->isVisibleToUserId($user->id)) {
            abort(403, 'هذا الطلب ليس موجهًا إليك');
        }
    }

    private function validateRequest(Request $request, ?PurchaseRequest $purchaseRequest = null): array
    {
        return $request->validate([
            'request_number' => [
                'required', 'string', 'max:60',
                Rule::unique('logistics_purchase_requests', 'request_number')
                    ->whereNull('deleted_at')
                    ->ignore($purchaseRequest?->id),
            ],
            'pr_date' => 'required|date',
            'required_date' => 'required|date',
            'request_type' => ['nullable', Rule::in(['purchase', 'maintenance'])],
            'center_id' => 'required|exists:centers,id',
            'project_id' => 'required|exists:projects,id',
            'management_unit' => 'required|string|max:255',
            'refer_to_approver1_id' => 'required|exists:users,id',
            'notes' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.description' => 'required|string|max:2000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => ['required', 'in:'.implode(',', \App\Models\Admin\Logistics\PurchaseRequestItem::UNITS)],
            'items.*.currency' => 'required|in:USD,SYP',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.budget_line' => 'required|string|max:60',
            'items.*.notes' => 'required|string|max:1000',
        ]);
    }

    /*
     * مزامنة بنود الطلب (إنشاء/تحديث/حذف) — الحذف قبل الإنشاء (نمط المقررات).
     */
    private function syncItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $existing = $purchaseRequest->items()->pluck('id')->all();
        $keepIds = [];

        foreach ($items as $row) {
            $data = [
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'currency' => $row['currency'],
                'unit_price' => $row['unit_price'],
                'total_price' => $row['quantity'] * $row['unit_price'],
                'budget_line' => $row['budget_line'] ?? null,
                'notes' => $row['notes'] ?? null,
            ];

            if (! empty($row['id']) && in_array((int) $row['id'], $existing, true)) {
                PurchaseRequestItem::where('id', $row['id'])
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->update($data);
                $keepIds[] = (int) $row['id'];
            } else {
                $keepIds[] = $purchaseRequest->items()->create($data)->id;
            }
        }

        $purchaseRequest->items()->whereNotIn('id', $keepIds)->delete();
    }

    private function specsSummary(array $items): string
    {
        return collect($items)->pluck('description')->implode(' | ');
    }

    private function storeRequestedSignature(Request $request, PurchaseRequest $purchaseRequest): void
    {
        $path = null;

        if ($request->hasFile('signature_image')) {
            $validated = $request->validate([
                'signature_image' => 'image|mimes:png,jpg,jpeg|max:2048',
            ]);
            $path = $request->file('signature_image')->store('pr-signatures', 'public');
        }

        if ($path === null) {
            return;
        }

        $user = $request->user();

        PurchaseRequestSignature::updateOrCreate(
            ['purchase_request_id' => $purchaseRequest->id, 'role' => 'requested_by'],
            [
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $user->jobTitle?->title_ar,
                'signed_at' => now(),
                'signature_path' => $path,
            ]
        );
    }

    /*
     * المرشح الافتراضي للموافقة الأولى: حامل صلاحية عرض لوحة مدير المشاريع.
     */
    private function defaultApproverId(): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.projects-manager.dashboard')
            ->where('can_view', 1)
            ->whereNull('center_id')
            ->whereNull('project_id')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    /*
     * الحصرية الصارمة: المستلم الحالي للخطوة فقط — لا super-admin ولا ملاك آخرون.
     */
    private function authorizeHolderStrict(PurchaseRequest $purchaseRequest, string $step): void
    {
        $user = auth()->user();

        $column = match ($step) {
            'approver1' => 'refer_to_approver1_id',
            'approver2' => 'refer_to_approver2_id',
            'approver3' => 'refer_to_approver3_id',
            'logistics' => 'refer_to_logistics_id',
        };

        $isHolder = $purchaseRequest->isCurrentRecipient($user->id)
            || ((int) $purchaseRequest->{$column} === (int) $user->id);

        abort_if(! $isHolder, 403, 'هذه الخطوة موجهة لشخص آخر — أنت لست صاحبها الحالي.');
    }

    private function authorizeEditable(PurchaseRequest $purchaseRequest): void
    {
        abort_if($purchaseRequest->status !== 'review', 403, 'بدأت الموافقات — الطلب لم يعد قابلًا للتعديل.');

        $user = auth()->user();
        abort_if((int) $purchaseRequest->user_id !== (int) $user->id, 403, 'تعديل الطلب متاح لمنشئه فقط أثناء المراجعة.');
    }
}
