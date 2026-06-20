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

        // Default filter to current user's project
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $projectId = $request->has('project_id') ? $request->input('project_id') : ($userEmployee?->project_id ?? '');

        $courses = Course::with('project')
            ->when($search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name_ar', 'like', "%{$v}%")
                    ->orWhere('name_en', 'like', "%{$v}%");
            }))
            ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'project_id', 'per_page']));

        $projects = Project::orderBy('name')->get();

        return view('admin.students.courses.index', compact('courses', 'search', 'projectId', 'perPage', 'projects'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
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
        $projects = Project::orderBy('name')->get();
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
        $course->periods()->detach();
        $course->delete();

        return redirect()->route('admin.students.courses.index')
            ->with('success', 'تم حذف المقرر بنجاح');
    }
}
