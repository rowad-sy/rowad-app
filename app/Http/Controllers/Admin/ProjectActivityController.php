<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectActivity;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectActivity,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\ProjectActivity,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\ProjectActivity,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\ProjectActivity,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $projectId = $request->input('project_id');
        $activityDate = $request->input('activity_date');

        $scope = PermissionHelper::getEffectiveScope(auth()->user(), ProjectActivity::class);

        $query = ProjectActivity::with(['project', 'center', 'creator']);

        if (! $scope['sees_all']) {
            $query->where(function ($q) use ($scope) {
                if (! empty($scope['project_ids'])) {
                    $q->orWhereIn('project_id', $scope['project_ids']);
                }
                if (! empty($scope['center_ids'])) {
                    $q->orWhereIn('center_id', $scope['center_ids']);
                }
            });
        }

        $activities = $query
            ->when($projectId, fn ($q, $v) => $q->where('project_id', $v))
            ->when($activityDate, fn ($q, $v) => $q->whereDate('activity_date', $v))
            ->orderBy('activity_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.project-activities.index', compact('activities', 'projects', 'projectId', 'activityDate'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('admin.project-activities.form', compact('projects', 'centers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateActivity($request);
        $validated['created_by'] = auth()->id();

        $activity = ProjectActivity::create($validated);

        return redirect()->route('admin.project-activities.index')
            ->with('success', 'تم تسجيل النشاط بنجاح');
    }

    public function edit(ProjectActivity $activity)
    {
        $projects = Project::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('admin.project-activities.form', compact('activity', 'projects', 'centers'));
    }

    public function update(Request $request, ProjectActivity $activity)
    {
        $validated = $this->validateActivity($request);

        $activity->update($validated);

        return redirect()->route('admin.project-activities.index')
            ->with('success', 'تم تحديث النشاط بنجاح');
    }

    public function destroy(ProjectActivity $activity)
    {
        $activity->delete();

        return redirect()->route('admin.project-activities.index')
            ->with('success', 'تم حذف النشاط');
    }

    private function validateActivity(Request $request): array
    {
        return $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'center_id' => 'nullable|exists:centers,id',
            'responsible' => 'nullable|string|max:255',
            'activity_date' => 'required|date',
            'beneficiary' => 'nullable|string|max:255',
            'male_count' => 'nullable|integer|min:0',
            'female_count' => 'nullable|integer|min:0',
            'progress' => 'nullable|string|max:5000',
            'obstacles' => 'nullable|string|max:5000',
        ]);
    }
}