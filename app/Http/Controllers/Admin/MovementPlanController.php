<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanEntry;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class MovementPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MovementPlan,view')->only(['index', 'show', 'help', 'exportExcel', 'exportPdf']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,edit')->only(['approve', 'reject', 'assign', 'complete', 'refer']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $plans = $this->scopedQuery($request)
            ->orderBy('plan_month', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $centers = Center::orderBy('name')->get();

        return view('admin.movement-plans.index', compact('plans', 'centers'));
    }

    /*
     * نطاق رؤية خطط الحركة: غير الأدمن يرى ما له فيه علاقة (إنشاء/إحالة/توزيع/متابعة).
     */
    private function scopedQuery(Request $request)
    {
        $user = $request->user();

        $query = MovementPlan::with(['center', 'project', 'creator', 'movementOfficer'])
            ->withCount(['entries', 'recipients']);

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('refer_to_pm2_id', $user->id)
                    ->orWhere('refer_to_movement_officer_id', $user->id)
                    ->orWhere('assigned_by', $user->id)
                    ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('month')) {
            $query->where('plan_month', 'like', $request->month.'%');
        }

        return $query;
    }

    /*
     * أسطر التصدير: بند حركة واحد لكل سطر مع بيانات خطته.
     */
    private function exportRows(Request $request)
    {
        $plans = $this->scopedQuery($request)
            ->with(['entries', 'recipients.user'])
            ->orderBy('plan_month', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $plans->flatMap(fn ($plan) => $plan->entries->map(fn ($entry) => [
            'request_number' => $plan->request_number,
            'plan_month' => $plan->plan_month?->format('Y-m') ?? '',
            'movement_date' => $entry->movement_date->format('Y-m-d'),
            'day_name' => $entry->movement_date->dayName,
            'departure_time' => $entry->departure_time ? substr((string) $entry->departure_time, 0, 5) : '',
            'return_time' => $entry->return_time ? substr((string) $entry->return_time, 0, 5) : '',
            'from_location' => $entry->from_location ?? '',
            'to_location' => $entry->to_location ?? '',
            'purpose' => $entry->purpose,
            'entry_notes' => $entry->notes ?? '',
            'status_key' => $plan->status,
            'status' => MovementPlan::STATUSES[$plan->status] ?? $plan->status,
            'center' => $plan->center?->name ?? '',
            'project' => $plan->project?->name ?? '',
            'creator' => $plan->creator?->name ?? '',
            'officer' => $plan->movementOfficer?->name ?? '',
            'recipients' => $plan->recipients->pluck('user.name')->filter()->implode('، '),
        ]));
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->exportRows($request);

        $export = new \App\Exports\BaseExport(
            $rows,
            ['رقم الخطة', 'شهر الخطة', 'التاريخ', 'اليوم', 'الانطلاق', 'العودة', 'من', 'إلى', 'الغاية', 'ملاحظات البند', 'حالة الخطة', 'المركز', 'المشروع', 'أنشأها', 'مسؤول الحركة', 'المتابِعون'],
            ['request_number', 'plan_month', 'movement_date', 'day_name', 'departure_time', 'return_time', 'from_location', 'to_location', 'purpose', 'entry_notes', 'status', 'center', 'project', 'creator', 'officer', 'recipients'],
        );

        // BaseExport يفترض data_get على الكائنات؛ المصفولات الترابطية تعمل معه مباشرة
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'خطط-الحركة-'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->exportRows($request);

        return view('admin.movement-plans.print', [
            'rows' => $rows,
            'month' => $request->month,
            'status' => $request->filled('status') ? MovementPlan::STATUSES[$request->status] : null,
        ]);
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        $employee = Employee::where('user_id', auth()->id())->first();
        $defaultReferralId = $this->defaultProjectsManagerId($employee?->project_id);
        if ($defaultReferralId !== null && (int) $defaultReferralId === (int) auth()->id()) {
            $defaultReferralId = null;
        }

        return view('admin.movement-plans.form', compact('centers', 'projects', 'users', 'defaultReferralId'));
    }

    public function edit(MovementPlan $movementPlan)
    {
        $this->authorizeEditable($movementPlan);

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        $movementPlan->load('entries');
        $defaultReferralId = $movementPlan->refer_to_pm2_id;

        return view('admin.movement-plans.form', compact('movementPlan', 'centers', 'projects', 'users', 'defaultReferralId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $employee = Employee::where('user_id', auth()->id())->first();

        $referralId = $validated['refer_to_pm2_id'] ?? $this->defaultProjectsManagerId($validated['project_id'] ?? $employee?->project_id);
        if ($referralId !== null && (int) $referralId === (int) auth()->id()) {
            $referralId = null;
        }

        $plan = MovementPlan::create([
            'request_number' => $this->nextRequestNumber(),
            'created_by' => auth()->id(),
            'center_id' => $validated['center_id'] ?? $employee?->center_id,
            'project_id' => $validated['project_id'] ?? $employee?->project_id,
            'plan_month' => $validated['plan_month'],
            'notes' => $validated['notes'] ?? null,
            'refer_to_pm2_id' => $referralId,
            'status' => 'review',
        ]);

        $this->syncEntries($plan, $validated['entries']);

        $plan->logWorkflow('create', $plan->refer_to_pm2_id, 'تم إنشاء خطة الحركة وإحالتها للمراجعة', 'review');

        if ($plan->refer_to_pm2_id !== null) {
            $plan->referTo($plan->refer_to_pm2_id, 'pm2');
        }

        return redirect()->route('admin.movement-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة الحركة وإحالتها للمراجعة');
    }

    public function update(Request $request, MovementPlan $movementPlan)
    {
        $this->authorizeEditable($movementPlan);

        $validated = $this->validatePlan($request);

        $newReferral = $validated['refer_to_pm2_id'] ?? null;

        if (
            $newReferral !== null
            && (int) $newReferral !== (int) $movementPlan->refer_to_pm2_id
        ) {
            if ((int) $newReferral === (int) $movementPlan->created_by) {
                return back()->withInput()->withErrors(['refer_to_pm2_id' => 'لا يمكن إحالة الخطة إلى منشئها.']);
            }

            $movementPlan->completeReferral('pm2');
            $movementPlan->update(['refer_to_pm2_id' => $newReferral]);
            $movementPlan->referTo($newReferral, 'pm2');
            $movementPlan->logWorkflow('referred', $newReferral, 'غيّر المنشئ وجهة الإحالة عند التعديل');
        }

        $movementPlan->update([
            'center_id' => $validated['center_id'] ?? $movementPlan->center_id,
            'project_id' => $validated['project_id'] ?? $movementPlan->project_id,
            'plan_month' => $validated['plan_month'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncEntries($movementPlan, $validated['entries']);

        return redirect()->route('admin.movement-plans.show', $movementPlan)
            ->with('success', 'تم تحديث خطة الحركة وبنودها.');
    }

    /**
     * التحقق المشترك بين الإنشاء والتعديل: رأس الخطة + بنود الحركات المتعددة
     * مع فحص وقت العودة مقابل الانطلاق لكل بند.
     */
    private function validatePlan(Request $request): array
    {
        $validated = $request->validate([
            'plan_month' => 'required|date_format:Y-m',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'refer_to_pm2_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'entries' => 'required|array|min:1',
            'entries.*.id' => 'nullable|exists:movement_plan_entries,id',
            'entries.*.movement_date' => 'required|date',
            'entries.*.departure_time' => 'nullable|date_format:H:i',
            'entries.*.return_time' => 'nullable|date_format:H:i',
            'entries.*.from_location' => 'nullable|string|max:255',
            'entries.*.to_location' => 'nullable|string|max:255',
            'entries.*.purpose' => 'required|string|max:2000',
            'entries.*.notes' => 'nullable|string|max:2000',
        ]);

        $validated['plan_month'] = $validated['plan_month'].'-01';

        foreach ($validated['entries'] as $i => $entry) {
            if (
                ($entry['departure_time'] ?? null)
                && ($entry['return_time'] ?? null)
                && $entry['return_time'] < $entry['departure_time']
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "entries.$i.return_time" => 'وقت العودة يجب أن يكون بعد وقت الانطلاق.',
                ]);
            }
        }

        return $validated;
    }

    /**
     * مزامنة بنود الحركات: تحديث الموجود (بالـ id) وإنشاء الجديد وحذف الغائب —
     * الحذف قبل الإنشاء (نمط CourseController).
     */
    private function syncEntries(MovementPlan $plan, array $entries): void
    {
        $keepIds = [];

        $existing = $plan->entries()->pluck('id')->all();

        foreach ($entries as $row) {
            $data = [
                'movement_date' => $row['movement_date'],
                'departure_time' => $row['departure_time'] ?? null,
                'return_time' => $row['return_time'] ?? null,
                'from_location' => $row['from_location'] ?? null,
                'to_location' => $row['to_location'] ?? null,
                'purpose' => $row['purpose'],
                'notes' => $row['notes'] ?? null,
            ];

            if (! empty($row['id']) && in_array((int) $row['id'], $existing, true)) {
                MovementPlanEntry::where('id', $row['id'])->where('movement_plan_id', $plan->id)->update($data);
                $keepIds[] = (int) $row['id'];
            } else {
                $keepIds[] = $plan->entries()->create($data)->id;
            }
        }

        $plan->entries()->whereNotIn('id', $keepIds)->delete();
    }

    private function authorizeEditable(MovementPlan $movementPlan): void
    {
        abort_if($movementPlan->status !== 'review', 403, 'الخطة لم تعد في مرحلة المراجعة — لا يمكن تعديل بنودها.');

        $user = auth()->user();
        abort_if($user->type !== 'super-admin' && (int) $movementPlan->created_by !== (int) $user->id, 403, 'تعديل الخطة متاح لمنشئها فقط أثناء المراجعة.');
    }

    public function help()
    {
        return view('admin.movement-plans.help');
    }

    public function show(MovementPlan $movementPlan)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $movementPlan->isVisibleToUserId($user->id)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }

        $movementPlan->load([
            'creator', 'center', 'project', 'movementOfficer', 'projectsManager', 'assigner',
            'entries', 'recipients.user', 'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.movement-plans.show', ['plan' => $movementPlan, 'users' => $users]);
    }

    public function approve(Request $request, MovementPlan $movementPlan)
    {
        $this->authorizeStep($movementPlan, 'pm2', 'review');

        $validated = $request->validate([
            'movement_officer_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $movementPlan->update([
            'refer_to_movement_officer_id' => $validated['movement_officer_id'],
            'status' => 'approved',
        ]);

        $movementPlan->completeReferral('pm2');
        $movementPlan->referTo($validated['movement_officer_id'], 'movement_officer', $validated['note'] ?? null);

        $movementPlan->logWorkflow('approve', $validated['movement_officer_id'], $validated['note'] ?? 'وافقت إدارة المشاريع وأُحيلت لمسؤول الحركة', 'approved');

        return back()->with('success', 'اعتُمدت خطة الحركة وأُحيلت لمسؤول الحركة.');
    }

    public function reject(Request $request, MovementPlan $movementPlan)
    {
        $this->authorizeStep($movementPlan, 'pm2', 'review');

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $movementPlan->update([
            'status' => 'rejected',
            'reason' => $validated['reason'],
        ]);

        $movementPlan->completeReferral('pm2');
        $movementPlan->logWorkflow('reject', null, $validated['reason'], 'rejected');

        return back()->with('success', 'رُفضت خطة الحركة.');
    }

    public function assign(Request $request, MovementPlan $movementPlan)
    {
        abort_if(! in_array($movementPlan->status, ['approved', 'assigned'], true), 403, 'الخطة ليست في مرحلة توزيع المتابِعين.');

        $this->authorizeHolder($movementPlan, 'movement_officer');

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
            'role_labels' => 'nullable|array',
            'role_labels.*' => 'nullable|string|max:100',
        ]);

        $movementPlan->recipients()->delete();

        foreach ($validated['user_ids'] as $index => $userId) {
            MovementPlanRecipient::create([
                'movement_plan_id' => $movementPlan->id,
                'user_id' => $userId,
                'role_label' => $validated['role_labels'][$index] ?? null,
            ]);
        }

        $movementPlan->update([
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $movementPlan->completeReferral('movement_officer');
        $movementPlan->referToMany($validated['user_ids'], 'recipient', 'متابعة خطة الحركة');

        $movementPlan->logWorkflow('assign', null, 'حدّد مسؤول الحركة المستفيدين للمتابعة', 'assigned');

        return back()->with('success', 'حُددت جهات المتابعة — خطة الحركة قيد المتابعة الآن.');
    }

    public function complete(MovementPlan $movementPlan)
    {
        abort_if($movementPlan->status !== 'assigned', 403, 'الخطة ليست قيد المتابعة.');

        $this->authorizeHolder($movementPlan, 'movement_officer');

        $movementPlan->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $movementPlan->completeReferral('recipient');
        $movementPlan->logWorkflow('complete', null, 'أُنجزت خطة الحركة', 'completed');

        return back()->with('success', 'أُنقلت خطة الحركة كمنجزة.');
    }

    /*
     * إعادة الإحالة: يقوم بها المستلَم الحالي للخطوة فقط.
     */
    public function refer(Request $request, MovementPlan $movementPlan)
    {
        $validated = $request->validate([
            'step' => 'required|in:pm2,movement_officer',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->authorizeHolder($movementPlan, $validated['step']);

        $column = $validated['step'] === 'pm2'
            ? 'refer_to_pm2_id'
            : 'refer_to_movement_officer_id';

        $movementPlan->update([$column => $validated['to_user_id']]);
        $movementPlan->referTo($validated['to_user_id'], $validated['step'], $validated['note'] ?? null);
        $movementPlan->logWorkflow('referred', $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت إحالة خطة الحركة بنجاح.');
    }

    private function authorizeStep(MovementPlan $movementPlan, string $step, string $expectedStatus): void
    {
        $this->authorizeHolder($movementPlan, $step);

        if ($movementPlan->status !== $expectedStatus) {
            abort(403, 'حالة الخطة لا تسمح بهذه الخطوة.');
        }
    }

    /*
     * المستلَم الحالي للخطوة (عبر الإحالة النشطة أو العمود القديم). إذا لم
     * يُحدَّد أحد بعد (بيانات قديمة) يبقى التصرف متاحاً لمن يملك صلاحية
     * التعديل توافقاً مع السلوك السابق.
     */
    private function authorizeHolder(MovementPlan $movementPlan, string $step): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return;
        }

        $column = $step === 'pm2' ? 'refer_to_pm2_id' : 'refer_to_movement_officer_id';

        // لا يوجد مستلَم معيّن بعد (بيانات قديمة) → يواصل التصرف لمن يملك
        // صلاحية التعديل كما كان السلوك سابقاً.
        if ($movementPlan->{$column} === null) {
            return;
        }

        $isHolder = $movementPlan->isCurrentRecipient($user->id)
            || ((int) $movementPlan->{$column} === (int) $user->id);

        if (! $isHolder) {
            abort(403, 'هذه الخطوة ليست موجهة إليك.');
        }
    }

    private function defaultProjectsManagerId(?int $projectId): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
            ->where('can_view', 1)
            ->when($projectId, fn ($q) => $q->where(fn ($s) => $s->whereNull('project_id')->orWhere('project_id', $projectId)))
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    public function destroy(MovementPlan $movementPlan)
    {
        $movementPlan->delete();

        return redirect()->route('admin.movement-plans.index')
            ->with('success', 'تم حذف خطة الحركة.');
    }

    private function nextRequestNumber(): string
    {
        return 'MOV-' . now()->year . '-' . str_pad(
            (string) (MovementPlan::count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}