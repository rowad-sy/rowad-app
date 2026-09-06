<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Student\StudentEnrollment;
use App\Models\Admin\Student\StudentSubjectGrade;
use Illuminate\Http\Request;

class StudentGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\StudentEnrollment,view')->only(['edit']);
        $this->middleware('permission:App\Models\Admin\Student\StudentEnrollment,edit')->only(['update']);
    }

    public function edit(StudentEnrollment $enrollment)
    {
        $enrollment->load(['student', 'course', 'period']);
        $subjects = $enrollment->course->subjects()->get();

        $grades = StudentSubjectGrade::where('student_enrollment_id', $enrollment->id)
            ->pluck('grade', 'subject_id');

        return view('admin.students.enrollments.grades', compact('enrollment', 'subjects', 'grades'));
    }

    public function update(Request $request, StudentEnrollment $enrollment)
    {
        $validated = $request->validate([
            'grades' => 'nullable|array',
            'grades.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $weights = collect($enrollment->course->subjects)
            ->keyBy('id')
            ->map(fn ($s) => (float) ($s->weight ?? 0));

        $total = 0;
        $totalWeight = 0;

        foreach (($validated['grades'] ?? []) as $subjectId => $grade) {
            $subjectId = (int) $subjectId;
            if ($grade === null || $grade === '') {
                StudentSubjectGrade::where('student_enrollment_id', $enrollment->id)
                    ->where('subject_id', $subjectId)
                    ->delete();
                continue;
            }

            StudentSubjectGrade::updateOrCreate(
                ['student_enrollment_id' => $enrollment->id, 'subject_id' => $subjectId],
                ['grade' => $grade]
            );

            $weight = $weights->get($subjectId, 0);
            $total += (float) $grade * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            $enrollment->update(['grade' => round($total / $totalWeight, 2)]);
        }

        return redirect()->route('admin.students.show', $enrollment->student)
            ->with('success', 'تم حفظ درجات المواد بنجاح');
    }
}
