<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MediaPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MediaPlan,view')->only(['index', 'show', 'help']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,edit')->only(['edit', 'update', 'storeEvent', 'destroyEvent', 'addComment', 'directManagerDecide', 'pm2Decide', 'mediaManagerDecide', 'markEvent', 'finalize', 'refer']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MediaPlan::with(['center', 'project', 'creator', 'events'])->withCount('events');

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('refer_to_direct_manager_id', $user->id)
                    ->orWhere('refer_to_pm2_id', $user->id)
                    ->orWhere('refer_to_media_manager_id', $user->id)
                    ->orWhere('refer_to_media_officer_id', $user->id)
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
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

        $plans = $query->orderBy('month_date', 'desc')->paginate(15);
        $centers = Center::orderBy('name')->get();

        return view('admin.media-plans.index', compact('plans', 'centers'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.media-plans.form', compact('centers', 'projects', 'users'));
    }

    public function help()
    {
        return view('admin.media-plans.help');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $events = $this->normalizeEvents($request->input('events', []));

        $this->assertBatchHasNoConflicts($events);

        try {
            DB::beginTransaction();

            $plan = MediaPlan::create([
                'month_date' => $validated['month_date'],
                'center_id' => $validated['center_id'] ?? $this->employeeCenterId(),
                'project_id' => $validated['project_id'] ?? $this->employeeProjectId(),
                'created_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
                'refer_to_direct_manager_id' => $this->defaultDirectManagerId($validated['project_id'] ?? $this->employeeProjectId()),
                'refer_to_media_officer_id' => $this->defaultMediaOfficerId($validated['center_id'] ?? $this->employeeCenterId()),
                'status' => 'review',
            ]);

            $this->saveEvents($plan, $events);

            DB::commit();

            $directId = $plan->refer_to_direct_manager_id;
            $plan->logWorkflow('create', $directId, 'تم إنشاء الخطة الإعلامية وإحالتها للمدير المباشر', 'review');

            if ($directId !== null) {
                $plan->referTo($directId, 'direct_manager');
            }
        } catch (QueryException) {
            DB::rollBack();

            return back()->withInput()->with('error', 'لا يمكن حفظ الخطة — يوجد تعارض في مواعيد الفعاليات (نفس التاريخ والساعة).');
        }

        return redirect()->route('admin.media-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الإعلامية بنجاح');
    }

    public function show(MediaPlan $plan)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $plan->isVisibleToUserId($user->id)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }

        $plan->load([
            'center', 'project', 'creator',
            'directManager', 'pm2User', 'mediaManager', 'mediaOfficer', 'lockedByUser',
            'events.responsible', 'events.executionUser', 'events.comments.user',
            'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::orderBy('name')->get();

        $tentativeDirectorId = $plan->refer_to_direct_manager_id ?? $this->defaultDirectManagerId($plan->project_id);
        $tentativePm2Id = $plan->refer_to_pm2_id ?? $this->defaultProjectsManagerId();
        $tentativeOfficerId = $plan->refer_to_media_officer_id ?? $this->defaultMediaOfficerId($plan->center_id);

        return view('admin.media-plans.show', compact(
            'plan', 'users', 'tentativeDirectorId', 'tentativePm2Id', 'tentativeOfficerId'
        ));
    }

    public function edit(MediaPlan $plan)
    {
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد موافقة المدير المباشر — لا يمكن تعديلها');

        $plan->load('events');
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.media-plans.form', compact('plan', 'centers', 'projects', 'users'));
    }

    public function update(Request $request, MediaPlan $plan)
    {
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد موافقة المدير المباشر — لا يمكن تعديلها');
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
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد موافقة المدير المباشر — لا يمكن حذفها');

        $plan->delete();

        return redirect()->route('admin.media-plans.index')
            ->with('success', 'تم حذف الخطة الإعلامية بنجاح');
    }

    public function storeEvent(Request $request, MediaPlan $plan)
    {
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة — لا يمكن إضافة فعاليات');

        $validated = $this->validateEvent($request);

        $exists = $plan->events()
            ->where('event_date', $validated['event_date'])
            ->where('event_time', $validated['event_time'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'يوجد تعارض — توجد فعالية أخرى بنفس التاريخ والساعة في هذه الخطة.');
        }

        $plan->events()->create([
            'event_date' => $validated['event_date'],
            'office' => $validated['office'] ?? null,
            'event_name' => $validated['event_name'],
            'day' => $validated['day'] ?? Carbon::parse($validated['event_date'])->locale('ar')->translatedFormat('l'),
            'event_time' => $validated['event_time'],
            'location' => $validated['location'] ?? null,
            'responsible_user_id' => $validated['responsible_user_id'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'coverage_type' => $validated['coverage_type'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'تمت إضافة الفعالية بنجاح');
    }

    public function destroyEvent(MediaPlanEvent $event)
    {
        abort_if($event->plan->isLocked(), 403, 'الخطة مُقفلة — لا يمكن حذف الفعالية');

        $event->delete();

        return back()->with('success', 'تم حذف الفعالية بنجاح');
    }

    public function addComment(Request $request, MediaPlanEvent $event)
    {
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
     * (1) المدير المباشر — يوافق على الخطة ثم تُقفل وتُحال لمدير المشاريع.
     */
    public function directManagerDecide(Request $request, MediaPlan $plan)
    {
        $this->authorizeHolder($plan, 'direct_manager', 'review');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_pm2_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $plan->update(['status' => 'rejected', 'reason' => $validated['note'] ?? null]);
            $plan->completeReferral('direct_manager');
            $plan->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'رُفضت الخطة الإعلامية من المدير المباشر.');
        }

        $plan->update([
            'status' => 'manager_approved',
            'refer_to_pm2_id' => $validated['refer_to_pm2_id'],
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);

        $plan->completeReferral('direct_manager');
        $this->pushReferral($plan, 'pm2', $validated['refer_to_pm2_id'], $validated['note'] ?? null);
        $plan->logWorkflow('manager_approved', $validated['refer_to_pm2_id'], $validated['note'] ?? null, 'manager_approved');

        return back()->with('success', 'وافق المدير المباشر — أُقفلت الخطة وأُحيلت لمدير المشاريع.');
    }

    /*
     * (2) مدير المشاريع — يوافق ويُحال لمدير الإعلام.
     */
    public function pm2Decide(Request $request, MediaPlan $plan)
    {
        $this->authorizeHolder($plan, 'pm2', 'manager_approved');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_media_manager_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $plan->update(['status' => 'rejected', 'reason' => $validated['note'] ?? null]);
            $plan->completeReferral('pm2');
            $plan->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'رُفضت الخطة الإعلامية من مدير المشاريع.');
        }

        $plan->update([
            'status' => 'pm2_approved',
            'refer_to_media_manager_id' => $validated['refer_to_media_manager_id'],
        ]);

        $plan->completeReferral('pm2');
        $this->pushReferral($plan, 'media_manager', $validated['refer_to_media_manager_id'], $validated['note'] ?? null);
        $plan->logWorkflow('pm2_approved', $validated['refer_to_media_manager_id'], $validated['note'] ?? null, 'pm2_approved');

        return back()->with('success', 'وافق مدير المشاريع — أُحيلت الخطة لمدير الإعلام.');
    }

    /*
     * (3) مدير الإعلام — يوافق ويُحال للمسؤول الإعلامي في مركز الخطة.
     */
    public function mediaManagerDecide(Request $request, MediaPlan $plan)
    {
        $this->authorizeHolder($plan, 'media_manager', 'pm2_approved');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_media_officer_id' => 'required_if:decision,approve|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $plan->update(['status' => 'rejected', 'reason' => $validated['note'] ?? null]);
            $plan->completeReferral('media_manager');
            $plan->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'رُفضت الخطة الإعلامية من مدير الإعلام.');
        }

        $plan->update([
            'status' => 'media_manager_approved',
            'refer_to_media_officer_id' => $validated['refer_to_media_officer_id'],
        ]);

        $plan->completeReferral('media_manager');
        $this->pushReferral($plan, 'media_officer', $validated['refer_to_media_officer_id'], $validated['note'] ?? null);
        $plan->logWorkflow('media_manager_approved', $validated['refer_to_media_officer_id'], $validated['note'] ?? null, 'media_manager_approved');

        return back()->with('success', 'وافق مدير الإعلام — أُحيلت الخطة للمسؤول الإعلامي في المركز.');
    }

    /*
     * (4) المسؤول الإعلامي — يحدد على كل فعالية: نُفِّذت / لم تُنفَّذ + ملاحظات.
     */
    public function markEvent(Request $request, MediaPlanEvent $event)
    {
        $plan = $event->plan;
        $this->authorizeHolder($plan, 'media_officer', ['media_manager_approved', 'executing']);

        $validated = $request->validate([
            'execution_status' => 'required|in:executed,not_executed',
            'execution_note' => 'nullable|string|max:2000',
        ]);

        $event->update([
            'execution_status' => $validated['execution_status'],
            'execution_note' => $validated['execution_note'] ?? null,
            'execution_by' => auth()->id(),
            'execution_at' => now(),
        ]);

        if ($plan->status === 'media_manager_approved') {
            $plan->update(['status' => 'executing']);
        }

        return back()->with('success', 'حُدِّث تنفيذ الفعالية.');
    }

    /*
     * إغلاق الخطة يدوياً بعد وضع علامات الفعاليات — تُصبح منجزة.
     */
    public function finalize(Request $request, MediaPlan $plan)
    {
        $this->authorizeHolder($plan, 'media_officer', ['media_manager_approved', 'executing']);

        $plan->update([
            'status' => 'executed',
            'approved_at' => now(),
        ]);

        $plan->completeReferral('media_officer');
        $plan->logWorkflow('executed', null, $request->note ?? 'أُنجزت الخطة الإعلامية', 'executed');

        return back()->with('success', 'أُغلقت الخطة الإعلامية كمنجزة.');
    }

    /*
     * إعادة الإحالة — المستلَم الحالي للخطوة يعيد إحالتها لشخص آخر.
     */
    public function refer(Request $request, MediaPlan $plan)
    {
        $validated = $request->validate([
            'step' => 'required|in:direct_manager,pm2,media_manager,media_officer',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->authorizeHolder($plan, $validated['step']);

        $this->pushReferral($plan, $validated['step'], $validated['to_user_id'], $validated['note'] ?? null);
        $plan->logWorkflow('referred', $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت إحالة الخطة الإعلامية بنجاح.');
    }

    public const STEP_COLUMNS = [
        'direct_manager' => 'refer_to_direct_manager_id',
        'pm2' => 'refer_to_pm2_id',
        'media_manager' => 'refer_to_media_manager_id',
        'media_officer' => 'refer_to_media_officer_id',
    ];

    private function authorizeHolder(MediaPlan $plan, string $step, array|string|null $expectedStatuses = null): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return;
        }

        $column = self::STEP_COLUMNS[$step] ?? null;

        // لا يوجد مستلَم معيّن بعد (بيانات قديمة) → يبقى التصرف متاحاً.
        if ($column !== null && $plan->{$column} === null) {
            return;
        }

        $isHolder = $plan->isCurrentRecipient($user->id)
            || ($column !== null && (int) $plan->{$column} === (int) $user->id);

        if (! $isHolder) {
            abort(403, 'هذه الخطوة ليست موجهة إليك.');
        }

        if ($expectedStatuses !== null && ! in_array($plan->status, (array) $expectedStatuses, true)) {
            abort(403, 'حالة الخطة لا تسمح بهذه الخطوة.');
        }
    }

    private function pushReferral(MediaPlan $plan, string $step, ?int $toUserId, ?string $note): void
    {
        $column = self::STEP_COLUMNS[$step] ?? null;

        if ($column !== null) {
            $plan->update([$column => $toUserId]);
        }

        if ($toUserId !== null) {
            $plan->referTo($toUserId, $step, $note);
        }
    }

    private function defaultDirectManagerId(?int $projectId): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
            ->where('can_view', 1)
            ->when($projectId, fn ($q) => $q->where(fn ($s) => $s->whereNull('project_id')->orWhere('project_id', $projectId)))
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    private function defaultProjectsManagerId(): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.project-manager.dashboard')
            ->where('can_view', 1)
            ->whereNull('center_id')
            ->whereNull('project_id')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    private function defaultMediaOfficerId(?int $centerId): ?int
    {
        return Permission::whereJsonContains('model_names', 'App\Models\Admin\MediaPlan')
            ->where('can_edit', 1)
            ->where('can_view', 1)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'month_date' => 'required|date',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'note' => 'nullable|string|max:2000',
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

    private function employeeCenterId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('center_id');
    }

    private function employeeProjectId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('project_id');
    }
}