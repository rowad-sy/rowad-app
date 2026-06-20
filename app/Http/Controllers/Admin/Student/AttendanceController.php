<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Attendance;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Attendance,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Attendance,create')->only(['store']);
    }

    public function index(Request $request)
    {
        $date = $request->input('date', today()->format('Y-m-d'));
        $courseId = $request->input('course_id');
        $periodId = $request->input('period_id');

        // Default filters to current user's center/project
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $centerId = $request->has('center_id') ? $request->input('center_id') : ($userEmployee?->center_id ?? '');
        $projectId = $request->has('project_id') ? $request->input('project_id') : ($userEmployee?->project_id ?? '');

        $students = Student::where('status', 'active')
            ->when($centerId, fn($q, $v) => $q->where('center_id', $v))
            ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
            ->when($courseId || $periodId, function ($q) use ($courseId, $periodId) {
                $q->whereHas('enrollments', function ($eq) use ($courseId, $periodId) {
                    $eq->when($courseId, fn($qq, $v) => $qq->where('course_id', $v))
                       ->when($periodId, fn($qq, $v) => $qq->where('period_id', $v));
                });
            })
            ->orderBy('first_name_ar')
            ->get();

        $attendanceMap = Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $courses = Course::orderBy('name_ar')->get();
        $periods = Period::orderBy('name_ar')->get();

        return view('admin.students.attendance', compact(
            'students', 'attendanceMap', 'date', 'centerId', 'projectId',
            'courseId', 'periodId', 'centers', 'projects', 'courses', 'periods'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,excused',
        ]);

        $date = $request->input('date');

        foreach ($request->input('attendance') as $item) {
            Attendance::updateOrCreate(
                ['student_id' => $item['student_id'], 'date' => $date],
                [
                    'status' => $item['status'],
                    'note' => $item['note'] ?? null,
                    'created_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('admin.students.attendance', ['date' => $date])
            ->with('success', 'تم حفظ الحضور بنجاح');
    }
}
