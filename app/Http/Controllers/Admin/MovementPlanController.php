<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class MovementPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MovementPlan,view')->only(['index', 'show', 'help']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,edit')->only(['approve', 'reject', 'assign', 'complete', 'refer']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MovementPlan::with(['center', 'project', 'creator', 'movementOfficer'])
            ->withCount('recipients');

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

        $plans = $query->orderBy('movement_date', 'desc')->paginate(15)->withQueryString();
        $centers = Center::orderBy('name')->get();

        return view('admin.movement-plans.index', compact('plans', 'centers'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.movement-plans.form', compact('centers', 'projects', 'users'));
    }

    public function help()
    {
        return view('admin.movement-plans.help');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'movement_date' => 'required|date',
            'departure_time' => 'nullable|date_format:H:i',
            'return_time' => 'nullable|date_format:H:i',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'purpose' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $employee = Employee::where('user_id', auth()->id())->first();

        if (
            ($validated['departure_time'] ?? null)
            && ($validated['return_time'] ?? null)
            && $validated['return_time'] < $validated['departure_time']
        ) {
            return back()->withInput()->withErrors(['return_time' => 'وقت العودة يجب أن يكون بعد وقت الانطلاق.']);
        }

        $plan = MovementPlan::create([
            'request_number' => $this->nextRequestNumber(),
            'created_by' => auth()->id(),
            'center_id' => $validated['center_id'] ?? $employee?->center_id,
            'project_id' => $validated['project_id'] ?? $employee?->project_id,
            'movement_date' => $validated['movement_date'],
            'departure_time' => $validated['departure_time'] ?? null,
            'return_time' => $validated['return_time'] ?? null,
            'from_location' => $validated['from_location'] ?? null,
            'to_location' => $validated['to_location'] ?? null,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
            'refer_to_pm2_id' => $this->defaultProjectsManagerId($validated['project_id'] ?? $employee?->project_id),
            'status' => 'review',
        ]);

        $pm2Id = $plan->refer_to_pm2_id;
        $plan->logWorkflow('create', $pm2Id, 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', 'review');

        if ($pm2Id !== null) {
            $plan->referTo($pm2Id, 'pm2');
        }

        return redirect()->route('admin.movement-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع');
    }

    public function show(MovementPlan $movementPlan)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $movementPlan->isVisibleToUserId($user->id)) {
            abort(403, 'هذه الخطة ليست موجهة إليك');
        }

        $movementPlan->load([
            'creator', 'center', 'project', 'movementOfficer', 'projectsManager', 'assigner',
            'recipients.user', 'workflowActions.fromUser', 'workflowActions.toUser',
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