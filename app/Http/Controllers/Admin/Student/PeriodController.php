<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Period,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Period,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Student\Period,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Period,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $year = $request->input('year');
        $perPage = (int) $request->input('per_page', 10);

        // Default filter to current user's project
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $projectId = $request->has('project_id') ? $request->input('project_id') : ($userEmployee?->project_id ?? '');

        $periods = Period::with('project')
            ->when($search, fn($q, $v) => $q->where('name_ar', 'like', "%{$v}%"))
            ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
            ->when($year, fn($q, $v) => $q->where('year', $v))
            ->orderBy('year', 'desc')
            ->orderBy('start_date')
            ->paginate($perPage)
            ->appends($request->only(['search', 'project_id', 'year', 'per_page']));

        $projects = Project::orderBy('name')->get();
        $years = Period::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('admin.students.periods.index', compact('periods', 'search', 'projectId', 'year', 'perPage', 'projects', 'years'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $courses = Course::orderBy('name_ar')->get();

        // Default project from current user's employee record
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.students.periods.form', compact('projects', 'courses', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name_ar' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $period = Period::create([
            'project_id' => $validated['project_id'],
            'name_ar' => $validated['name_ar'],
            'year' => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        if (!empty($validated['course_ids'])) {
            $period->courses()->sync($validated['course_ids']);
        }

        return redirect()->route('admin.students.periods.index')
            ->with('success', 'تم إضافة الفترة بنجاح');
    }

    public function edit(Period $period)
    {
        $projects = Project::orderBy('name')->get();
        $courses = Course::orderBy('name_ar')->get();
        $period->load('courses');

        return view('admin.students.periods.form', compact('period', 'projects', 'courses'));
    }

    public function update(Request $request, Period $period)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name_ar' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $period->update([
            'project_id' => $validated['project_id'],
            'name_ar' => $validated['name_ar'],
            'year' => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        if (!empty($validated['course_ids'])) {
            $period->courses()->sync($validated['course_ids']);
        }

        return redirect()->route('admin.students.periods.index')
            ->with('success', 'تم تحديث الفترة بنجاح');
    }

    public function destroy(Period $period)
    {
        $period->courses()->detach();
        $period->delete();

        return redirect()->route('admin.students.periods.index')
            ->with('success', 'تم حذف الفترة بنجاح');
    }
}
