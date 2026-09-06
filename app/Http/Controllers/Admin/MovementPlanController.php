<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class MovementPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MovementPlan,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,edit')->only(['approve', 'reject', 'assign', 'complete']);
        $this->middleware('permission:App\Models\Admin\MovementPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MovementPlan::with(['center', 'project', 'creator', 'movementOfficer'])
            ->withCount('recipients');

        if ($user->type !== 'super-admin') {
            $employee = Employee::where('user_id', $user->id)->first();

            $query->where(function ($q) use ($user, $employee) {
                $q->where('created_by', $user->id)
                    ->orWhere('refer_to_movement_officer_id', $user->id)
                    ->orWhere('assigned_by', $user->id)
                    ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                    ->when($employee?->center_id, fn ($q2, $centerId) => $q2->orWhere('center_id', $centerId));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        $plans = $query->orderBy('movement_date', 'desc')->paginate(15);
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
            $validated['departure_time']
            && $validated['return_time']
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
            'status' => 'review',
        ]);

        $plan->logWorkflow('create', null, 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', 'review');

        return redirect()->route('admin.movement-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع');
    }

    public function show(MovementPlan $movementPlan)
    {
        $movementPlan->load([
            'creator', 'center', 'project', 'movementOfficer', 'assigner',
            'recipients.user', 'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.movement-plans.show', ['plan' => $movementPlan, 'users' => $users]);
    }

    public function approve(Request $request, MovementPlan $movementPlan)
    {
        abort_if($movementPlan->status !== 'review', 403, 'الخطة ليست بانتظار مراجعة إدارة المشاريع.');

        $validated = $request->validate([
            'movement_officer_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $movementPlan->update([
            'refer_to_movement_officer_id' => $validated['movement_officer_id'],
            'status' => 'approved',
        ]);

        $movementPlan->logWorkflow('approve', $validated['movement_officer_id'], $validated['note'] ?? 'وافقت إدارة المشاريع وأُحيلت لمسؤول الحركة', 'approved');

        return back()->with('success', 'اعتُمدت خطة الحركة وأُحيلت لمسؤول الحركة.');
    }

    public function reject(Request $request, MovementPlan $movementPlan)
    {
        abort_if($movementPlan->status !== 'review', 403, 'الخطة ليست بانتظار مراجعة إدارة المشاريع.');

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $movementPlan->update([
            'status' => 'rejected',
            'reason' => $validated['reason'],
        ]);

        $movementPlan->logWorkflow('reject', null, $validated['reason'], 'rejected');

        return back()->with('success', 'رُفضت خطة الحركة.');
    }

    public function assign(Request $request, MovementPlan $movementPlan)
    {
        abort_if(! in_array($movementPlan->status, ['approved', 'assigned'], true), 403, 'الخطة ليست في مرحلة توزيع المتابِعين.');

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

        $movementPlan->logWorkflow('assign', null, 'حدّد مسؤول الحركة المستفيدين للمتابعة', 'assigned');

        return back()->with('success', 'حُددت جهات المتابعة — خطة الحركة قيد المتابعة الآن.');
    }

    public function complete(MovementPlan $movementPlan)
    {
        abort_if($movementPlan->status !== 'assigned', 403, 'الخطة ليست قيد المتابعة.');

        $movementPlan->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $movementPlan->logWorkflow('complete', null, 'أُنجزت خطة الحركة', 'completed');

        return back()->with('success', 'أُنقلت خطة الحركة كمنجزة.');
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