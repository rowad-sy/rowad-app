<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MediaPlanFormExport;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use App\Support\RoleHoldersLookup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MediaPlanController extends Controller
{
    public const ROWADUNA_PAGE = 'page:admin.rowaduna.dashboard';

    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MediaPlan,view')->only(['index', 'show', 'help', 'printForm', 'exportExcel']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,edit')->only([
            'edit', 'update', 'storeEvent', 'destroyEvent', 'addComment',
            'directManagerDecide', 'pm2Decide', 'reopenSimple', 'finalize', 'refer',
            'assignEvent', 'reporterDecide', 'publisherPreview', 'reviewerDecide', 'publishFinal', 'rescheduleEvent',
        ]);
        $this->middleware('permission:App\Models\Admin\MediaPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MediaPlan::with(['center', 'project', 'creator', 'events'])->withCount('events');

        if ($user->type !== 'super-admin') {
            $scope = PermissionHelper::getEffectiveScope($user, MediaPlan::class, 'can_view');

            if (! $scope['sees_all']) {
                $scopedIds = $this->scopedVisiblePlanIds($user, $scope);
                $projectsManagerIds = $this->projectsManagerPlanIds($user);

                $query->where(function ($q) use ($user, $scopedIds, $projectsManagerIds) {
                $q->where('created_by', $user->id)
                    ->orWhere('refer_to_direct_manager_id', $user->id)
                    ->orWhere('refer_to_pm2_id', $user->id)
                    ->orWhere('refer_to_rowaduna_id', $user->id)
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id))
                    ->orWhereHas('events', function ($e) use ($user) {
                        $e->where('refer_to_reporter_id', $user->id)
                            ->orWhere('refer_to_publisher_id', $user->id)
                            ->orWhere('refer_to_reviewer_id', $user->id);
                    })
                    ->orWhereIn('id', $scopedIds ?: [-1])
                    ->orWhereIn('id', $projectsManagerIds ?: [-1]);
                });
            }
        }

        if ($request->filled('month')) {
            $query->whereMonth('month_date', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('month_date', $request->year);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->orderBy('month_date', 'desc')->paginate(15)->withQueryString();
        $centers = Center::orderBy('name')->get();

        return view('admin.media-plans.index', compact('plans', 'centers'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::ordered()->get();
        $defaultCenterId = $this->employeeCenterId();
        $defaultProjectId = $this->employeeProjectId();

        return view('admin.media-plans.simple-form', compact('centers', 'projects', 'defaultCenterId', 'defaultProjectId'));
    }

    public function help()
    {
        return view('admin.media-plans.help');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSimplePlan($request);
        $centerId = $validated['center_id'] ?? $this->employeeCenterId();
        $projectId = $validated['project_id'] ?? $this->employeeProjectId();
        $context = new MediaPlan(['center_id' => $centerId, 'project_id' => $projectId, 'workflow_version' => 'simple']);
        if (! PermissionHelper::canAccessRecord(auth()->user(), MediaPlan::class, 'create', $context)) {
            throw ValidationException::withMessages(['center_id' => 'لا تملك صلاحية إنشاء خطة لهذا المركز أو المشروع.']);
        }
        $this->assertProjectsManagerConfigured($context);

        $plan = DB::transaction(function () use ($validated, $centerId, $projectId) {
            Center::whereKey($centerId)->lockForUpdate()->firstOrFail();
            $this->assertSimpleScheduleFree($centerId, $validated['events']);

            $plan = MediaPlan::create([
                'month_date' => $validated['month_date'],
                'center_id' => $centerId,
                'project_id' => $projectId,
                'created_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
                'workflow_version' => 'simple',
                'status' => 'simple_review',
            ]);

            $this->saveSimpleEvents($plan, $validated['events']);
            $plan->logWorkflow('create', null, 'أُنشئت الخطة وأُحيلت إلى دور مدير المشاريع', 'simple_review');

            return $plan;
        });

        return redirect()->route('admin.media-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الإعلامية بنجاح');
    }

    /** IDs visible through center/project view permissions. */
    private function scopedVisiblePlanIds(User $user, array $scope): array
    {
        $projectIds = array_map('intval', $scope['project_ids'] ?? []);
        $centerIds = array_map('intval', $scope['center_ids'] ?? []);
        if ($projectIds === [] && $centerIds === []) {
            return [];
        }

        return MediaPlan::query()
            ->where(function ($query) use ($projectIds, $centerIds) {
                if ($projectIds !== []) {
                    $query->whereIn('project_id', $projectIds);
                }
                if ($centerIds !== []) {
                    $query->{$projectIds === [] ? 'whereIn' : 'orWhereIn'}('center_id', $centerIds);
                }
            })
            ->get(['id', 'center_id', 'project_id'])
            ->filter(fn (MediaPlan $plan) => PermissionHelper::canAccessRecord($user, MediaPlan::class, 'view', $plan))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Simple-workflow plans visible to any eligible projects-manager role holder. */
    private function projectsManagerPlanIds(User $user): array
    {
        return MediaPlan::query()->where('workflow_version', 'simple')
            ->get(['id', 'center_id', 'project_id', 'workflow_version', 'status'])
            ->filter(fn (MediaPlan $plan) => $this->isProjectsManagerForPlan($user, $plan))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function projectsManagerCandidates(MediaPlan $plan)
    {
        $role = Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)->first();
        if (! $role) {
            return collect();
        }

        $eligibleIds = RoleHoldersLookup::userIds(MediaPlan::class, 'edit', $plan->project_id, $plan->center_id);

        return $role->users()->whereIn('users.id', $eligibleIds)
            ->where('users.type', 'employee')->where('users.is_active', true)
            ->orderBy('users.name')->get()
            ->filter(fn (User $user) => PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan))
            ->values();
    }

    private function eligibleRowadunaUsers(MediaPlan $plan)
    {
        $scope = [$plan->project_id, $plan->center_id];
        $rowadunaIds = RoleHoldersLookup::userIds(self::ROWADUNA_PAGE, 'view', ...$scope);
        $editIds = RoleHoldersLookup::userIds(MediaPlan::class, 'edit', ...$scope);
        $ids = array_values(array_intersect($rowadunaIds, $editIds));

        return User::whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (User $user) => PermissionHelper::canViewPage($user, 'admin.rowaduna.dashboard')
                && PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan))
            ->values();
    }

    private function eligibleReporters(MediaPlan $plan)
    {
        $ids = RoleHoldersLookup::userIds(MediaPlan::class, 'edit', $plan->project_id, $plan->center_id);

        return User::whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (User $user) => PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan))
            ->values();
    }

    private function isProjectsManagerForPlan(User $user, MediaPlan $plan): bool
    {
        if (! $plan->isSimple() || ! Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)
            ->whereHas('users', fn ($query) => $query->where('users.id', $user->id))->exists()) {
            return false;
        }

        return PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan);
    }

    private function canViewPlan(User $user, MediaPlan $plan): bool
    {
        return $user->type === 'super-admin'
            || $plan->isVisibleToUserId($user->id)
            || ($plan->isSimple() && $this->isProjectsManagerForPlan($user, $plan));
    }

    private function assertProjectsManagerConfigured(MediaPlan $plan): void
    {
        if (! Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)->exists()) {
            throw ValidationException::withMessages(['role_assignment' => 'دور مدير المشاريع غير معرّف في الأدوار والنطاقات.']);
        }

        if ($this->projectsManagerCandidates($plan)->isEmpty()) {
            throw ValidationException::withMessages(['role_assignment' => 'لا يوجد مدير مشاريع نشط ومخوّل لهذا المركز/المشروع.']);
        }
    }

    private function authorizeProjectsManager(MediaPlan $plan, array $statuses): void
    {
        abort_unless($this->isProjectsManagerForPlan(auth()->user(), $plan), 403, 'الإجراء متاح لحاملي دور مدير المشاريع المخولين ضمن نطاق الخطة فقط.');
        abort_unless(in_array($plan->status, $statuses, true), 403, 'حالة الخطة لا تسمح بهذا الإجراء.');
    }

    private function authorizeSimpleRowaduna(MediaPlan $plan, array $statuses): void
    {
        $user = auth()->user();
        abort_unless($plan->isSimple() && (int) $plan->refer_to_rowaduna_id === (int) $user->id, 403, 'الخطة ليست محالة إليك من روادنا.');
        abort_unless(PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan), 403, 'لا تملك صلاحية تعديل الخطة ضمن نطاقها.');
        abort_unless(in_array($plan->status, $statuses, true), 403, 'حالة الخطة لا تسمح بهذا الإجراء.');
    }

    private function authorizeSimpleCreatorEdit(MediaPlan $plan): void
    {
        abort_unless($plan->isSimple() && $plan->status === 'simple_review', 403, 'لا يمكن تعديل الفعاليات بعد اعتماد الخطة.');
        abort_unless(auth()->user()->type === 'super-admin' || (int) $plan->created_by === (int) auth()->id(), 403, 'تعديل الخطة متاح لمنشئها فقط قبل الاعتماد.');
        abort_unless(PermissionHelper::canAccessRecord(auth()->user(), MediaPlan::class, 'edit', $plan), 403, 'لا تملك صلاحية تعديل الخطة ضمن نطاقها.');
    }

    public function show(MediaPlan $plan)
    {
        $user = auth()->user();
        if (! $this->canViewPlan($user, $plan)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }

        if ($plan->isSimple()) {
            $plan->load([
                'center', 'project', 'creator', 'rowadunaUser', 'lockedByUser',
                'events.reporter', 'events.comments.user',
                'workflowActions.fromUser', 'workflowActions.toUser',
            ]);

            $isProjectsManager = $this->isProjectsManagerForPlan($user, $plan);
            $isRowadunaOwner = (int) $plan->refer_to_rowaduna_id === (int) $user->id
                && PermissionHelper::canAccessRecord($user, MediaPlan::class, 'edit', $plan);
            $reporters = $this->eligibleReporters($plan);
            $rowadunaUsers = $this->eligibleRowadunaUsers($plan);

            return view('admin.media-plans.show-simple', compact(
                'plan', 'isProjectsManager', 'isRowadunaOwner', 'reporters', 'rowadunaUsers'
            ));
        }

        $plan->load([
            'center', 'project', 'creator',
            'directManager', 'pm2User', 'rowadunaUser', 'mediaManager', 'mediaOfficer', 'lockedByUser',
            'events.responsible', 'events.executionUser',
            'events.reporter', 'events.publisher', 'events.reviewer',
            'events.previewReviewerUser', 'events.publishedByUser', 'events.comments.user',
            'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::orderBy('name')->get();

        $tentativePm2Id = $plan->refer_to_pm2_id ?? $this->defaultProjectsManagerId();
        $tentativeRowadunaId = $plan->refer_to_rowaduna_id ?? $this->defaultRowadunaId();

        return view('admin.media-plans.show', compact(
            'plan', 'users', 'tentativePm2Id', 'tentativeRowadunaId'
        ));
    }

    public function edit(MediaPlan $plan)
    {
        if ($plan->isSimple()) {
            abort_unless($plan->status === 'simple_review', 403, 'لا يمكن تعديل الخطة بعد اعتماد مدير المشاريع.');
            abort_unless((int) $plan->created_by === (int) auth()->id(), 403, 'تعديل الخطة متاح لمنشئها فقط قبل الاعتماد.');

            $plan->load('events');
            $centers = Center::orderBy('name')->get();
            $projects = Project::ordered()->get();

            return view('admin.media-plans.simple-form', compact('plan', 'centers', 'projects'));
        }

        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد أول موافقة — لا يمكن تعديلها');

        $plan->load('events');
        $centers = Center::orderBy('name')->get();
        $projects = Project::ordered()->get();
        $users = User::orderBy('name')->get();
        $creatorIsPm = $this->creatorIsProjectManager();

        return view('admin.media-plans.form', compact('plan', 'centers', 'projects', 'users', 'creatorIsPm'));
    }

    public function update(Request $request, MediaPlan $plan)
    {
        if ($plan->isSimple()) {
            abort_unless($plan->status === 'simple_review', 403, 'لا يمكن تعديل الخطة بعد اعتماد مدير المشاريع.');
            abort_unless((int) $plan->created_by === (int) auth()->id(), 403, 'تعديل الخطة متاح لمنشئها فقط قبل الاعتماد.');

            return $this->updateSimplePlan($request, $plan);
        }

        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد أول موافقة — لا يمكن تعديلها');
        $validated = $this->validatePlan($request);
        $events = $this->normalizeEvents($request->input('events', []));

        $this->assertBatchHasNoConflicts($events);

        try {
            DB::beginTransaction();

            $plan->update([
                'month_date' => $validated['month_date'],
                'center_id' => $validated['center_id'] ?? $plan->center_id ?? $this->employeeCenterId(),
                'project_id' => $validated['project_id'] ?? $plan->project_id ?? $this->employeeProjectId(),
                'note' => $validated['note'] ?? null,
            ]);

            $plan->events()->delete();
            $this->saveEvents($plan, $events);

            DB::commit();
        } catch (QueryException) {
            DB::rollBack();

            return back()->withInput()->with('error', 'لا يمكن تحديث الخطة — يوجد تعارض في مواعيد الفعاليات (نفس التاريخ والساعة).');
        }

        return redirect()->route('admin.media-plans.show', $plan)
            ->with('success', 'تم تحديث الخطة الإعلامية بنجاح');
    }

    public function destroy(MediaPlan $plan)
    {
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد أول موافقة — لا يمكن حذفها');
        if ($plan->isSimple() && auth()->user()->type !== 'super-admin') {
            abort_unless((int) $plan->created_by === (int) auth()->id(), 403, 'حذف الخطة متاح لمنشئها فقط قبل الاعتماد.');
            abort_unless(PermissionHelper::canAccessRecord(auth()->user(), MediaPlan::class, 'delete', $plan), 403, 'الخطة خارج نطاق صلاحية الحذف.');
        }

        $plan->delete();

        return redirect()->route('admin.media-plans.index')
            ->with('success', 'تم حذف الخطة الإعلامية بنجاح');
    }

    public function storeEvent(Request $request, MediaPlan $plan)
    {
        if ($plan->isSimple()) {
            $this->authorizeSimpleCreatorEdit($plan);
            $validated = $this->validateSimpleEvent($request);

            DB::transaction(function () use ($plan, $validated) {
                Center::whereKey($plan->center_id)->lockForUpdate()->firstOrFail();
                $this->assertSimpleScheduleFree($plan->center_id, [$validated]);
                $this->saveSimpleEvent($plan, $validated);
            });

            return back()->with('success', 'أُضيفت الفعالية إلى الخطة.');
        }

        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة — لا يمكن إضافة فعاليات');

        $validated = $this->validateEvent($request);

        $exists = $plan->events()
            ->where('event_date', $validated['event_date'])
            ->where('event_time', $validated['event_time'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'يوجد تعارض — توجد فعالية أخرى بنفس التاريخ والساعة في هذه الخطة.');
        }

        $plan->events()->create($validated);

        return back()->with('success', 'تمت إضافة الفعالية بنجاح');
    }

    public function destroyEvent(MediaPlanEvent $event)
    {
        if ($event->plan->isSimple()) {
            $this->authorizeSimpleCreatorEdit($event->plan);
            abort_if($event->plan->events()->count() <= 1, 403, 'يجب أن تحتوي الخطة على فعالية واحدة على الأقل.');
            $event->delete();

            return back()->with('success', 'حُذفت الفعالية من الخطة.');
        }

        abort_if($event->plan->isLocked(), 403, 'الخطة مُقفلة — لا يمكن حذف الفعالية');

        $event->delete();

        return back()->with('success', 'تم حذف الفعالية بنجاح');
    }

    public function addComment(Request $request, MediaPlanEvent $event)
    {
        abort_if($event->plan->isSimple(), 404, 'التعليقات غير مفعّلة في المسار المبسّط حالياً.');

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $event->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'تم إضافة التعليق على الموعد بنجاح');
    }

    /*
     * (1) المدير المباشر — يوافق ثم تُقفل الخطة وتُحال لمدير المشاريع.
     * حصرية صارمة: المحال إليه فقط، حتى السوبر ادمن.
     */
    public function directManagerDecide(Request $request, MediaPlan $plan)
    {
        abort_if($plan->isSimple(), 404, 'الخطة المبسّطة لا تمر بالمدير المباشر.');
        $this->authorizePlanHolder($plan, 'direct_manager', ['review']);

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_pm2_id' => ['required_if:decision,approve', 'exists:users,id', function ($attr, $value, $fail) {
                if ((int) $value === (int) auth()->id()) {
                    $fail('لا يمكن أن تكون الإحالة إلى نفسك — اختر مدير المشاريع.');
                }
            }],
        ]);

        if ($validated['decision'] === 'reject') {
            return $this->rejectPlan($plan, 'direct_manager', $validated['note'] ?? null, 'المدير المباشر');
        }

        $plan->update([
            'status' => 'manager_approved',
            'refer_to_pm2_id' => $validated['refer_to_pm2_id'],
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);

        $plan->completeReferral('direct_manager');
        $plan->referTo($validated['refer_to_pm2_id'], 'pm2', $validated['note'] ?? null);
        $plan->logWorkflow('manager_approved', $validated['refer_to_pm2_id'], $validated['note'] ?? null, 'manager_approved');

        return back()->with('success', 'وافق المدير المباشر — أُقفلت الخطة وأُحيلت لمدير المشاريع.');
    }

    /*
     * (2) مدير المشاريع — يوافق وتُحال الخطة لمسؤول روادنا.
     */
    public function pm2Decide(Request $request, MediaPlan $plan)
    {
        if ($plan->isSimple()) {
            return $this->simpleProjectsManagerDecide($request, $plan);
        }

        $this->authorizePlanHolder($plan, 'pm2', ['pm2_review', 'manager_approved']);

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_rowaduna_id' => ['required_if:decision,approve', 'exists:users,id', function ($attr, $value, $fail) {
                if ((int) $value === (int) auth()->id()) {
                    $fail('لا يمكن أن تكون الإحالة إلى نفسك — اختر مسؤول روادنا.');
                }
            }],
        ]);

        if ($validated['decision'] === 'reject') {
            return $this->rejectPlan($plan, 'pm2', $validated['note'] ?? null, 'مدير المشاريع');
        }

        $updates = [
            'status' => 'rowaduna_review',
            'refer_to_rowaduna_id' => $validated['refer_to_rowaduna_id'],
        ];

        // في مسار «مدير المشروع يُنشئ مباشرة»: موافقة مدير المشاريع هي قفل الخطة.
        if ($plan->status === 'pm2_review' && $plan->locked_at === null) {
            $updates['locked_at'] = now();
            $updates['locked_by'] = auth()->id();
        }

        $plan->update($updates);

        $plan->completeReferral('pm2');
        $plan->referTo($validated['refer_to_rowaduna_id'], 'rowaduna', $validated['note'] ?? null);
        $plan->logWorkflow('pm2_approved', $validated['refer_to_rowaduna_id'], $validated['note'] ?? null, 'rowaduna_review');

        return back()->with('success', 'وافق مدير المشاريع — أُحيلت الخطة لمسؤول روادنا لإسناد التغطيات.');
    }

    private function simpleProjectsManagerDecide(Request $request, MediaPlan $plan)
    {
        $this->authorizeProjectsManager($plan, ['simple_review']);
        $rowadunaIds = $this->eligibleRowadunaUsers($plan)->modelKeys();

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_rowaduna_id' => [
                'nullable', 'integer',
                Rule::in(array_map('intval', $rowadunaIds)),
            ],
            'reason' => 'required_if:decision,reject|nullable|string|max:255',
        ], [
            'refer_to_rowaduna_id.in' => 'اختر مستخدماً نشطاً مخولاً لصفحة روادنا ولإدارة الخطة.',
            'reason.required_if' => 'أدخل سبب الرفض.',
        ]);

        $rowadunaId = $request->filled('refer_to_rowaduna_id')
            ? (int) $validated['refer_to_rowaduna_id']
            : null;

        DB::transaction(function () use ($plan, $validated, $rowadunaId) {
            $lockedPlan = MediaPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $this->authorizeProjectsManager($lockedPlan, ['simple_review']);
            if ($rowadunaId !== null && ! $this->eligibleRowadunaUsers($lockedPlan)->contains('id', $rowadunaId)) {
                throw ValidationException::withMessages(['refer_to_rowaduna_id' => 'لم يعد المستخدم المختار مخولاً لإدارة الخطة من روادنا.']);
            }

            if ($validated['decision'] === 'reject') {
                $lockedPlan->update([
                    'status' => 'simple_rejected',
                    'reason' => $validated['reason'],
                    'locked_at' => now(),
                    'locked_by' => auth()->id(),
                ]);
                $lockedPlan->cancelActiveReferrals();
                $lockedPlan->logWorkflow('rejected', null, $validated['reason'], 'simple_rejected');

                return;
            }

            $lockedPlan->update([
                'status' => $rowadunaId === null ? 'simple_completed' : 'simple_rowaduna',
                'refer_to_rowaduna_id' => $rowadunaId,
                'reason' => null,
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'approved_at' => $rowadunaId === null ? now() : null,
            ]);

            if ($rowadunaId !== null) {
                $lockedPlan->referTo($rowadunaId, 'rowaduna', $validated['note'] ?? null);
            }

            $lockedPlan->logWorkflow(
                'pm2_approved',
                $rowadunaId,
                $validated['note'] ?? ($rowadunaId === null ? 'اعتماد وإنهاء دون إحالة إلى روادنا' : 'اعتماد وإحالة إلى روادنا'),
                $rowadunaId === null ? 'simple_completed' : 'simple_rowaduna',
            );
        });

        return back()->with('success', $rowadunaId === null
            ? 'اعتُمدت الخطة وأُغلقت. يمكنك إعادة فتحها وإحالتها إلى روادنا لاحقاً.'
            : 'اعتُمدت الخطة وأُحيلت إلى مسؤول روادنا.');
    }

    public function reopenSimple(Request $request, MediaPlan $plan)
    {
        abort_unless($plan->isSimple() && $plan->status === 'simple_completed', 403, 'لا يمكن إعادة فتح هذه الخطة في حالتها الحالية.');
        abort_unless($plan->refer_to_rowaduna_id === null, 403, 'إعادة الفتح متاحة للخطة التي أُنهيت دون إحالتها إلى روادنا.');
        $this->authorizeProjectsManager($plan, ['simple_completed']);

        $rowadunaIds = array_map('intval', $this->eligibleRowadunaUsers($plan)->modelKeys());
        $validated = $request->validate([
            'refer_to_rowaduna_id' => ['required', 'integer', Rule::in($rowadunaIds)],
            'note' => 'nullable|string|max:2000',
        ], [
            'refer_to_rowaduna_id.required' => 'اختر مسؤول روادنا لإعادة فتح الخطة وإحالتها.',
            'refer_to_rowaduna_id.in' => 'المستخدم المختار غير مخول لإدارة هذه الخطة من روادنا.',
        ]);

        DB::transaction(function () use ($plan, $validated) {
            $lockedPlan = MediaPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $this->authorizeProjectsManager($lockedPlan, ['simple_completed']);
            if (! $this->eligibleRowadunaUsers($lockedPlan)->contains('id', (int) $validated['refer_to_rowaduna_id'])) {
                throw ValidationException::withMessages(['refer_to_rowaduna_id' => 'لم يعد المستخدم المختار مخولاً لإدارة الخطة من روادنا.']);
            }
            $lockedPlan->update([
                'status' => 'simple_rowaduna',
                'refer_to_rowaduna_id' => (int) $validated['refer_to_rowaduna_id'],
                'approved_at' => null,
            ]);
            $lockedPlan->referTo((int) $validated['refer_to_rowaduna_id'], 'rowaduna', $validated['note'] ?? null);
            $lockedPlan->logWorkflow('reopened', (int) $validated['refer_to_rowaduna_id'], $validated['note'] ?? 'أُعيد فتح الخطة وأُحيلت إلى روادنا', 'simple_rowaduna');
        });

        return back()->with('success', 'أُعيد فتح الخطة وأُحيلت إلى مسؤول روادنا دون السماح بتعديل بياناتها.');
    }

    /*
     * (3) مسؤول روادنا — يُسند فعالية إلى مراسل (مع فحص تعارض المواعيد).
     */
    public function assignEvent(Request $request, MediaPlanEvent $event)
    {
        $plan = $event->plan;

        if ($plan->isSimple()) {
            return $this->assignSimpleEvent($request, $event, $plan);
        }

        $this->authorizeRowadunaHolder($plan, ['rowaduna_review', 'in_progress', 'pm2_approved', 'media_manager_approved']);

        $validated = $request->validate([
            'reporter_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->assertReporterSlotFree($event, (int) $validated['reporter_user_id'], $event->event_date, $event->event_time);

        $wasPending = $plan->status === 'rowaduna_review';

        $event->update([
            'coverage_status' => 'assigned',
            'refer_to_reporter_id' => $validated['reporter_user_id'],
            'not_covered_reason' => null,
        ]);

        if ($wasPending) {
            $plan->update(['status' => 'in_progress']);
            $plan->completeReferral('rowaduna');
        }

        $event->referTo((int) $validated['reporter_user_id'], 'reporter', $validated['note'] ?? null);
        $plan->logWorkflow('event_assigned', (int) $validated['reporter_user_id'],
            'إسناد فعالية «' . $event->event_name . '» للمراسل' . ($validated['note'] ?? ''));

        return back()->with('success', 'أُسندت الفعالية إلى المراسل بنجاح.');
    }

    private function assignSimpleEvent(Request $request, MediaPlanEvent $event, MediaPlan $plan)
    {
        $this->authorizeSimpleRowaduna($plan, ['simple_rowaduna', 'simple_in_progress']);
        abort_unless(in_array($event->coverage_status, ['pending', 'assigned'], true), 403, 'لا يمكن تغيير المراسل بعد تسجيل نتيجة التغطية.');

        $reporterIds = array_map('intval', $this->eligibleReporters($plan)->modelKeys());
        $validated = $request->validate([
            'reporter_user_id' => ['required', 'integer', Rule::in($reporterIds)],
            'note' => 'nullable|string|max:2000',
        ], [
            'reporter_user_id.in' => 'المراسل المختار غير نشط أو لا يملك صلاحية العمل على الخطط الإعلامية ضمن هذا النطاق.',
        ]);
        if (! $this->eligibleReporters($plan)->contains('id', (int) $validated['reporter_user_id'])) {
            throw ValidationException::withMessages(['reporter_user_id' => 'لم يعد المراسل المختار مؤهلاً ضمن نطاق الخطة.']);
        }

        $event->update([
            'coverage_status' => 'assigned',
            'refer_to_reporter_id' => (int) $validated['reporter_user_id'],
            'not_covered_reason' => null,
            'coverage_note' => null,
        ]);
        $plan->update(['status' => 'simple_in_progress']);
        $event->referTo((int) $validated['reporter_user_id'], 'reporter', $validated['note'] ?? null);
        $plan->logWorkflow('event_assigned', (int) $validated['reporter_user_id'], 'أُسندت فعالية «' . $event->event_name . '» إلى المراسل.');

        return back()->with('success', 'أُسندت الفعالية إلى المراسل.');
    }

    /*
     * (4) المراسل — تمت التغطية (+ رابط المواد على جوجل درايف + اختيار المونتير)
     * أو لم تتم (+ السبب، وتبقى قابلة لإعادة الجدولة).
     */
    public function reporterDecide(Request $request, MediaPlanEvent $event)
    {
        if ($event->plan->isSimple()) {
            return $this->reporterDecideSimple($request, $event);
        }

        $this->authorizeEventHolder($event, 'reporter', 'assigned');

        $validated = $request->validate([
            'decision' => 'required|in:covered,not_covered',
            'media_items_url' => 'required_if:decision,covered|nullable|url|max:2000',
            'coverage_note' => 'nullable|string|max:2000',
            'publisher_user_id' => 'required_if:decision,covered|nullable|exists:users,id',
            'not_covered_reason' => 'required_if:decision,not_covered|nullable|string|max:2000',
        ]);

        $plan = $event->plan;

        if ($validated['decision'] === 'not_covered') {
            $event->update([
                'coverage_status' => 'not_covered',
                'not_covered_reason' => $validated['not_covered_reason'],
                'coverage_note' => $validated['coverage_note'] ?? null,
                'execution_status' => 'not_executed',
                'execution_by' => auth()->id(),
                'execution_at' => now(),
            ]);

            $event->completeReferral('reporter');
            $plan->logWorkflow('not_covered', null, 'لم تتم تغطية «' . $event->event_name . '»: ' . $validated['not_covered_reason']);

            $this->autoClosePlan($plan);

            return back()->with('success', 'سُجِّلت حالة «لم تتم التغطية» — يمكن لمدير المشروع إعادة جدولتها لموعد آخر.');
        }

        $event->update([
            'coverage_status' => 'covered',
            'media_items_url' => $validated['media_items_url'],
            'coverage_note' => $validated['coverage_note'] ?? null,
            'publish_status' => 'to_publish',
            'refer_to_publisher_id' => $validated['publisher_user_id'],
            'execution_status' => 'executed',
            'execution_by' => auth()->id(),
            'execution_at' => now(),
        ]);

        $event->completeReferral('reporter');
        $event->referTo((int) $validated['publisher_user_id'], 'publisher', $validated['coverage_note'] ?? null);
        $plan->logWorkflow('covered', (int) $validated['publisher_user_id'],
            'تمت تغطية «' . $event->event_name . '' . '» ورفع المواد، وأُحيلت للمونتير/الناشر');

        return back()->with('success', 'سُجِّلت التغطية ورُفعت روابط المواد — أُحيلت للمونتير/الناشر للنشر المؤقت.');
    }

    private function reporterDecideSimple(Request $request, MediaPlanEvent $event)
    {
        $plan = $event->plan;
        $this->authorizeEventHolder($event, 'reporter', 'assigned');
        abort_unless($plan->status === 'simple_in_progress', 403, 'الخطة ليست في مرحلة تسجيل التغطية.');

        $validated = $request->validate([
            'decision' => 'required|in:covered,not_covered',
            'coverage_note' => 'nullable|string|max:2000',
            'not_covered_reason' => 'required_if:decision,not_covered|nullable|string|max:2000',
        ], [
            'not_covered_reason.required_if' => 'اكتب سبب عدم التغطية.',
        ]);

        $covered = $validated['decision'] === 'covered';
        $event->update([
            'coverage_status' => $covered ? 'covered' : 'not_covered',
            'coverage_note' => $validated['coverage_note'] ?? null,
            'not_covered_reason' => $covered ? null : $validated['not_covered_reason'],
            'execution_status' => $covered ? 'executed' : 'not_executed',
            'execution_by' => auth()->id(),
            'execution_at' => now(),
        ]);
        $event->completeReferral('reporter');
        $plan->logWorkflow(
            $covered ? 'covered' : 'not_covered',
            null,
            $covered
                ? 'سُجلت تغطية فعالية «' . $event->event_name . '».'
                : 'لم تتم تغطية فعالية «' . $event->event_name . '»: ' . $validated['not_covered_reason'],
            'simple_in_progress',
        );

        return back()->with('success', $covered ? 'سُجلت الفعالية على أنها تمت تغطيتها.' : 'سُجل سبب عدم التغطية.');
    }

    /*
     * (5) المونتير/الناشر — نشر مؤقت: يضع رابط المعاينة ويُحيل لمدير المشروع للمراجعة.
     */
    public function publisherPreview(Request $request, MediaPlanEvent $event)
    {
        abort_if($event->plan->isSimple(), 404, 'النشر المؤقت غير موجود في المسار المبسّط.');
        $this->authorizeEventHolder($event, 'publisher', ['to_publish', 'rework']);

        $validated = $request->validate([
            'preview_url' => 'required|url|max:2000',
            'refer_to_reviewer_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $plan = $event->plan;

        $event->update([
            'publish_status' => 'to_review',
            'preview_url' => $validated['preview_url'],
            'refer_to_reviewer_id' => $validated['refer_to_reviewer_id'],
        ]);

        $event->completeReferral('publisher');
        $event->referTo((int) $validated['refer_to_reviewer_id'], 'preview_review', $validated['note'] ?? null);
        $plan->logWorkflow('preview_published', (int) $validated['refer_to_reviewer_id'],
            'نشر مؤقت لـ «' . $event->event_name . '» بانتظار مراجعة المالك');

        return back()->with('success', 'رُفع رابط النشر المؤقت — بانتظار مراجعة مدير المشروع.');
    }

    /*
     * (6) مدير المشروع — مراجعة رابط المعاينة: موافقة للنشر الدائم أو إعادة مع ملاحظات.
     */
    public function reviewerDecide(Request $request, MediaPlanEvent $event)
    {
        abort_if($event->plan->isSimple(), 404, 'مراجعة المعاينة غير موجودة في المسار المبسّط.');
        $this->authorizeEventHolder($event, 'preview_review', 'to_review');

        $validated = $request->validate([
            'decision' => 'required|in:approve,return',
            'preview_feedback' => 'required_if:decision,return|nullable|string|max:2000',
        ]);

        $plan = $event->plan;
        $publisherId = $event->refer_to_publisher_id;

        if ($publisherId === null) {
            return back()->with('error', 'لا يوجد ناشر محال إليه لهذه الفعالية — أعد النشر من خطوة المونتير.');
        }

        if ($validated['decision'] === 'return') {
            $event->update([
                'publish_status' => 'rework',
                'preview_feedback' => $validated['preview_feedback'],
            ]);

            $event->completeReferral('preview_review');
            $event->referTo((int) $publisherId, 'publisher', $validated['preview_feedback']);
            $plan->logWorkflow('preview_returned', (int) $publisherId, 'ملاحظات على معاينة «' . $event->event_name . '»: ' . $validated['preview_feedback']);

            return back()->with('success', 'أُعيدت الفعالية للناشر مع الملاحظات.');
        }

        $event->update([
            'publish_status' => 'to_final',
            'preview_feedback' => null,
            'preview_reviewed_by' => auth()->id(),
            'preview_reviewed_at' => now(),
        ]);

        $event->completeReferral('preview_review');
        $event->referTo((int) $publisherId, 'publisher', 'موافق على المعاينة — انشر نهائياً');
        $plan->logWorkflow('preview_approved', (int) $publisherId, 'اعتماد معاينة «' . $event->event_name . '» — بانتظار النشر الدائم');

        return back()->with('success', 'اعتمدت المعاينة — أُحيلت للناشر لإدخال روابط النشر الدائم.');
    }

    /*
     * (7) المونتير/الناشر — النشر الدائم: روابط المنصات (فيسبوك/إنستغرام/يوتيوب/...).
     */
    public function publishFinal(Request $request, MediaPlanEvent $event)
    {
        abort_if($event->plan->isSimple(), 404, 'النشر النهائي غير موجود في المسار المبسّط.');
        $this->authorizeEventHolder($event, 'publisher', 'to_final');

        $validated = $request->validate([
            'platforms' => 'required|array|min:1',
            'platforms.*.platform' => 'required|in:' . implode(',', array_keys(MediaPlanEvent::PLATFORMS)),
            'platforms.*.url' => 'required|url|max:2000',
        ]);

        $links = collect($validated['platforms'])
            ->filter(fn ($l) => ! empty($l['url']))
            ->values()
            ->all();

        if ($links === []) {
            return back()->with('error', 'أدخل رابطاً واحداً على الأقل للمنصات.');
        }

        $plan = $event->plan;

        $event->update([
            'publish_status' => 'published',
            'publish_links' => $links,
            'published_by' => auth()->id(),
            'published_at' => now(),
        ]);

        $event->completeReferral('publisher');
        $plan->logWorkflow('published', null, 'نُشر «' . $event->event_name . '» نهائياً على: '
            . implode('، ', array_map(fn ($l) => MediaPlanEvent::PLATFORMS[$l['platform']] ?? $l['platform'], $links)));

        $this->autoClosePlan($plan);

        return back()->with('success', 'تم النشر الدائم وتوثيق الروابط.');
    }

    /*
     * (بديل) إعادة جدولة فعالية لم تتم تغطيتها — لمدير المشروع (صاحب الخطة) أو مسؤول روادنا.
     */
    public function rescheduleEvent(Request $request, MediaPlanEvent $event)
    {
        $plan = $event->plan;
        abort_if($plan->isSimple(), 404, 'إعادة الجدولة غير موجودة في المسار المبسّط حالياً.');
        $user = auth()->user();

        $allowed = in_array((int) $user->id, array_filter([
            (int) $plan->refer_to_direct_manager_id,
            (int) $plan->created_by,
            (int) $plan->refer_to_rowaduna_id,
        ]), true);

        abort_if(! $allowed, 403, 'إعادة الجدولة لمدير المشروع أو مسؤول روادنا فقط.');
        abort_if($event->coverage_status !== 'not_covered', 403, 'تُعاد الجدولة فقط للفعاليات التي لم تتم تغطيتها.');
        abort_if(in_array($plan->status, ['executed', 'rejected'], true), 403, 'الخطة مغلقة — لا يمكن إعادة الجدولة.');

        $validated = $request->validate([
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'reporter_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $dup = $plan->events()
            ->where('id', '!=', $event->id)
            ->where('event_date', $validated['event_date'])
            ->where('event_time', $validated['event_time'])
            ->exists();

        if ($dup) {
            return back()->with('error', 'يوجد تعارض — توجد فعالية أخرى بنفس التاريخ والساعة في هذه الخطة.');
        }

        $this->assertReporterSlotFree(null, (int) $validated['reporter_user_id'], $validated['event_date'], $validated['event_time'], $event->id);

        $event->update([
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'day' => Carbon::parse($validated['event_date'])->locale('ar')->translatedFormat('l'),
            'coverage_status' => 'assigned',
            'refer_to_reporter_id' => $validated['reporter_user_id'],
            'not_covered_reason' => null,
            'media_items_url' => null,
            'publish_status' => 'none',
            'preview_url' => null,
            'preview_feedback' => null,
            'publish_links' => null,
        ]);

        $event->cancelActiveReferrals('reporter');
        $event->referTo((int) $validated['reporter_user_id'], 'reporter', $validated['note'] ?? null);
        $plan->logWorkflow('rescheduled', (int) $validated['reporter_user_id'],
            'أُعيدت جدولة «' . $event->event_name . '» إلى ' . $validated['event_date'] . ' ' . $validated['event_time']);

        return back()->with('success', 'أُعيدت جدولة الفعالية وأُرسلت للمراسل في الموعد الجديد.');
    }

    /*
     * إغلاق الخطة من مسؤول روادنا — يدوياً بعد إنجاز أو تعذر كل الفعاليات.
     */
    public function finalize(Request $request, MediaPlan $plan)
    {
        if ($plan->isSimple()) {
            return $this->finalizeSimple($request, $plan);
        }

        $this->authorizeRowadunaHolder($plan, ['in_progress', 'rowaduna_review']);

        $validated = $request->validate(['force' => 'nullable|boolean']);

        if (! $plan->allEventsClosed() && ! $request->boolean('force')) {
            $open = $plan->events()->get()->reject(fn ($e) => $e->isClosed())->count();

            return back()->with('error', 'بقيت ' . $open . ' فعالية غير مكتملة — أكمل التغطيات والنشر أو استخدم «إغلاق إجباري» بملاحظة.');
        }

        $plan->update(['status' => 'executed', 'approved_at' => now()]);
        $plan->cancelActiveReferrals();
        $plan->logWorkflow('executed', null, $request->note ?? 'أُنجزت الخطة الإعلامية', 'executed');

        return back()->with('success', 'أُغلقت الخطة الإعلامية كمنجزة.');
    }

    private function finalizeSimple(Request $request, MediaPlan $plan)
    {
        $this->authorizeSimpleRowaduna($plan, ['simple_in_progress']);
        abort_if($plan->events()->count() === 0, 403, 'لا يمكن إغلاق خطة بلا فعاليات.');

        $open = $plan->events()->whereNotIn('coverage_status', ['covered', 'not_covered'])->count();
        if ($open > 0) {
            return back()->with('error', 'لا يمكن إغلاق الخطة قبل تسجيل نتيجة التغطية لجميع الفعاليات.');
        }

        DB::transaction(function () use ($plan, $request) {
            $lockedPlan = MediaPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $this->authorizeSimpleRowaduna($lockedPlan, ['simple_in_progress']);
            $openCount = $lockedPlan->events()->whereNotIn('coverage_status', ['covered', 'not_covered'])->count();
            abort_if($openCount > 0, 403, 'هناك فعاليات لم تسجل نتيجتها بعد.');

            $lockedPlan->update(['status' => 'simple_completed', 'approved_at' => now()]);
            $lockedPlan->completeReferral('rowaduna');
            $lockedPlan->cancelActiveReferrals('reporter');
            $lockedPlan->logWorkflow('executed', null, $request->note ?: 'أُغلقت الخطة من مسؤول روادنا بعد تسجيل نتائج جميع الفعاليات.', 'simple_completed');
        });

        return back()->with('success', 'أُغلقت الخطة بعد تسجيل نتائج جميع الفعاليات.');
    }

    /*
     * إعادة إحالة على مستوى الخطة — الحامل الحالي للخطوة فقط.
     */
    public function refer(Request $request, MediaPlan $plan)
    {
        abort_if($plan->isSimple(), 404, 'إعادة الإحالة الفردية غير مستخدمة في المسار المبسّط.');

        $validated = $request->validate([
            'step' => 'required|in:direct_manager,pm2,rowaduna',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->authorizePlanHolder($plan, $validated['step']);

        $column = self::STEP_COLUMNS[$validated['step']];
        $plan->update([$column => $validated['to_user_id']]);
        $plan->referTo((int) $validated['to_user_id'], $validated['step'], $validated['note'] ?? null);
        $plan->logWorkflow('referred', (int) $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت إحالة الخطة الإعلامية بنجاح.');
    }

    /* ------------------------------------------------------------ print/export */

    public function printForm(Request $request, MediaPlan $plan)
    {
        $this->ensureVisible($plan);

        $theme = $request->get('theme') === 'rowaduna' ? 'rowaduna' : 'rowad';

        if ($plan->isSimple()) {
            return view('admin.media-plans.print-simple', [
                'plan' => $plan->load(['center', 'project', 'creator', 'rowadunaUser', 'events.reporter']),
                'theme' => $theme,
            ]);
        }

        return view('admin.media-plans.print', [
            'plan' => $plan->load(['center', 'project', 'creator', 'events.reporter', 'events.publisher', 'events.comments']),
            'theme' => $theme,
        ]);
    }

    public function exportExcel(Request $request, MediaPlan $plan)
    {
        $this->ensureVisible($plan);

        $theme = $request->get('theme') === 'rowaduna' ? 'rowaduna' : 'rowad';

        $filename = 'media-plan-' . $plan->month_date->format('Y-m') . ($theme === 'rowaduna' ? '-rowaduna' : '') . '.xlsx';

        return Excel::download(new MediaPlanFormExport($plan->load(['center', 'project', 'creator', 'events.reporter', 'events.publisher']), $theme), $filename);
    }

    /* ------------------------------------------------------------ guards & helpers */

    public const STEP_COLUMNS = [
        'direct_manager' => 'refer_to_direct_manager_id',
        'pm2' => 'refer_to_pm2_id',
        'rowaduna' => 'refer_to_rowaduna_id',
    ];

    /*
     * حصرية صارمة على مستوى الخطة — المحال إليه فقط، حتى السوبر ادمن لا يعتمد.
     */
    private function authorizePlanHolder(MediaPlan $plan, string $step, array|string|null $expectedStatuses = null): void
    {
        $user = auth()->user();
        $column = self::STEP_COLUMNS[$step];

        $isHolder = $plan->isCurrentRecipient($user->id)
            || (int) $plan->{$column} === (int) $user->id;

        // إحالات الخطوات القديمة (بيانات قبل ترقية الدورة) لا تُعطي عهدة للخطوة الجديدة.
        abort_if(! $isHolder, 403, 'هذه الخطوة موجهة لشخص آخر — أنت لست صاحبها الحالي.');

        if ($expectedStatuses !== null && ! in_array($plan->status, (array) $expectedStatuses, true)) {
            abort(403, 'حالة الخطة لا تسمح بهذه الخطوة.');
        }
    }

    /*
     * حصرية صارمة لمسؤول روادنا (عهدة العمود أو إحالة نشطة على الخطوة).
     */
    private function authorizeRowadunaHolder(MediaPlan $plan, array $statuses): void
    {
        $this->authorizePlanHolder($plan, 'rowaduna', $statuses);
    }

    /*
     * حصرية صارمة على مستوى الفعالية (مراسل/ناشر/مراجع).
     */
    private function authorizeEventHolder(MediaPlanEvent $event, string $step, array|string $expectedState): void
    {
        $user = auth()->user();

        $column = match ($step) {
            'reporter' => 'refer_to_reporter_id',
            'publisher' => 'refer_to_publisher_id',
            'preview_review' => 'refer_to_reviewer_id',
        };

        $state = match ($step) {
            'reporter' => $event->coverage_status,
            'publisher', 'preview_review' => $event->publish_status,
        };

        $isHolder = $event->isCurrentRecipient($user->id)
            || (int) $event->{$column} === (int) $user->id;

        abort_if(! $isHolder, 403, 'هذه الخطوة موجهة لشخص آخر — أنت لست صاحبها الحالي.');
        abort_if(! in_array($state, (array) $expectedState, true), 403, 'حالة الفعالية لا تسمح بهذه الخطوة الآن.');

        $plan = $event->plan;
        abort_if(in_array($plan->status, ['rejected'], true), 403, 'الخطة مرفوضة.');
    }

    /*
     * منع إسناد أكثر من تغطية لنفس المراسل في نفس التاريخ والساعة (عبر كل الخطط).
     */
    private function assertReporterSlotFree(?MediaPlanEvent $event, int $reporterId, $date, $time, ?int $exceptId = null): void
    {
        $exceptId = $exceptId ?? $event?->id;

        $conflict = MediaPlanEvent::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('refer_to_reporter_id', $reporterId)
            ->where('event_date', $date)
            ->where('event_time', $time)
            ->where('coverage_status', '!=', 'not_covered')
            ->whereHas('plan', fn ($q) => $q->whereNotIn('status', ['rejected']))
            ->with('plan')
            ->first();

        if ($conflict) {
            $planMonth = $conflict->plan->month_date->locale('ar')->translatedFormat('F Y');

            throw ValidationException::withMessages([
                'reporter_user_id' => 'لا يمكن إسناد التغطية لنفس المراسل في نفس الوقت: لديه فعالية «'
                    . $conflict->event_name . '» في نفس الموعد (خطة ' . $planMonth . ').',
            ]);
        }
    }

    private function autoClosePlan(MediaPlan $plan): void
    {
        if ($plan->status === 'in_progress' && $plan->allEventsPublished()) {
            $plan->update(['status' => 'executed', 'approved_at' => now()]);
            $plan->logWorkflow('executed', null, 'اكتملت كل فعاليات الخطة — أُغلقت تلقائياً', 'executed');
        }
    }

    private function rejectPlan(MediaPlan $plan, string $step, ?string $note, string $label)
    {
        $plan->update(['status' => 'rejected', 'reason' => $note]);
        $plan->completeReferral($step);
        $plan->cancelActiveReferrals();
        $plan->logWorkflow('rejected', null, $note, 'rejected');

        return back()->with('error', 'رُفضت الخطة الإعلامية من ' . $label . '.');
    }

    private function ensureVisible(MediaPlan $plan): void
    {
        $user = auth()->user();
        if (! $this->canViewPlan($user, $plan)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }
    }

    private function updateSimplePlan(Request $request, MediaPlan $plan)
    {
        $validated = $this->validateSimplePlan($request);
        $centerId = $validated['center_id'] ?? $plan->center_id ?? $this->employeeCenterId();
        $projectId = $validated['project_id'] ?? $plan->project_id ?? $this->employeeProjectId();
        if (! $centerId) {
            throw ValidationException::withMessages(['center_id' => 'اختر مركزاً للخطة حتى يمكن فحص تعارض المواعيد.']);
        }

        $context = new MediaPlan(['center_id' => $centerId, 'project_id' => $projectId, 'workflow_version' => 'simple']);
        if (! PermissionHelper::canAccessRecord(auth()->user(), MediaPlan::class, 'edit', $context)) {
            throw ValidationException::withMessages(['center_id' => 'لا تملك صلاحية تعديل الخطة ضمن المركز أو المشروع المحدد.']);
        }
        $this->assertProjectsManagerConfigured($context);

        DB::transaction(function () use ($plan, $validated, $centerId, $projectId) {
            Center::whereKey($centerId)->lockForUpdate()->firstOrFail();
            $this->assertSimpleScheduleFree($centerId, $validated['events'], $plan->id);

            $plan->update([
                'month_date' => $validated['month_date'],
                'center_id' => $centerId,
                'project_id' => $projectId,
                'note' => $validated['note'] ?? null,
            ]);
            $plan->events()->delete();
            $this->saveSimpleEvents($plan, $validated['events']);
        });

        return redirect()->route('admin.media-plans.show', $plan)->with('success', 'تم تحديث الخطة وفعالياتها.');
    }

    private function validateSimplePlan(Request $request): array
    {
        $validated = $request->validate([
            'month_date' => 'required|date_format:Y-m',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'note' => 'nullable|string|max:2000',
            'events' => 'required|array|min:1',
            'events.*.event_date' => 'required|date',
            'events.*.event_time' => 'required|date_format:H:i',
            'events.*.event_end_time' => 'required|date_format:H:i',
            'events.*.event_name' => 'required|string|max:255',
            'events.*.location' => 'nullable|string|max:255',
            'events.*.coverage_type' => 'nullable|string|max:255',
            'events.*.notes' => 'nullable|string|max:2000',
        ], [
            'events.required' => 'أضف فعالية واحدة على الأقل.',
            'events.min' => 'أضف فعالية واحدة على الأقل.',
            'events.*.event_end_time.required' => 'أدخل وقت نهاية الفعالية.',
        ]);

        foreach ($validated['events'] as $index => $event) {
            if ($event['event_end_time'] <= $event['event_time']) {
                throw ValidationException::withMessages([
                    "events.$index.event_end_time" => 'وقت النهاية يجب أن يكون بعد وقت البداية في اليوم نفسه.',
                ]);
            }
        }
        $validated['events'] = array_values($validated['events']);
        $validated['month_date'] .= '-01';

        $centerId = $validated['center_id'] ?? $this->employeeCenterId();
        if (! $centerId) {
            throw ValidationException::withMessages(['center_id' => 'اختر مركزاً للخطة حتى يمكن فحص تعارض المواعيد.']);
        }

        return $validated;
    }

    private function validateSimpleEvent(Request $request): array
    {
        $event = $request->validate([
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'event_end_time' => 'required|date_format:H:i',
            'event_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'coverage_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($event['event_end_time'] <= $event['event_time']) {
            throw ValidationException::withMessages(['event_end_time' => 'وقت النهاية يجب أن يكون بعد وقت البداية في اليوم نفسه.']);
        }

        return $event;
    }

    /** Prevent intersecting reservations in a center, across all projects. */
    private function assertSimpleScheduleFree(int $centerId, array $events, ?int $excludePlanId = null): void
    {
        $previousEvents = [];
        foreach ($events as $index => $event) {
            foreach ($previousEvents as $previous) {
                if ($event['event_date'] === $previous['event_date']
                    && $event['event_time'] < $previous['event_end_time']
                    && $event['event_end_time'] > $previous['event_time']) {
                    throw ValidationException::withMessages([
                        "events.$index.event_time" => 'يتداخل موعد هذه الفعالية مع فعالية أخرى في الخطة نفسها.',
                    ]);
                }
            }
            $previousEvents[] = $event;

            $conflict = MediaPlanEvent::query()
                ->whereDate('event_date', $event['event_date'])
                ->whereHas('plan', function ($query) use ($centerId, $excludePlanId) {
                    $query->where('center_id', $centerId)
                        ->whereNotIn('status', ['rejected', 'simple_rejected'])
                        ->when($excludePlanId, fn ($q) => $q->where('id', '!=', $excludePlanId));
                })
                ->where(function ($query) use ($event) {
                    // Legacy entries have no known duration; reserve their recorded instant.
                    $query->where(function ($legacy) use ($event) {
                        $legacy->whereNull('event_end_time')
                            ->where('event_time', '>=', $event['event_time'])
                            ->where('event_time', '<', $event['event_end_time']);
                    })->orWhere(function ($interval) use ($event) {
                        $interval->whereNotNull('event_end_time')
                            ->where('event_time', '<', $event['event_end_time'])
                            ->where('event_end_time', '>', $event['event_time']);
                    });
                })
                ->with('plan.project')
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    "events.$index.event_time" => 'يتعارض الموعد مع فعالية «' . $conflict->event_name . '» في الخطة '
                        . $conflict->plan->month_date->locale('ar')->translatedFormat('F Y')
                        . ($conflict->plan->project ? ' لمشروع «' . $conflict->plan->project->name . '»' : '')
                        . ' في المركز نفسه.',
                ]);
            }
        }
    }

    private function saveSimpleEvents(MediaPlan $plan, array $events): void
    {
        foreach ($events as $event) {
            $this->saveSimpleEvent($plan, $event);
        }
    }

    private function saveSimpleEvent(MediaPlan $plan, array $event): MediaPlanEvent
    {
        return $plan->events()->create([
            'event_date' => $event['event_date'],
            'event_time' => $event['event_time'],
            'event_end_time' => $event['event_end_time'],
            'day' => Carbon::parse($event['event_date'])->locale('ar')->translatedFormat('l'),
            'event_name' => $event['event_name'],
            'location' => $event['location'] ?? null,
            'coverage_type' => $event['coverage_type'] ?? null,
            'notes' => $event['notes'] ?? null,
        ]);
    }

    private function creatorIsProjectManager(): bool
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return false;
        }

        return PermissionHelper::can($user, 'page:admin.project-manager.dashboard', 'view');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'month_date' => 'required|date',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'note' => 'nullable|string|max:2000',
            'refer_to_direct_manager_id' => 'nullable|exists:users,id',
            'refer_to_pm2_id' => 'nullable|exists:users,id',
            'skip_direct_manager' => 'nullable|boolean',
        ]);
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'event_date' => 'required|date',
            'office' => 'nullable|string|max:255',
            'event_name' => 'required|string|max:255',
            'day' => 'nullable|string|max:20',
            'event_time' => 'required|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'responsible_user_id' => 'nullable|exists:users,id',
            'summary' => 'nullable|string|max:2000',
            'coverage_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function normalizeEvents(array $events): array
    {
        return array_values(array_filter($events, function ($ev) {
            return isset($ev['event_date']) && $ev['event_date'] !== '' && isset($ev['event_time']) && $ev['event_time'] !== '';
        }));
    }

    private function assertBatchHasNoConflicts(array $events): void
    {
        $seen = [];
        $messages = [];

        foreach ($events as $index => $ev) {
            $key = $ev['event_date'] . '|' . $ev['event_time'];

            if (isset($seen[$key])) {
                $messages[] = 'الفعالية "' . ($ev['event_name'] ?? '') . '" في السطر ' . ($index + 1)
                    . ' تتعارض مع السطر ' . ($seen[$key] + 1) . ' — نفس التاريخ والساعة.';
            } else {
                $seen[$key] = $index;
            }
        }

        if ($messages) {
            throw ValidationException::withMessages(['events' => $messages]);
        }
    }

    private function saveEvents(MediaPlan $plan, array $events): void
    {
        foreach ($events as $ev) {
            $plan->events()->create([
                'event_date' => $ev['event_date'],
                'office' => $ev['office'] ?? null,
                'event_name' => $ev['event_name'],
                'day' => $ev['day'] ?? Carbon::parse($ev['event_date'])->locale('ar')->translatedFormat('l'),
                'event_time' => $ev['event_time'],
                'location' => $ev['location'] ?? null,
                'responsible_user_id' => $ev['responsible_user_id'] ?? null,
                'summary' => $ev['summary'] ?? null,
                'coverage_type' => $ev['coverage_type'] ?? null,
                'notes' => $ev['notes'] ?? null,
            ]);
        }
    }

    private function defaultDirectManagerId(?int $projectId): ?int
    {
        return RoleHoldersLookup::first('page:admin.project-manager.dashboard', 'view', $projectId)
            ?? Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
                ->where('can_view', 1)
                ->when($projectId, fn ($q) => $q->where(fn ($s) => $s->whereNull('project_id')->orWhere('project_id', $projectId)))
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->value('user_id');
    }

    private function defaultProjectsManagerId(): ?int
    {
        return RoleHoldersLookup::first('page:admin.projects-manager.dashboard');
    }

    private function defaultRowadunaId(): ?int
    {
        return RoleHoldersLookup::first(self::ROWADUNA_PAGE);
    }

    private function employeeCenterId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('center_id');
    }

    private function employeeProjectId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('project_id');
    }
}
