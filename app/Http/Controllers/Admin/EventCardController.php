<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\EventCard;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class EventCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\EventCard,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\EventCard,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\EventCard,edit')->only(['edit', 'update', 'approve', 'reject', 'finalize', 'refer']);
        $this->middleware('permission:App\Models\Admin\EventCard,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = EventCard::with(['project', 'creator', 'referredUser']);

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('referred_user_id', $user->id)
                    ->orWhere('approved_by', $user->id)
                    ->orWhere('finalized_by', $user->id)
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $cards = $query->latest()->paginate(15)->withQueryString();
        $statuses = EventCard::STATUSES;

        return view('admin.event-cards.index', compact('cards', 'statuses'));
    }

    public function create(Request $request)
    {
        return view('admin.event-cards.form', [
            'card' => null,
            'projects' => $this->assignableProjects(),
            'users' => $this->selectableUsers(),
            'preselectedProject' => $request->integer('project_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCard($request);

        $card = new EventCard($validated['fields']);
        $card->status = 'review';
        $card->created_by = auth()->id();
        $card->save();

        $toUserId = $validated['referred_user_id'];
        $card->update(['referred_user_id' => $toUserId]);
        $card->referTo($toUserId, 'event_approve', $validated['note'] ?? null);
        $card->logWorkflow('created', $toUserId, $validated['note'] ?? null, 'review');

        return redirect()->route('admin.event-cards.show', $card)
            ->with('success', 'تمت إضافة بطاقة الفعالية وإحالتها للموافقة.');
    }

    public function show(EventCard $eventCard)
    {
        $this->authorizeView($eventCard);

        $eventCard->load([
            'project.centers', 'center', 'creator', 'referredUser',
            'approvedByUser', 'finalizedByUser',
            'workflowActions.fromUser', 'workflowActions.toUser',
            'activeReferrals.toUser',
        ]);

        return view('admin.event-cards.show', compact('eventCard'));
    }

    public function edit(EventCard $eventCard)
    {
        $this->authorizeView($eventCard);

        if ($eventCard->isLocked() && auth()->user()->type !== 'super-admin') {
            return redirect()->route('admin.event-cards.show', $eventCard)
                ->with('error', 'البطاقة مقفلة بعد الموافقة عليها ولا يمكن تعديلها.');
        }

        return view('admin.event-cards.form', [
            'card' => $eventCard,
            'projects' => $this->assignableProjects(),
            'users' => $this->selectableUsers(),
            'preselectedProject' => null,
        ]);
    }

    public function update(Request $request, EventCard $eventCard)
    {
        $this->authorizeView($eventCard);

        if ($eventCard->isLocked() && auth()->user()->type !== 'super-admin') {
            abort(403, 'البطاقة مقفلة ولا يمكن تعديلها.');
        }

        $validated = $this->validateCard($request);
        $eventCard->update($validated['fields']);

        // إعادة الإحالة عند تغيير الشخص أثناء المراجعة
        if ($eventCard->status === 'review') {
            $toUserId = $validated['referred_user_id'];
            $eventCard->update(['referred_user_id' => $toUserId]);
            $eventCard->referTo($toUserId, 'event_approve', $validated['note'] ?? null);
            $eventCard->logWorkflow('updated', $toUserId, $validated['note'] ?? null);
        } else {
            $eventCard->logWorkflow('updated');
        }

        return redirect()->route('admin.event-cards.show', $eventCard)
            ->with('success', 'تم تحديث بطاقة الفعالية.');
    }

    public function destroy(EventCard $eventCard)
    {
        if ($eventCard->status === 'finalized' && auth()->user()->type !== 'super-admin') {
            abort(403, 'لا يمكن حذف بطاقة معتمدة.');
        }

        $eventCard->cancelActiveReferrals();
        $eventCard->delete();

        return redirect()->route('admin.event-cards.index')
            ->with('success', 'تم حذف بطاقة الفعالية.');
    }

    /*
     * (1) المحال إليه — موافقة: تنتقل البطاقة لمدير المشاريع للاعتماد.
     */
    public function approve(Request $request, EventCard $eventCard)
    {
        $this->authorizeHolder($eventCard, 'event_approve', 'review');

        $eventCard->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $eventCard->completeReferral('event_approve');

        $finalizerId = $this->defaultProjectsManagerId();
        if ($finalizerId !== null) {
            $eventCard->update(['finalized_by' => null]);
            $eventCard->referTo($finalizerId, 'event_finalize', $request->note);
        }

        $eventCard->logWorkflow('approved', $finalizerId, $request->note, 'approved');

        return back()->with('success', 'تمت الموافقة على البطاقة — أُرسلت لمدير المشاريع للاعتماد النهائي.');
    }

    /*
     * (1) المحال إليه — رفض.
     */
    public function reject(Request $request, EventCard $eventCard)
    {
        $this->authorizeHolder($eventCard, 'event_approve', 'review');

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $eventCard->update(['status' => 'rejected', 'reason' => $validated['reason']]);
        $eventCard->cancelActiveReferrals();
        $eventCard->logWorkflow('rejected', null, $validated['reason'], 'rejected');

        return back()->with('error', 'رُفضت بطاقة الفعالية.');
    }

    /*
     * (2) مدير المشاريع — الاعتماد النهائي.
     */
    public function finalize(Request $request, EventCard $eventCard)
    {
        $this->authorizeHolder($eventCard, 'event_finalize', 'approved');

        $eventCard->update([
            'status' => 'finalized',
            'finalized_by' => auth()->id(),
            'finalized_at' => now(),
        ]);
        $eventCard->completeReferral('event_finalize');
        $eventCard->logWorkflow('finalized', null, $request->note, 'finalized');

        return back()->with('success', 'تم اعتماد بطاقة الفعالية.');
    }

    /*
     * إعادة الإحالة لمستلم آخر لنفس الخطوة.
     */
    public function refer(Request $request, EventCard $eventCard)
    {
        $validated = $request->validate([
            'step' => 'required|in:event_approve,event_finalize',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->authorizeHolder($eventCard, $validated['step']);

        $eventCard->referTo($validated['to_user_id'], $validated['step'], $validated['note'] ?? null);
        if ($validated['step'] === 'event_approve') {
            $eventCard->update(['referred_user_id' => $validated['to_user_id']]);
        }
        $eventCard->logWorkflow('referred', $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت الإحالة بنجاح.');
    }

    private function validateCard(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'center_id' => 'nullable|exists:centers,id',
            'event_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'presenter' => 'nullable|string|max:255',
            'expected_attendance' => 'nullable|integer|min:0',
            'objectives' => 'nullable|string',
            'schedule_place' => 'nullable|string|max:255',
            'schedule_date' => 'nullable|date',
            'schedule_time' => 'nullable|string|max:20',
            'tasks_projects' => 'nullable|string',
            'tasks_operations' => 'nullable|string',
            'tasks_mel' => 'nullable|string',
            'content_items' => 'nullable|array',
            'content_items.*.item' => 'nullable|string|max:255',
            'content_items.*.content' => 'nullable|string|max:2000',
            'content_items.*.responsible' => 'nullable|string|max:255',
            'content_items.*.duration' => 'nullable|string|max:50',
            'logistics_items' => 'nullable|array',
            'logistics_items.*.item' => 'nullable|string|max:255',
            'logistics_items.*.responsible' => 'nullable|string|max:255',
            'purchases_items' => 'nullable|array',
            'purchases_items.*.item' => 'nullable|string|max:255',
            'purchases_items.*.responsible' => 'nullable|string|max:255',
            'media_items' => 'nullable|array',
            'media_items.*.coverage' => 'nullable|string|max:255',
            'media_items.*.responsible' => 'nullable|string|max:255',
            'hr_notes' => 'nullable|string',
            'transport_items' => 'nullable|array',
            'transport_items.*.request' => 'nullable|string|max:255',
            'transport_items.*.type' => 'nullable|string|max:100',
            'transport_items.*.responsible' => 'nullable|string|max:255',
            'budget_items' => 'nullable|array',
            'budget_items.*.item' => 'nullable|string|max:255',
            'budget_items.*.description' => 'nullable|string|max:2000',
            'budget_items.*.cost' => 'nullable|numeric|min:0',
            'post_evaluation' => 'nullable|string',
            'referred_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $fields = collect($validated)->except(['referred_user_id', 'note'])->all();
        $fields['content_items'] = $this->cleanRows($fields['content_items'] ?? []);
        $fields['logistics_items'] = $this->cleanRows($fields['logistics_items'] ?? []);
        $fields['purchases_items'] = $this->cleanRows($fields['purchases_items'] ?? []);
        $fields['media_items'] = $this->cleanRows($fields['media_items'] ?? []);
        $fields['transport_items'] = $this->cleanRows($fields['transport_items'] ?? []);
        $fields['budget_items'] = $this->cleanRows($fields['budget_items'] ?? []);
        $fields['budget_total'] = collect($fields['budget_items'])->sum(fn ($r) => (float) ($r['cost'] ?? 0));

        return ['fields' => $fields, 'referred_user_id' => $validated['referred_user_id'], 'note' => $validated['note'] ?? null];
    }

    private function cleanRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => collect($row)->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()
            ->all();
    }

    private function assignableProjects()
    {
        $user = auth()->user();
        if ($user->type === 'super-admin') {
            return Project::orderBy('name')->get();
        }

        $scope = \App\Helpers\PermissionHelper::getEffectiveScope($user, Project::class);
        if ($scope['sees_all']) {
            return Project::orderBy('name')->get();
        }

        return Project::where(function ($q) use ($scope, $user) {
            $q->whereIn('id', $scope['project_ids']);
            $employee = \App\Models\Admin\Hr\Employee::where('user_id', $user->id)->first();
            if ($employee?->project_id) {
                $q->orWhere('id', $employee->project_id);
            }
        })->orderBy('name')->get();
    }

    private function selectableUsers()
    {
        return User::where('is_active', 1)
            ->whereIn('type', ['employee', 'super-admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }

    private function defaultProjectsManagerId(): ?int
    {
        return Permission::whereJsonContains('model_names', 'page:admin.projects-manager.dashboard')
            ->where('can_view', 1)
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');
    }

    private function authorizeView(EventCard $card): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin' || $card->isVisibleToUserId($user->id)) {
            return;
        }

        if (\App\Helpers\PermissionHelper::can($user, 'page:admin.projects-manager.dashboard', 'view')) {
            return;
        }

        abort(403);
    }

    private function authorizeHolder(EventCard $card, string $step, array|string|null $expectedStatuses = null): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return;
        }

        $isHolder = $card->isCurrentRecipient($user->id)
            || ($step === 'event_approve' && (int) $card->referred_user_id === (int) $user->id)
            || ($step === 'event_finalize' && \App\Helpers\PermissionHelper::can($user, 'page:admin.projects-manager.dashboard', 'view'));

        if (! $isHolder) {
            abort(403, 'هذه الخطوة ليست موجهة إليك.');
        }

        if ($expectedStatuses !== null && ! in_array($card->status, (array) $expectedStatuses, true)) {
            abort(403, 'حالة البطاقة لا تسمح بهذه الخطوة.');
        }
    }
}
