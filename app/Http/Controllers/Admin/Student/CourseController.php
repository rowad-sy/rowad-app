<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\CourseOffering;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Subject;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public const LEVEL_TYPES = [
        'grade' => 'صف',
        'level' => 'مستوى تدريب',
        'childhood' => 'طفولة',
        'kindergarten' => 'روضة',
        'course' => 'دورة/دبلومة',
    ];

    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Course,view')->only(['index', 'help']);
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

        $courses = Course::with(['project', 'periods', 'levels'])
            ->withCount(['subjects', 'offerings', 'levels'])
            ->with([
                'subjects' => fn ($q) => $q->withCount('exams'),
            ])
            ->when($search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name_ar', 'like', "%{$v}%")
                    ->orWhere('name_en', 'like', "%{$v}%");
            }))
            ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
            ->unless($scope['sees_all'], fn ($q) => !empty($scope['project_ids']) ? $q->whereIn('project_id', $scope['project_ids']) : $q)
            ->orderBy('id', 'desc')
            ->get();

        // إجمالي عدد الامتحانات لكل مقرر (مجموع امتحانات مواده)
        $examsTotal = [];
        foreach ($courses as $course) {
            $examsTotal[$course->id] = $course->subjects->sum('exams_count');
        }

        return view('admin.students.courses.index', compact('courses', 'search', 'projectId', 'projects', 'examsTotal'));
    }

    public function create()
    {
        $projects = $this->scopedProjects();
        $periods = Period::orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();
        $levelTypes = self::LEVEL_TYPES;

        // Default project from current user's employee record
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.students.courses.form', compact('projects', 'periods', 'instructors', 'defaultProjectId', 'levelTypes'));
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
            'levels' => 'nullable|array',
            'levels.*.id' => 'nullable|integer',
            'levels.*.name_ar' => 'required_with:levels|string|max:255',
            'levels.*.type' => 'nullable|string|max:50',
            'levels.*.code' => 'nullable|string|max:100',
            'levels.*.sort_order' => 'nullable|integer|min:0',
            'subjects' => 'nullable|array',
            'subjects.*.id' => 'nullable|integer',
            'subjects.*.name_ar' => 'required_with:subjects|string|max:255',
            'subjects.*.name_en' => 'nullable|string|max:255',
            'subjects.*.hours' => 'nullable|numeric|min:0',
            'subjects.*.weight' => 'nullable|numeric|min:0',
            'subjects.*.exams' => 'nullable|array',
            'subjects.*.exams.*.id' => 'nullable|integer',
            'subjects.*.exams.*.name_ar' => 'nullable|string|max:255',
            'subjects.*.exams.*.type' => 'nullable|string|max:50',
            'subjects.*.exams.*.max_score' => 'nullable|numeric|min:0',
            'offerings' => 'nullable|array',
            'offerings.*.id' => 'nullable|integer',
            'offerings.*.period_id' => 'required_with:offerings|exists:periods,id',
            'offerings.*.instructor_id' => 'nullable|exists:hr_employees,id',
            'offerings.*.name_ar' => 'nullable|string|max:255',
            'offerings.*.session_time' => 'nullable|string|max:100',
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

        $this->syncLevels($course, $validated['levels'] ?? []);
        $this->syncSubjects($course, $validated['subjects'] ?? []);
        $this->syncOfferings($course, $validated['offerings'] ?? []);

        return redirect()->route('admin.students.courses.index')
            ->with('success', 'تم إضافة المقرر بنجاح');
    }

    public function edit(Course $course)
    {
        $projects = $this->scopedProjects();
        $periods = Period::orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();
        $levelTypes = self::LEVEL_TYPES;
        $course->load(['periods', 'levels', 'subjects.exams', 'offerings']);

        return view('admin.students.courses.form', compact('course', 'projects', 'periods', 'instructors', 'levelTypes'));
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
            'levels' => 'nullable|array',
            'levels.*.id' => 'nullable|integer',
            'levels.*.name_ar' => 'required_with:levels|string|max:255',
            'levels.*.type' => 'nullable|string|max:50',
            'levels.*.code' => 'nullable|string|max:100',
            'levels.*.sort_order' => 'nullable|integer|min:0',
            'subjects' => 'nullable|array',
            'subjects.*.id' => 'nullable|integer',
            'subjects.*.name_ar' => 'required_with:subjects|string|max:255',
            'subjects.*.name_en' => 'nullable|string|max:255',
            'subjects.*.hours' => 'nullable|numeric|min:0',
            'subjects.*.weight' => 'nullable|numeric|min:0',
            'subjects.*.exams' => 'nullable|array',
            'subjects.*.exams.*.id' => 'nullable|integer',
            'subjects.*.exams.*.name_ar' => 'nullable|string|max:255',
            'subjects.*.exams.*.type' => 'nullable|string|max:50',
            'subjects.*.exams.*.max_score' => 'nullable|numeric|min:0',
            'offerings' => 'nullable|array',
            'offerings.*.id' => 'nullable|integer',
            'offerings.*.period_id' => 'required_with:offerings|exists:periods,id',
            'offerings.*.instructor_id' => 'nullable|exists:hr_employees,id',
            'offerings.*.name_ar' => 'nullable|string|max:255',
            'offerings.*.session_time' => 'nullable|string|max:100',
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

        $this->syncLevels($course, $validated['levels'] ?? []);
        $this->syncSubjects($course, $validated['subjects'] ?? []);
        $this->syncOfferings($course, $validated['offerings'] ?? []);

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

    public function help()
    {
        return view('admin.students.courses.help', ['levelTypes' => self::LEVEL_TYPES]);
    }

    private function syncSubjects(Course $course, array $subjects): void
    {
        $existingIds = collect($subjects)
            ->filter(fn ($s) => !empty($s['id']) && is_numeric($s['id']))
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        $course->subjects()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach (array_values($subjects) as $index => $subject) {
            if (!empty($subject['id']) && is_numeric($subject['id'])) {
                $subjectModel = $course->subjects()->find((int) $subject['id']);

                $course->subjects()->whereKey((int) $subject['id'])->update([
                    'name_ar' => $subject['name_ar'],
                    'name_en' => $subject['name_en'] ?? null,
                    'hours' => $subject['hours'] ?? null,
                    'weight' => $subject['weight'] ?? null,
                    'sort_order' => $index,
                ]);
            } else {
                $subjectModel = $course->subjects()->create([
                    'name_ar' => $subject['name_ar'],
                    'name_en' => $subject['name_en'] ?? null,
                    'hours' => $subject['hours'] ?? null,
                    'weight' => $subject['weight'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            $this->syncExams($subjectModel, $subject['exams'] ?? []);
        }
    }

    private function syncExams(Subject $subject, array $exams): void
    {
        $existingIds = collect($exams)
            ->filter(fn ($e) => !empty($e['id']) && is_numeric($e['id']))
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        $subject->exams()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach (array_values($exams) as $index => $exam) {
            $data = [
                'name_ar' => $exam['name_ar'] ?? '',
                'type' => $exam['type'] ?? null,
                'max_score' => $exam['max_score'] !== '' && $exam['max_score'] !== null ? $exam['max_score'] : null,
                'sort_order' => $index,
            ];

            if (!empty($exam['id']) && is_numeric($exam['id'])) {
                $subject->exams()->whereKey((int) $exam['id'])->update($data);
            } else {
                // تجاهل صف الامتحان الفارغ (بلا اسم)
                if (trim((string) ($data['name_ar'])) === '') {
                    continue;
                }
                $subject->exams()->create($data);
            }
        }
    }

    private function syncLevels(Course $course, array $levels): void
    {
        $existingIds = collect($levels)
            ->filter(fn ($l) => !empty($l['id']) && is_numeric($l['id']))
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        $course->levels()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach (array_values($levels) as $index => $level) {
            $data = [
                'project_id' => $course->project_id,
                'course_id' => $course->id,
                'name_ar' => $level['name_ar'],
                'type' => $level['type'] ?? 'level',
                'code' => $level['code'] ?? null,
                'sort_order' => $level['sort_order'] ?? $index,
            ];

            if (!empty($level['id']) && is_numeric($level['id'])) {
                $course->levels()->whereKey((int) $level['id'])->update($data);
            } else {
                $course->levels()->create($data);
            }
        }
    }

    private function syncOfferings(Course $course, array $offerings): void
    {
        $existingIds = collect($offerings)
            ->filter(fn ($o) => !empty($o['id']) && is_numeric($o['id']))
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        $course->offerings()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach (array_values($offerings) as $offering) {
            if (!empty($offering['id']) && is_numeric($offering['id'])) {
                $course->offerings()->whereKey((int) $offering['id'])->update([
                    'period_id' => $offering['period_id'],
                    'instructor_id' => $offering['instructor_id'] ?? null,
                    'name_ar' => $offering['name_ar'] ?? null,
                    'session_time' => $offering['session_time'] ?? null,
                ]);
            } else {
                $course->offerings()->create([
                    'period_id' => $offering['period_id'],
                    'instructor_id' => $offering['instructor_id'] ?? null,
                    'name_ar' => $offering['name_ar'] ?? null,
                    'session_time' => $offering['session_time'] ?? null,
                ]);
            }
        }
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
