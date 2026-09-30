<?php

namespace App\Http\Controllers\Admin\Tech;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechIssue;
use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Http\Request;

class TechIssueController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Tech\TechIssue,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Tech\TechIssue,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Tech\TechIssue,edit')->only(['edit', 'update', 'respond']);
        $this->middleware('permission:App\Models\Admin\Tech\TechIssue,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $perPage = (int) $request->input('per_page', 10);

        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Tech\TechIssue');
        $centerId = $request->filled('center_id') ? $request->input('center_id') : (count($scope['center_ids']) === 1 ? $scope['center_ids'][0] : '');
        $projectId = $request->filled('project_id') ? $request->input('project_id') : (count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : '');

        $issues = RecordAccess::scopeQuery(TechIssue::with(['center', 'project', 'reporter', 'assignee']), TechIssue::class, 'view')
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($priority && $priority !== 'all', fn ($q) => $q->where('priority', $priority))
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'priority', 'center_id', 'project_id', 'per_page']));

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.tech.issues.index', compact('issues', 'search', 'status', 'priority', 'centerId', 'projectId', 'perPage', 'centers', 'projects'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        $userEmployee = Employee::where('user_id', auth()->id())->first();
        $defaultCenterId = $userEmployee?->center_id;
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.tech.issues.form', compact('centers', 'projects', 'users', 'defaultCenterId', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $validated['reported_by'] = auth()->id();
        $validated['status'] = 'open';

        RecordAccess::authorizeTarget(TechIssue::class, 'create', $validated['center_id'] ?? null, $validated['project_id'] ?? null);
        TechIssue::create($validated);

        return redirect()->route('admin.tech.issues.index')
            ->with('success', 'تم إضافة التذكرة بنجاح');
    }

    public function show(TechIssue $issue)
    {
        RecordAccess::authorize(TechIssue::class, 'view', $issue->center_id, $issue->project_id, $issue->id);
        $issue->load(['center', 'project', 'reporter', 'assignee']);
        return view('admin.tech.issues.show', compact('issue'));
    }

    public function edit(TechIssue $issue)
    {
        RecordAccess::authorize(TechIssue::class, 'edit', $issue->center_id, $issue->project_id, $issue->id);
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.tech.issues.form', compact('issue', 'centers', 'projects', 'users'));
    }

    public function update(Request $request, TechIssue $issue)
    {
        RecordAccess::authorize(TechIssue::class, 'edit', $issue->center_id, $issue->project_id, $issue->id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,completed,blocked',
        ]);

        RecordAccess::authorizeTarget(TechIssue::class, 'edit', $validated['center_id'] ?? null, $validated['project_id'] ?? null, $issue->id);
        $issue->update($validated);

        return redirect()->route('admin.tech.issues.index')
            ->with('success', 'تم تحديث التذكرة بنجاح');
    }

    public function destroy(TechIssue $issue)
    {
        RecordAccess::authorize(TechIssue::class, 'delete', $issue->center_id, $issue->project_id, $issue->id);
        $issue->delete();

        return redirect()->route('admin.tech.issues.index')
            ->with('success', 'تم حذف التذكرة بنجاح');
    }

    public function respond(Request $request, TechIssue $issue)
    {
        RecordAccess::authorize(TechIssue::class, 'edit', $issue->center_id, $issue->project_id, $issue->id);
        $validated = $request->validate([
            'admin_response' => 'required|string',
            'status' => 'required|in:open,in_progress,completed,blocked',
        ]);

        $data = ['admin_response' => $validated['admin_response']];

        if ($validated['status'] === 'completed') {
            $data['resolved_at'] = now();
        }

        $issue->update($data + ['status' => $validated['status']]);

        return redirect()->route('admin.tech.issues.show', $issue)
            ->with('success', 'تم تحديث التذكرة بنجاح');
    }
}
