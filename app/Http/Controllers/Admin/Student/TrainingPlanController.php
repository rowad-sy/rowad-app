<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Subject;
use App\Models\Admin\Student\TrainingPlan;
use App\Models\Admin\Student\TrainingPlanLesson;
use Illuminate\Http\Request;

class TrainingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Course,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Student\Course,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Student\Course,edit')->only(['edit', 'update']);
    }

    public function index(Request $request)
    {
        $plans = TrainingPlan::with(['project'])
            ->withCount('lessons')
            ->when($request->input('project_id'), fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->input('search'), fn ($q, $v) => $q->where('name_ar', 'like', "%{$v}%"))
            ->orderBy('start_date', 'desc')
            ->get();

        $projects = Project::orderBy('name')->get();
        $projectId = $request->input('project_id') ? (int) $request->input('project_id') : '';
        $search = $request->input('search');

        return view('admin.students.training-plans.index', compact('plans', 'projects', 'projectId', 'search'));
    }

    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $levels = AcademicLevel::with('project')->orderBy('sort_order')->get();
        $subjects = Subject::orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();

        $course = null;
        $defaultProjectId = null;
        $courseId = (int) $request->query('course_id', 0);
        if ($courseId > 0) {
            $course = Course::with(['project', 'levels', 'subjects'])->find($courseId);
            if ($course) {
                $levels = $course->levels;
                $subjects = $course->subjects;
                $defaultProjectId = $course->project_id;
            }
        }

        return view('admin.students.training-plans.form', compact('projects', 'levels', 'subjects', 'instructors', 'course', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);

        $plan = TrainingPlan::create([
            'project_id' => $data['project_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        $conflicts = $this->syncLessons($plan, $data['lessons'] ?? []);

        if ($conflicts->isNotEmpty()) {
            return redirect()->route('admin.students.training-plans.edit', $plan)
                ->withInput()
                ->with('conflicts', $conflicts);
        }

        return redirect()->route('admin.students.training-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة التدريبية بنجاح');
    }

    public function edit(TrainingPlan $plan)
    {
        $plan->load(['lessons']);

        $projects = Project::orderBy('name')->get();
        $levels = AcademicLevel::with('project')->orderBy('sort_order')->get();
        $subjects = Subject::orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();

        return view('admin.students.training-plans.form', compact('plan', 'projects', 'levels', 'subjects', 'instructors'));
    }

    public function update(Request $request, TrainingPlan $plan)
    {
        $data = $this->validatePlan($request);

        $plan->update([
            'project_id' => $data['project_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        $conflicts = $this->syncLessons($plan, $data['lessons'] ?? []);

        if ($conflicts->isNotEmpty()) {
            return redirect()->route('admin.students.training-plans.edit', $plan)
                ->withInput()
                ->with('conflicts', $conflicts);
        }

        return redirect()->route('admin.students.training-plans.show', $plan)
            ->with('success', 'تم تحديث الخطة التدريبية بنجاح');
    }

    public function show(TrainingPlan $plan)
    {
        $plan->load(['project']);

        $lessons = $plan->lessons()
            ->with(['level', 'subject', 'instructor'])
            ->orderBy('week_number')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('week_number');

        $levels = $plan->lessons()->with('level')->get()->pluck('level')->unique('id')->values();

        return view('admin.students.training-plans.show', compact('plan', 'lessons', 'levels'));
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,completed,cancelled',
            'lessons' => 'nullable|array',
            'lessons.*.academic_level_id' => 'required|exists:academic_levels,id',
            'lessons.*.subject_id' => 'required|exists:subjects,id',
            'lessons.*.instructor_id' => 'nullable|exists:hr_employees,id',
            'lessons.*.week_number' => 'required|integer|min:1',
            'lessons.*.day_of_week' => 'required|integer|between:1,7',
            'lessons.*.start_time' => 'nullable',
            'lessons.*.end_time' => 'nullable',
            'lessons.*.lesson_name' => 'nullable|string|max:255',
            'lessons.*.location' => 'nullable|string|max:255',
        ]);
    }

    /**
     * يزامن الدروس مع الخطة ويكشف تعارض المدرّس (نفس المدرّس في صفّين بنفس اليوم/الوقت).
     * يعيد قائمة التعارضات للعرض للمستخدم.
     */
    private function syncLessons(TrainingPlan $plan, array $lessons): \Illuminate\Support\Collection
    {
        // أزل مسبقاً أي دروس لم تعد موجودة في القائمة المرسلة
        $existingIds = collect($lessons)
            ->filter(fn ($l) => !empty($l['id']))
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        // حذف الدروس المحذوفة من النموذج قبل إعادة البناء (حتى لا تُحذف الدروس الجديدة)
        $plan->lessons()->whereNotIn('id', $existingIds->isEmpty() ? [PHP_INT_MAX] : $existingIds)->delete();

        $plannedData = collect($lessons)->filter(fn ($l) => !empty($l['academic_level_id']) && !empty($l['subject_id']));

        // الدرس يعتبر متعارضاً إذا نُقل/أُنشئ لنفس المدرّس ونفس اليوم/الوقت في نفس الأسبوع
        $slots = []; // instructor|day|start|week => true
        $conflicts = collect();

        foreach ($plannedData as $lesson) {
            $instructorId = $lesson['instructor_id'] ?? null;
            $key = ($instructorId ?: 'no_teacher') . '|' . ($lesson['day_of_week'] ?? '') . '|' . trim($lesson['start_time'] ?? '') . '|' . ($lesson['week_number'] ?? '');

            if (isset($slots[$key])) {
                $level = AcademicLevel::find($lesson['academic_level_id']);
                $instructor = $instructorId ? Employee::find($instructorId) : null;
                $conflicts->push(
                    "تم تجاهل درس متعارض: المدرّس " . ($instructor?->first_name_ar . ' ' . $instructor?->last_name_ar ?: '؟') .
                    " مؤتمت بصفّين في نفس الموعد (الأسبوع {$lesson['week_number']}، اليوم {$lesson['day_of_week']}، " . ($lesson['start_time'] ?? '—') . ") — " . ($level->name_ar ?? '؟')
                );
                // نمرّر ولا نحفظ هذا الدرس
                continue;
            }
            $slots[$key] = true;
            $this->upsertLesson($plan, $lesson);
        }

        return $conflicts;
    }

    private function upsertLesson(TrainingPlan $plan, array $lesson): void
    {
        $instructorId = $lesson['instructor_id'] ?? null;
        $data = [
            'academic_level_id' => $lesson['academic_level_id'],
            'subject_id' => $lesson['subject_id'],
            'instructor_id' => $instructorId,
            'week_number' => $lesson['week_number'],
            'day_of_week' => $lesson['day_of_week'],
            'start_time' => $lesson['start_time'] ?? null,
            'end_time' => $lesson['end_time'] ?? null,
            'lesson_name' => $lesson['lesson_name'] ?? null,
            'location' => $lesson['location'] ?? null,
        ];

        if (!empty($lesson['id']) && is_numeric($lesson['id'])) {
            $plan->lessons()->whereKey((int) $lesson['id'])->update($data);
        } else {
            $plan->lessons()->create($data);
        }
    }
}
