<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Cohort;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use Illuminate\Http\Request;

class CohortController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Cohort,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Cohort,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Cohort,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Cohort,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $projectId = $request->input('project_id');

        $cohorts = Cohort::with(['project', 'manager'])
            ->withCount('students')
            ->when($search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($projectId, fn ($q, $v) => $q->where('project_id', $v))
            ->orderBy('project_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.cohorts.index', compact('cohorts', 'projects', 'search', 'projectId'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('first_name_ar')->get();

        return view('admin.cohorts.form', compact('projects', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCohort($request);

        Cohort::create([
            'project_id' => $validated['project_id'],
            'manager_id' => $validated['manager_id'] ?? null,
            'name' => $validated['name'],
            'shift' => $validated['shift'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.cohorts.index')
            ->with('success', 'تم إضافة الفوج بنجاح');
    }

    public function edit(Cohort $cohort)
    {
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('first_name_ar')->get();

        return view('admin.cohorts.form', compact('cohort', 'projects', 'employees'));
    }

    public function update(Request $request, Cohort $cohort)
    {
        $validated = $this->validateCohort($request);

        $cohort->update([
            'project_id' => $validated['project_id'],
            'manager_id' => $validated['manager_id'] ?? null,
            'name' => $validated['name'],
            'shift' => $validated['shift'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.cohorts.index')
            ->with('success', 'تم تحديث الفوج بنجاح');
    }

    public function destroy(Cohort $cohort)
    {
        $cohort->delete();

        return redirect()->route('admin.cohorts.index')
            ->with('success', 'تم حذف الفوج بنجاح');
    }

    private function validateCohort(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'manager_id' => 'nullable|exists:hr_employees,id',
            'name' => 'required|string|max:200',
            'shift' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
    }
}
