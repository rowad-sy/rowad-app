<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\LevelSubjectInstructor;
use App\Models\Admin\Student\Subject;
use Illuminate\Http\Request;

class AcademicLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Course,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Student\Course,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Student\Course,edit')->only(['edit', 'update']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $projectId = $request->input('project_id');

        $levels = AcademicLevel::with(['project', 'subjects'])
            ->when($search, fn ($q, $v) => $q->where('name_ar', 'like', "%{$v}%"))
            ->when($projectId, fn ($q, $v) => $q->where('project_id', $v))
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_id');

        $projects = Project::orderBy('name')->get();
        $projectId = $projectId ? (int) $projectId : '';

        return view('admin.students.levels.index', compact('levels', 'projects', 'search', 'projectId'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $subjects = Subject::orderBy('sort_order')->orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();

        $level = new AcademicLevel();
        $level->type = 'grade';
        $level->sort_order = 0;

        return view('admin.students.levels.form', compact('projects', 'subjects', 'instructors', 'level'));
    }

    public function store(Request $request)
    {
        $data = $this->validateLevel($request);

        $level = AcademicLevel::create([
            'project_id' => $data['project_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'type' => $data['type'] ?? 'grade',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncSubjectAssignments($level, $data['subjects'] ?? []);

        return redirect()->route('admin.students.levels.show', $level)
            ->with('success', 'تم إنشاء المستوى/الصف بنجاح');
    }

    public function edit(AcademicLevel $level)
    {
        $level->load(['subjects', 'subjectInstructors']);

        $projects = Project::orderBy('name')->get();
        $subjects = Subject::orderBy('sort_order')->orderBy('name_ar')->get();
        $instructors = Employee::orderBy('first_name_ar')->get();

        return view('admin.students.levels.form', compact('projects', 'subjects', 'instructors', 'level'));
    }

    public function update(Request $request, AcademicLevel $level)
    {
        $data = $this->validateLevel($request);

        $level->update([
            'project_id' => $data['project_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'type' => $data['type'] ?? 'grade',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncSubjectAssignments($level, $data['subjects'] ?? []);

        return redirect()->route('admin.students.levels.show', $level)
            ->with('success', 'تم تحديث المستوى/الصف بنجاح');
    }

    public function show(AcademicLevel $level)
    {
        $level->load(['project', 'subjects', 'subjectInstructors.instructor']);

        return view('admin.students.levels.show', compact('level'));
    }

    private function validateLevel(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name_ar' => 'required|string|max:200',
            'name_en' => 'nullable|string|max:200',
            'code' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'subjects' => 'nullable|array',
            'subjects.*.subject_id' => 'required_with:subjects|exists:subjects,id',
            'subjects.*.instructors' => 'nullable|array',
            'subjects.*.instructors.*' => 'exists:hr_employees,id',
            'subjects.*.main_instructor_id' => 'nullable|exists:hr_employees,id',
        ]);
    }

    /**
     * يزامن مواد المستوى ومدرّسيها.
     * البنية: subjects[] حيث كل عنصر يحتوي subject_id + instructors[] + main_instructor_id.
     */
    private function syncSubjectAssignments(AcademicLevel $level, array $subjects): void
    {
        $subjects = array_values($subjects);

        // حدّد المواد المراد إبقاؤها
        $keepSubjectIds = collect($subjects)
            ->filter(fn ($s) => !empty($s['subject_id']))
            ->pluck('subject_id')
            ->map(fn ($v) => (int) $v);

        // أزل المواد غير الموجودة في القائمة من pivot والمدرّسين
        if ($keepSubjectIds->isEmpty()) {
            $level->subjects()->detach();
            LevelSubjectInstructor::where('academic_level_id', $level->id)->delete();
            return;
        }

        $level->subjects()->sync(
            collect($subjects)->reduce(function ($carry, $s, $index) {
                if (!empty($s['subject_id'])) {
                    $carry[(int) $s['subject_id']] = ['sort_order' => $index];
                }
                return $carry;
            }, [])
        );

        // أزل مدرّسي المواد التي لم تعد موجودة
        LevelSubjectInstructor::where('academic_level_id', $level->id)
            ->whereNotIn('subject_id', $keepSubjectIds)
            ->delete();

        foreach ($subjects as $subject) {
            $subjectId = (int) $subject['subject_id'];
            $instructorIds = collect($subject['instructors'] ?? [])
                ->filter(fn ($v) => !empty($v))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            // المدرّس الرئيسي يجب أن يكون ضمن القائمة
            $mainInstructorId = $instructorIds->contains((int) ($subject['main_instructor_id'] ?? 0))
                ? (int) $subject['main_instructor_id']
                : ($instructorIds->first() ?: null);

            // أزل مدرّسي هذه المادة غير المحددين
            $existingRows = LevelSubjectInstructor::where('academic_level_id', $level->id)
                ->where('subject_id', $subjectId)
                ->get();

            if ($instructorIds->isEmpty()) {
                foreach ($existingRows as $row) {
                    $row->delete();
                }
                continue;
            }

            $existingIds = $existingRows->pluck('instructor_id');
            foreach ($existingRows as $row) {
                if (!$instructorIds->contains((int) $row->instructor_id)) {
                    $row->delete();
                }
            }

            foreach ($instructorIds as $instructorId) {
                LevelSubjectInstructor::updateOrCreate(
                    [
                        'academic_level_id' => $level->id,
                        'subject_id' => $subjectId,
                        'instructor_id' => $instructorId,
                    ],
                    ['is_main' => $instructorId === $mainInstructorId]
                );
            }

            // تأكد من أن المدرّس الرئيسي محدد (إن وُجد) مع تعطيل غيره
            if ($mainInstructorId) {
                LevelSubjectInstructor::where('academic_level_id', $level->id)
                    ->where('subject_id', $subjectId)
                    ->update(['is_main' => false]);
                LevelSubjectInstructor::where('academic_level_id', $level->id)
                    ->where('subject_id', $subjectId)
                    ->where('instructor_id', $mainInstructorId)
                    ->update(['is_main' => true]);
            }
        }
    }
}
