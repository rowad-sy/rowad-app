<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectTask,view')->only(['index', 'show', 'calendar']);
        $this->middleware('permission:App\Models\Admin\ProjectTask,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\ProjectTask,edit')->only(['edit', 'update', 'updateStatus']);
        $this->middleware('permission:App\Models\Admin\ProjectTask,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ProjectTask::with(['assignedTo', 'createdBy', 'center']);

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        $tasks = $query->orderBy('start_date', 'desc')->paginate(15);
        $centers = Center::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $statuses = ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'];

        return view('admin.projects.tasks.index', compact('tasks', 'centers', 'users', 'statuses'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.tasks.form', compact('centers', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'purpose' => 'nullable|string|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'needs_media_coverage' => 'boolean',
            'needs_costs' => 'boolean',
            'costs_details' => 'nullable|string|max:2000',
            'needs_equipment' => 'boolean',
            'equipment_details' => 'nullable|string|max:2000',
            'assigned_to' => 'required|exists:users,id',
            'center_id' => 'nullable|exists:centers,id',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['needs_media_coverage'] = $request->boolean('needs_media_coverage');
        $validated['needs_costs'] = $request->boolean('needs_costs');
        $validated['needs_equipment'] = $request->boolean('needs_equipment');
        $validated['status'] = 'pending';

        ProjectTask::create($validated);

        return redirect()->route('admin.projects.tasks.index')
            ->with('success', 'تم إضافة المهمة بنجاح');
    }

    public function show(ProjectTask $task)
    {
        $task->load(['assignedTo', 'createdBy', 'center']);

        return view('admin.projects.tasks.show', compact('task'));
    }

    public function edit(ProjectTask $task)
    {
        $centers = Center::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.tasks.form', compact('task', 'centers', 'users'));
    }

    public function update(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'purpose' => 'nullable|string|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'needs_media_coverage' => 'boolean',
            'needs_costs' => 'boolean',
            'costs_details' => 'nullable|string|max:2000',
            'needs_equipment' => 'boolean',
            'equipment_details' => 'nullable|string|max:2000',
            'assigned_to' => 'required|exists:users,id',
            'center_id' => 'nullable|exists:centers,id',
            'status' => 'required|in:pending,in_progress,completed,delayed,cancelled',
        ]);

        $validated['needs_media_coverage'] = $request->boolean('needs_media_coverage');
        $validated['needs_costs'] = $request->boolean('needs_costs');
        $validated['needs_equipment'] = $request->boolean('needs_equipment');

        $task->update($validated);

        return redirect()->route('admin.projects.tasks.index')
            ->with('success', 'تم تحديث المهمة بنجاح');
    }

    public function updateStatus(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'executed' => 'nullable|boolean',
            'not_executed_reason' => 'nullable|string|max:2000',
            'has_delay' => 'nullable|boolean',
            'delay_reason' => 'nullable|string|max:2000',
            'media_coverage_done' => 'nullable|boolean',
            'no_media_coverage_reason' => 'nullable|string|max:2000',
            'execution_notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:pending,in_progress,completed,delayed,cancelled',
        ]);

        $validated['executed'] = $request->boolean('executed');
        $validated['has_delay'] = $request->boolean('has_delay');
        $validated['media_coverage_done'] = $request->boolean('media_coverage_done');

        $task->update($validated);

        return redirect()->back()->with('success', 'تم تحديث حالة المهمة بنجاح');
    }

    public function destroy(ProjectTask $task)
    {
        $task->delete();

        return redirect()->route('admin.projects.tasks.index')
            ->with('success', 'تم حذف المهمة بنجاح');
    }

    public function calendar(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $user = auth()->user();
        $query = ProjectTask::with(['assignedTo', 'createdBy', 'center'])
            ->whereYear('start_date', '<=', $year)
            ->whereYear('end_date', '>=', $year)
            ->where(function ($q) use ($year, $month) {
                $q->whereMonth('start_date', '<=', $month)
                  ->orWhereMonth('end_date', '>=', $month);
            });

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        $tasks = $query->orderBy('start_date')->get();

        $statuses = ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'];

        return view('admin.projects.tasks.calendar', compact('tasks', 'year', 'month', 'statuses'));
    }

    public function statistics(Request $request)
    {
        $user = auth()->user();
        $query = ProjectTask::query();

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        $total = (clone $query)->count();
        $byStatus = (clone $query)->selectRaw("status, count(*) as count")->groupBy('status')->pluck('count', 'status');
        $executed = (clone $query)->where('executed', true)->count();
        $notExecuted = (clone $query)->where('executed', false)->count();
        $withDelay = (clone $query)->where('has_delay', true)->count();
        $mediaDone = (clone $query)->where('media_coverage_done', true)->count();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $delayed = (clone $query)->where('status', 'delayed')->count();
        $byMonth = (clone $query)->selectRaw("MONTH(start_date) as month, count(*) as count")->groupBy('month')->pluck('count', 'month');

        return view('admin.projects.tasks.statistics', compact(
            'total', 'byStatus', 'executed', 'notExecuted', 'withDelay',
            'mediaDone', 'pendingCount', 'inProgress', 'completed', 'delayed', 'byMonth'
        ));
    }
}
