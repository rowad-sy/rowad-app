<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\PermissionHelper;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Group;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanEntry;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Project;
use App\Support\RoleHoldersLookup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovementPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MovementPlan,view')->only(['index', 'show', 'help', 'exportExcel', 'exportPdf', 'printOne', 'exportExcelOne']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,edit')->only(['approve', 'reject', 'accept', 'assign', 'complete', 'refer']);
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
            $scope = PermissionHelper::getEffectiveScope($user, MovementPlan::class, 'can_view');

            if (! $scope['sees_all']) {
                $scopePlanIds = $this->permissionVisiblePlanIds($user, $scope);
                $roleQueuePlanIds = $this->projectsManagerQueuePlanIds($user);

                $query->where(function ($q) use ($user, $scopePlanIds, $roleQueuePlanIds) {
                    $q->where('created_by', $user->id)
                    ->orWhere('refer_to_pm2_id', $user->id)
                    ->orWhere('refer_to_movement_officer_id', $user->id)
                    ->orWhere('assigned_by', $user->id)
                    ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id))
                    ->orWhereIn('id', $scopePlanIds ?: [-1])
                    ->orWhereIn('id', $roleQueuePlanIds ?: [-1]);
                });
            }
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

    /** IDs visible through center/project-scoped view permissions. */
    private function permissionVisiblePlanIds(User $user, array $scope): array
    {
        $projectIds = array_map('intval', $scope['project_ids'] ?? []);
        $centerIds = array_map('intval', $scope['center_ids'] ?? []);

        if ($projectIds === [] && $centerIds === []) {
            return [];
        }

        return MovementPlan::query()
            ->where(function ($query) use ($projectIds, $centerIds) {
                if ($projectIds !== []) {
                    $query->whereIn('project_id', $projectIds);
                }
                if ($centerIds !== []) {
                    $method = $projectIds === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}('center_id', $centerIds);
                }
            })
            ->get(['id', 'project_id', 'center_id'])
            ->filter(fn (MovementPlan $plan) => PermissionHelper::canAccessRecord($user, MovementPlan::class, 'view', $plan))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Plans waiting in the projects-manager role queue and visible to this holder. */
    private function projectsManagerQueuePlanIds(User $user): array
    {
        return MovementPlan::query()
            ->where('status', 'review')
            ->where('review_role_key', Group::ROLE_PROJECTS_MANAGER)
            ->get(['id', 'project_id', 'center_id', 'status', 'review_role_key'])
            ->filter(fn (MovementPlan $plan) => $this->isRoleHolderForPlan($user, Group::ROLE_PROJECTS_MANAGER, $plan, 'edit'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function roleHoldersForPlan(string $roleKey, MovementPlan $plan, string $permission = 'edit')
    {
        $role = Group::roles()->where('role_key', $roleKey)->first();
        if (! $role) {
            return collect();
        }

        $eligibleIds = RoleHoldersLookup::userIds(MovementPlan::class, $permission, $plan->project_id, $plan->center_id);
        if ($eligibleIds === []) {
            return collect();
        }

        return $role->users()
            ->whereIn('users.id', $eligibleIds)
            ->where('users.type', 'employee')
            ->where('users.is_active', true)
            ->orderBy('users.name')
            ->get()
            ->filter(fn (User $user) => PermissionHelper::canAccessRecord($user, MovementPlan::class, $permission, $plan))
            ->values();
    }

    private function isRoleHolderForPlan(User $user, string $roleKey, MovementPlan $plan, string $permission = 'edit'): bool
    {
        $role = Group::roles()->where('role_key', $roleKey)->first();

        return $role !== null
            && $role->users()->where('users.id', $user->id)->exists()
            && PermissionHelper::canAccessRecord($user, MovementPlan::class, $permission, $plan);
    }

    private function movementOfficerCandidates(MovementPlan $plan)
    {
        // Existing plans keep their historical direct-recipient behavior.
        if ($plan->review_role_key !== Group::ROLE_PROJECTS_MANAGER) {
            return User::where('type', 'employee')->where('is_active', true)->orderBy('name')->get();
        }

        return $this->roleHoldersForPlan(Group::ROLE_MOVEMENT_OFFICER, $plan, 'edit');
    }

    private function canViewPlan(User $user, MovementPlan $plan): bool
    {
        return $user->type === 'super-admin'
            || $plan->isVisibleToUserId($user->id)
            || ($plan->status === 'review'
                && $plan->review_role_key === Group::ROLE_PROJECTS_MANAGER
                && $this->isRoleHolderForPlan($user, Group::ROLE_PROJECTS_MANAGER, $plan, 'edit'));
    }

    private function assertRoleConfigured(string $roleKey, MovementPlan $plan, string $field, string $label): void
    {
        if (! Group::roles()->where('role_key', $roleKey)->exists()) {
            throw ValidationException::withMessages([$field => "دور {$label} غير معرّف في الأدوار والنطاقات."]);
        }

        if ($this->roleHoldersForPlan($roleKey, $plan)->isEmpty()) {
            throw ValidationException::withMessages([$field => "لا يوجد مستخدم نشط ومخوّل ضمن دور {$label} لهذا المركز/المشروع."]);
        }
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
            'return_date' => $entry->return_date?->format('Y-m-d') ?? $entry->movement_date->format('Y-m-d'),
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

        $export = new \App\Exports\MovementPlanExport(
            collect($rows->all()),
            ['رقم الخطة', 'شهر الخطة', 'تاريخ الانطلاق', 'تاريخ العودة', 'من', 'إلى', 'وقت الانطلاق', 'وقت العودة', 'الغاية', 'ملاحظات البند', 'حالة الخطة', 'مسؤول الحركة', 'المتابِعون'],
            [
                'month' => $request->month,
                'status' => $request->filled('status') ? MovementPlan::STATUSES[$request->status] : null,
                'count' => $rows->count(),
            ],
        );

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

    /*
     * التصدير/الطباعة الفردية لخطة واحدة — بنود الخطة فقط، مع فحص الرؤية نفسه في show.
     */
    public function printOne(MovementPlan $movementPlan)
    {
        $this->ensureVisiblePlan($movementPlan);

        return view('admin.movement-plans.print', [
            'rows' => collect($this->rowsOfPlan($movementPlan)),
            'month' => $movementPlan->plan_month?->format('Y-m'),
            'status' => MovementPlan::STATUSES[$movementPlan->status] ?? null,
            'plan' => $movementPlan,
        ]);
    }

    public function exportExcelOne(MovementPlan $movementPlan)
    {
        $this->ensureVisiblePlan($movementPlan);

        $rows = collect($this->rowsOfPlan($movementPlan));

        $export = new \App\Exports\MovementPlanExport(
            $rows,
            ['رقم الخطة', 'شهر الخطة', 'تاريخ الانطلاق', 'تاريخ العودة', 'من', 'إلى', 'وقت الانطلاق', 'وقت العودة', 'الغاية', 'ملاحظات البند', 'حالة الخطة', 'مسؤول الحركة', 'المتابِعون'],
            [
                'month' => $movementPlan->plan_month?->format('Y-m'),
                'status' => MovementPlan::STATUSES[$movementPlan->status] ?? null,
                'count' => $rows->count(),
                'plan' => $movementPlan->request_number,
                'center' => $movementPlan->center?->name,
                'project' => $movementPlan->project?->name,
            ],
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, $movementPlan->request_number.'.xlsx');
    }

    private function ensureVisiblePlan(MovementPlan $movementPlan): void
    {
        $user = auth()->user();
        if (! $this->canViewPlan($user, $movementPlan)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }
    }

    private function rowsOfPlan(MovementPlan $plan): array
    {
        $plan->loadMissing(['entries', 'recipients.user', 'center', 'project', 'creator', 'movementOfficer']);

        return $plan->entries->map(fn ($entry) => [
            'request_number' => $plan->request_number,
            'plan_month' => $plan->plan_month?->format('Y-m') ?? '',
            'movement_date' => $entry->movement_date->format('Y-m-d'),
            'return_date' => $entry->return_date?->format('Y-m-d') ?? $entry->movement_date->format('Y-m-d'),
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
        ])->all();
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::ordered()->get();
        $roleContext = new MovementPlan();
        $projectsManagerRoleConfigured = Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)->exists();
        $projectsManagerCount = $this->roleHoldersForPlan(Group::ROLE_PROJECTS_MANAGER, $roleContext)->count();

        return view('admin.movement-plans.form', compact('centers', 'projects', 'projectsManagerRoleConfigured', 'projectsManagerCount'));
    }

    public function edit(MovementPlan $movementPlan)
    {
        $this->authorizeEditable($movementPlan);

        $centers = Center::orderBy('name')->get();
        $projects = Project::ordered()->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        $movementPlan->load('entries');
        $projectsManagerRoleConfigured = Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)->exists();
        $roleContext = new MovementPlan(['center_id' => $movementPlan->center_id, 'project_id' => $movementPlan->project_id]);
        $projectsManagerCount = $this->roleHoldersForPlan(Group::ROLE_PROJECTS_MANAGER, $roleContext)->count();

        return view('admin.movement-plans.form', compact('movementPlan', 'centers', 'projects', 'users', 'projectsManagerRoleConfigured', 'projectsManagerCount'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $employee = Employee::where('user_id', auth()->id())->first();

        $centerId = ($validated['center_id'] ?? null) ?: $employee?->center_id;
        $projectId = ($validated['project_id'] ?? null) ?: $employee?->project_id;
        $roleContext = new MovementPlan(['center_id' => $centerId, 'project_id' => $projectId]);
        $this->assertRoleConfigured(Group::ROLE_PROJECTS_MANAGER, $roleContext, 'role_assignment', 'مدير المشاريع');

        $plan = MovementPlan::create([
            'request_number' => $this->nextRequestNumber(),
            'created_by' => auth()->id(),
            'center_id' => $centerId,
            'project_id' => $projectId,
            'plan_month' => $validated['plan_month'],
            'notes' => $validated['notes'] ?? null,
            'refer_to_pm2_id' => null,
            'review_role_key' => Group::ROLE_PROJECTS_MANAGER,
            'status' => 'review',
        ]);

        $this->syncEntries($plan, $validated['entries']);

        $plan->logWorkflow('create', null, 'تم إنشاء خطة الحركة وإحالتها إلى دور مدير المشاريع', 'review');

        return redirect()->route('admin.movement-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة الحركة وإحالتها للمراجعة');
    }

    public function update(Request $request, MovementPlan $movementPlan)
    {
        $this->authorizeEditable($movementPlan);

        $validated = $this->validatePlan($request);

        $newReferral = $validated['refer_to_pm2_id'] ?? null;

        if (
            ! $movementPlan->review_role_key
            &&
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
            'entries.*.return_date' => 'required|date',
            'entries.*.departure_time' => 'nullable|date_format:H:i',
            'entries.*.return_time' => 'nullable|date_format:H:i',
            'entries.*.from_location' => 'nullable|string|max:255',
            'entries.*.to_location' => 'nullable|string|max:255',
            'entries.*.purpose' => 'required|string|max:2000',
            'entries.*.notes' => 'nullable|string|max:2000',
        ]);

        $validated['plan_month'] = $validated['plan_month'].'-01';

        foreach ($validated['entries'] as $i => $entry) {
            if ($entry['return_date'] < $entry['movement_date']) {
                throw ValidationException::withMessages([
                    "entries.$i.return_date" => 'تاريخ العودة يجب أن يكون في يوم الانطلاق أو بعده.',
                ]);
            }

            if (
                $entry['return_date'] === $entry['movement_date']
                &&
                ($entry['departure_time'] ?? null)
                && ($entry['return_time'] ?? null)
                && $entry['return_time'] <= $entry['departure_time']
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "entries.$i.return_time" => 'وقت العودة يجب أن يكون بعد وقت الانطلاق في اليوم نفسه.',
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
                'return_date' => $row['return_date'],
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
        if (! $this->canViewPlan($user, $movementPlan)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }

        $movementPlan->load([
            'creator', 'center', 'project', 'movementOfficer', 'projectsManager', 'assigner',
            'entries', 'recipients.user', 'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::where('type', 'employee')->orderBy('name')->get();
        $movementOfficers = $movementPlan->status === 'review'
            ? $this->movementOfficerCandidates($movementPlan)
            : collect();
        $movementOfficerRoleConfigured = Group::roles()->where('role_key', Group::ROLE_MOVEMENT_OFFICER)->exists();
        $isSuper = $user->type === 'super-admin';
        $isPm2 = $this->isRoleHolderForPlan($user, Group::ROLE_PROJECTS_MANAGER, $movementPlan, 'edit')
            || (int) $movementPlan->refer_to_pm2_id === (int) $user->id;
        $isOfficer = (int) $movementPlan->refer_to_movement_officer_id === (int) $user->id;
        $isCurrentHolder = $movementPlan->isCurrentRecipient($user->id);
        $canEdit = $isSuper || PermissionHelper::canAccessRecord($user, MovementPlan::class, 'edit', $movementPlan);
        $isRoleQueue = $movementPlan->status === 'review' && $movementPlan->review_role_key === Group::ROLE_PROJECTS_MANAGER;
        $referColumn = $movementPlan->status === 'review' ? $movementPlan->refer_to_pm2_id : $movementPlan->refer_to_movement_officer_id;
        $canReRefer = $canEdit && ! $isRoleQueue && (
            $isSuper || $isCurrentHolder || ($movementPlan->status === 'review' ? $isPm2 : $isOfficer) || $referColumn === null
        );

        return view('admin.movement-plans.show', compact(
            'movementPlan', 'users', 'movementOfficers', 'movementOfficerRoleConfigured', 'isSuper', 'isPm2', 'isOfficer', 'isCurrentHolder', 'canReRefer'
        ) + ['plan' => $movementPlan]);
    }

    public function approve(Request $request, MovementPlan $movementPlan)
    {
        $this->authorizeStep($movementPlan, 'pm2', 'review');

        $officers = $this->movementOfficerCandidates($movementPlan);
        if ($officers->isEmpty()) {
            $officerRoleConfigured = Group::roles()->where('role_key', Group::ROLE_MOVEMENT_OFFICER)->exists();
            throw ValidationException::withMessages([
                'movement_officer_id' => $movementPlan->review_role_key
                    ? ($officerRoleConfigured
                        ? 'لا يوجد مستخدم نشط ومخوّل في دور «مسؤول الحركة» لهذا المركز/المشروع.'
                        : 'دور «مسؤول الحركة» غير معرّف في الأدوار والنطاقات.')
                    : 'لا يوجد مستخدم متاح للإحالة إليه كمسؤول حركة.',
            ]);
        }

        $validated = $request->validate([
            'movement_officer_id' => ['required', 'integer', Rule::in($officers->pluck('id')->map(fn ($id) => (int) $id)->all())],
            'note' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($movementPlan, $validated) {
            $lockedPlan = MovementPlan::whereKey($movementPlan->id)->lockForUpdate()->firstOrFail();
            $this->authorizeStep($lockedPlan, 'pm2', 'review');
            $eligibleOfficers = $this->movementOfficerCandidates($lockedPlan)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! in_array((int) $validated['movement_officer_id'], $eligibleOfficers, true)) {
                throw ValidationException::withMessages(['movement_officer_id' => 'المستخدم المختار لم يعد مؤهلاً لدور مسؤول الحركة.']);
            }

            $lockedPlan->update([
                'refer_to_movement_officer_id' => (int) $validated['movement_officer_id'],
                'status' => 'approved',
            ]);
            $lockedPlan->completeReferral('pm2');
            $lockedPlan->referTo((int) $validated['movement_officer_id'], 'movement_officer', $validated['note'] ?? null);
            $lockedPlan->logWorkflow('approve', (int) $validated['movement_officer_id'], $validated['note'] ?? 'وافق مدير المشاريع وأحال الخطة لمسؤول الحركة', 'approved');
        });

        return back()->with('success', 'اعتُمدت خطة الحركة وأُحيلت لمسؤول الحركة.');
    }

    public function accept(MovementPlan $movementPlan)
    {
        abort_if($movementPlan->status !== 'approved', 403, 'الخطة ليست بانتظار استلام مسؤول الحركة.');
        $this->authorizeHolder($movementPlan, 'movement_officer');

        DB::transaction(function () use ($movementPlan) {
            $lockedPlan = MovementPlan::whereKey($movementPlan->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedPlan->status !== 'approved', 403, 'تم استلام الخطة أو تغيرت حالتها.');
            $this->authorizeHolder($lockedPlan, 'movement_officer');

            $lockedPlan->update(['status' => 'officer_accepted', 'officer_accepted_at' => now()]);
            $lockedPlan->completeReferral('movement_officer');
            $lockedPlan->logWorkflow('officer_accepted', null, 'استلم مسؤول الحركة الخطة ووافق على تنفيذها', 'officer_accepted');
        });

        return back()->with('success', 'تم استلام الخطة؛ يمكنك الآن توزيع المتابعة.');
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
        abort_if(! in_array($movementPlan->status, ['officer_accepted', 'assigned'], true), 403, 'يجب استلام الخطة قبل توزيع المتابِعين.');

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

        $movementPlan->completeReferral('movement_officer');
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

        if ($validated['step'] === 'pm2' && $movementPlan->review_role_key === Group::ROLE_PROJECTS_MANAGER) {
            return back()->with('error', 'الإحالة لهذه الخطوة تكون إلى دور مدير المشاريع، ولا تُعاد إلى مستخدم بعينه.');
        }

        if ($validated['step'] === 'movement_officer' && $movementPlan->review_role_key === Group::ROLE_PROJECTS_MANAGER) {
            $eligibleIds = $this->movementOfficerCandidates($movementPlan)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! in_array((int) $validated['to_user_id'], $eligibleIds, true)) {
                throw ValidationException::withMessages(['to_user_id' => 'يجب اختيار مستخدم مؤهل في دور مسؤول الحركة.']);
            }
        }

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

        if ($step === 'pm2' && $movementPlan->review_role_key) {
            abort_unless(
                $movementPlan->status === 'review'
                    && $this->isRoleHolderForPlan($user, $movementPlan->review_role_key, $movementPlan, 'edit'),
                403,
                'هذه الخطوة متاحة لحاملي دور مدير المشاريع فقط.'
            );

            return;
        }

        if (
            $step === 'pm2'
            && $movementPlan->status === 'review'
            && (int) $movementPlan->created_by === (int) $user->id
        ) {
            return;
        }

        $column = $step === 'pm2' ? 'refer_to_pm2_id' : 'refer_to_movement_officer_id';

        if ($step === 'movement_officer' && $movementPlan->review_role_key !== null) {
            abort_unless($movementPlan->{$column} !== null, 403, 'لم تُحل الخطة إلى مسؤول حركة محدد.');
        }

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

    public function destroy(MovementPlan $movementPlan)
    {
        $movementPlan->delete();

        return redirect()->route('admin.movement-plans.index')
            ->with('success', 'تم حذف خطة الحركة.');
    }

    private function nextRequestNumber(): string
    {
        $prefix = 'MOV-' . now()->year . '-';

        $last = (int) MovementPlan::where('request_number', 'like', $prefix . '%')
            ->pluck('request_number')
            ->map(fn (string $number): int => (int) substr($number, strlen($prefix)))
            ->max();

        do {
            $candidate = $prefix . str_pad((string) ++$last, 4, '0', STR_PAD_LEFT);
        } while (MovementPlan::where('request_number', $candidate)->exists());

        return $candidate;
    }
}
