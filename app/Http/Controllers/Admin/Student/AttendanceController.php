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

        // Default scope from user's permission
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Attendance');
        $centerId = $request->filled('center_id') ? $request->input('center_id') : (count($scope['center_ids']) === 1 ? $scope['center_ids'][0] : '');
        $projectId = $request->filled('project_id') ? $request->input('project_id') : (count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : '');

        $students = Student::where('status', 'active')
            ->when($centerId, fn($q, $v) => $q->where('center_id', $v))
            ->when($projectId, fn($q, $v) => $q->inProjects([(int) $v]))
            // نطاق الصلاحية يُطبَّق دائماً ولا يمكن تجاوزه بالفلاتر اليدوية
            ->when(!$scope['sees_all'] && !empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
            ->when(!$scope['sees_all'] && !empty($scope['project_ids']), fn($q) => $q->inProjects($scope['project_ids']))
            ->when(!$scope['sees_all'] && !empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
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

        $centers = Center::when(!$scope['sees_all'] && !empty($scope['center_ids']), fn($q) => $q->whereIn('id', $scope['center_ids']))->orderBy('name')->get();
        $projects = Project::when(!$scope['sees_all'] && !empty($scope['project_ids']), fn($q) => $q->whereIn('id', $scope['project_ids']))->orderBy('name')->get();
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

        // منع تسجيل الحضور لطلاب خارج نطاق صلاحية المستخدم
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Attendance');

        if (!$scope['sees_all']) {
            $allowedStudentIds = Student::query()
                ->when(!empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
                ->when(!empty($scope['project_ids']), fn($q) => $q->inProjects($scope['project_ids']))
                ->when(!empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
                ->pluck('id')
                ->all();

            $submittedStudentIds = collect($request->input('attendance'))
                ->pluck('student_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->all();

            if (array_diff($submittedStudentIds, $allowedStudentIds)) {
                abort(403, 'ليس لديك صلاحية تسجيل الحضور لهؤلاء الطلاب');
            }
        }

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
