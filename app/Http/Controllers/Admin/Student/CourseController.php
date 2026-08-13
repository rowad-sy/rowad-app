<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Course,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Course,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Student\Course,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Course,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        // Default scope from user's permission
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Course');

        // تقييد المشاريع المتاحة للمستخدم حسب نطاق صلاحياته
        $projects = $this->scopedProjects();

        // فلترة المشروع المحدد ضمن النطاق فقط (لا يمكن تجاوز النطاق عبر project_id)
        $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;
        if (!$scope['sees_all']) {
            if ($projectId !== null && !in_array($projectId, $scope['project_ids'], true)) {
                $projectId = count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : null;
            }
            if ($projectId === null && count($scope['project_ids']) === 1) {
                $projectId = $scope['project_ids'][0];
            }
        }

        $courses = Course::with('project')
            ->when($search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name_ar', 'like', "%{$v}%")
                    ->orWhere('name_en', 'like', "%{$v}%");
            }))
            ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
            ->unless($scope['sees_all'], fn ($q) => !empty($scope['project_ids']) ? $q->whereIn('project_id', $scope['project_ids']) : $q)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'project_id', 'per_page']));

        return view('admin.students.courses.index', compact('courses', 'search', 'projectId', 'perPage', 'projects'));
    }

    public function create()
    {
        $projects = $this->scopedProjects();
        $periods = Period::orderBy('name_ar')->get();

        // Default project from current user's employee record
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.students.courses.form', compact('projects', 'periods', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'period_ids' => 'nullable|array',
            'period_ids.*' => 'exists:periods,id',
        ]);

        if (!$this->projectInScope($validated['project_id'] ?? null)) {
            return back()->withErrors(['project_id' => 'لا تملك صلاحية إدارة هذا المشروع'])->withInput();
        }

        $course = Course::create([
            'project_id' => $validated['project_id'],
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
        ]);

        if (!empty($validated['period_ids'])) {
            $course->periods()->sync($validated['period_ids']);
        }

        return redirect()->route('admin.students.courses.index')
            ->with('success', 'تم إضافة المقرر بنجاح');
    }

    public function edit(Course $course)
    {
        $projects = $this->scopedProjects();
        $periods = Period::orderBy('name_ar')->get();
        $course->load('periods');

        return view('admin.students.courses.form', compact('course', 'projects', 'periods'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'period_ids' => 'nullable|array',
            'period_ids.*' => 'exists:periods,id',
        ]);

        if (!$this->projectInScope($validated['project_id'] ?? null)) {
            return back()->withErrors(['project_id' => 'لا تملك صلاحية إدارة هذا المشروع'])->withInput();
        }

        $course->update([
            'project_id' => $validated['project_id'],
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
        ]);

        if (!empty($validated['period_ids'])) {
            $course->periods()->sync($validated['period_ids']);
        }

        return redirect()->route('admin.students.courses.index')
            ->with('success', 'تم تحديث المقرر بنجاح');
    }

    public function destroy(Course $course)
    {
        if (!$this->projectInScope($course->project_id)) {
            abort(403, 'لا تملك صلاحية حذف هذا المقرر');
        }

        $course->periods()->detach();
        $course->delete();

        return redirect()->route('admin.students.courses.index')
            ->with('success', 'تم حذف المقرر بنجاح');
    }

    private function scopedProjects()
    {
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Course');

        return Project::orderBy('name')
            ->unless($scope['sees_all'], fn ($q) => !empty($scope['project_ids']) ? $q->whereIn('id', $scope['project_ids']) : $q)
            ->get();
    }

    private function projectInScope(?int $projectId): bool
    {
        if ($projectId === null) {
            return true;
        }

        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Course');

        return $scope['sees_all'] || in_array((int) $projectId, $scope['project_ids'], true);
    }
}
