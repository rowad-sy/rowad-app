<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MediaPlanFormExport;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
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
            'directManagerDecide', 'pm2Decide', 'finalize', 'refer',
            'assignEvent', 'reporterDecide', 'publisherPreview', 'reviewerDecide', 'publishFinal', 'rescheduleEvent',
        ]);
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
                    ->orWhere('refer_to_rowaduna_id', $user->id)
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id))
                    ->orWhereHas('events', function ($e) use ($user) {
                        $e->where('refer_to_reporter_id', $user->id)
                            ->orWhere('refer_to_publisher_id', $user->id)
                            ->orWhere('refer_to_reviewer_id', $user->id);
                    });
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
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $creatorIsPm = $this->creatorIsProjectManager();

        return view('admin.media-plans.form', compact('centers', 'projects', 'users', 'creatorIsPm'));
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

        $projectId = $validated['project_id'] ?? $this->employeeProjectId();
        $skipDirect = $this->creatorIsProjectManager() || $request->boolean('skip_direct_manager');

        $directorId = $skipDirect ? null : ($validated['refer_to_direct_manager_id'] ?? $this->defaultDirectManagerId($projectId));

        if (! $skipDirect) {
            if ($directorId === null || (int) $directorId === (int) auth()->id()) {
                throw ValidationException::withMessages([
                    'refer_to_direct_manager_id' => 'اختر المدير المباشر (لا يمكن أن تكون نفسك)، أو فعّل «أنا مدير المشروع — إرسال مباشر لمدير المشاريع».',
                ]);
            }
        }

        $pm2Id = $skipDirect
            ? ($validated['refer_to_pm2_id'] ?? $this->defaultProjectsManagerId())
            : null;

        if ($skipDirect && ($pm2Id === null || (int) $pm2Id === (int) auth()->id())) {
            throw ValidationException::withMessages(['refer_to_pm2_id' => 'اختر مدير المشاريع للإحالة إليه مباشرة.']);
        }

        try {
            DB::beginTransaction();

            $plan = MediaPlan::create([
                'month_date' => $validated['month_date'],
                'center_id' => $validated['center_id'] ?? $this->employeeCenterId(),
                'project_id' => $projectId,
                'created_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
                'refer_to_direct_manager_id' => $directorId,
                'refer_to_rowaduna_id' => $this->defaultRowadunaId(),
                'status' => $skipDirect ? 'pm2_review' : 'review',
            ]);

            $this->saveEvents($plan, $events);

            DB::commit();
        } catch (QueryException) {
            DB::rollBack();

            return back()->withInput()->with('error', 'لا يمكن حفظ الخطة — يوجد تعارض في مواعيد الفعاليات (نفس التاريخ والساعة).');
        }

        if ($skipDirect) {
            $plan->update(['refer_to_pm2_id' => $pm2Id]);
            $plan->logWorkflow('create', $pm2Id, 'أنشأ مدير المشروع الخطة وأرسلها مباشرة إلى مدير المشاريع', 'pm2_review');
            $plan->referTo($pm2Id, 'pm2');
        } else {
            $plan->logWorkflow('create', $directorId, 'تم إنشاء الخطة الإعلامية وإحالتها للمدير المباشر', 'review');
            $plan->referTo($directorId, 'direct_manager');
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
        abort_if($plan->isLocked(), 403, 'الخطة مُقفلة بعد أول موافقة — لا يمكن تعديلها');

        $plan->load('events');
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $creatorIsPm = $this->creatorIsProjectManager();

        return view('admin.media-plans.form', compact('plan', 'centers', 'projects', 'users', 'creatorIsPm'));
    }

    public function update(Request $request, MediaPlan $plan)
    {
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

        $plan->events()->create($validated);

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
     * (1) المدير المباشر — يوافق ثم تُقفل الخطة وتُحال لمدير المشاريع.
     * حصرية صارمة: المحال إليه فقط، حتى السوبر ادمن.
     */
    public function directManagerDecide(Request $request, MediaPlan $plan)
    {
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

    /*
     * (3) مسؤول روادنا — يُسند فعالية إلى مراسل (مع فحص تعارض المواعيد).
     */
    public function assignEvent(Request $request, MediaPlanEvent $event)
    {
        $plan = $event->plan;

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

    /*
     * (4) المراسل — تمت التغطية (+ رابط المواد على جوجل درايف + اختيار المونتير)
     * أو لم تتم (+ السبب، وتبقى قابلة لإعادة الجدولة).
     */
    public function reporterDecide(Request $request, MediaPlanEvent $event)
    {
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

    /*
     * (5) المونتير/الناشر — نشر مؤقت: يضع رابط المعاينة ويُحيل لمدير المشروع للمراجعة.
     */
    public function publisherPreview(Request $request, MediaPlanEvent $event)
    {
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

    /*
     * إعادة إحالة على مستوى الخطة — الحامل الحالي للخطوة فقط.
     */
    public function refer(Request $request, MediaPlan $plan)
    {
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
        if ($user->type !== 'super-admin' && ! $plan->isVisibleToUserId($user->id)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }
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
