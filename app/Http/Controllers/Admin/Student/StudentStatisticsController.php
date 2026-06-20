<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Attendance;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class StudentStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Student,view');
    }

    public function index()
    {
        // ── Summary Counts ──
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $inactiveStudents = Student::where('status', 'inactive')->count();
        $graduatedStudents = Student::where('status', 'graduated')->count();
        $suspendedStudents = Student::where('status', 'suspended')->count();
        $maleStudents = Student::where('gender', 'male')->count();
        $femaleStudents = Student::where('gender', 'female')->count();
        $totalEnrollments = StudentEnrollment::count();

        // ── Gender Distribution ──
        $genderLabels = ['ذكر', 'أنثى'];
        $genderData = [$maleStudents, $femaleStudents];
        $genderColors = ['#0d6efd', '#d63384'];

        // ── Status Distribution ──
        $statusLabels = ['نشط', 'غير نشط', 'متخرج', 'موقوف'];
        $statusData = [$activeStudents, $inactiveStudents, $graduatedStudents, $suspendedStudents];
        $statusColors = ['#198754', '#6c757d', '#0d6efd', '#ffc107'];

        // ── Center Distribution ──
        $centerStats = Center::select('centers.id', 'centers.name')
            ->selectRaw('COUNT(students.id) as total')
            ->leftJoin('students', 'centers.id', '=', 'students.center_id')
            ->groupBy('centers.id', 'centers.name')
            ->orderByDesc('total')
            ->get();
        $centerLabels = $centerStats->pluck('name')->toArray();
        $centerData = $centerStats->pluck('total')->toArray();

        // ── Project Distribution ──
        $projectStats = Project::select('projects.id', 'projects.name')
            ->selectRaw('COUNT(students.id) as total')
            ->leftJoin('students', 'projects.id', '=', 'students.project_id')
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('total')
            ->get();
        $projectLabels = $projectStats->pluck('name')->toArray();
        $projectData = $projectStats->pluck('total')->toArray();

        // ── Course Enrollment Distribution ──
        $courseStats = Course::select('courses.id', 'courses.name_ar')
            ->selectRaw('COUNT(student_enrollments.id) as total')
            ->leftJoin('student_enrollments', 'courses.id', '=', 'student_enrollments.course_id')
            ->groupBy('courses.id', 'courses.name_ar')
            ->orderByDesc('total')
            ->get();
        $courseLabels = $courseStats->pluck('name_ar')->toArray();
        $courseData = $courseStats->pluck('total')->toArray();

        // ── Period Enrollment Distribution ──
        $periodStats = Period::select('periods.id', 'periods.name_ar')
            ->selectRaw('COUNT(student_enrollments.id) as total')
            ->leftJoin('student_enrollments', 'periods.id', '=', 'student_enrollments.period_id')
            ->groupBy('periods.id', 'periods.name_ar')
            ->orderByDesc('total')
            ->get();
        $periodLabels = $periodStats->pluck('name_ar')->toArray();
        $periodData = $periodStats->pluck('total')->toArray();

        // ── Monthly Enrollment Trends (last 12 months) ──
        $monthlyTrends = StudentEnrollment::selectRaw("strftime('%Y-%m', enrollment_date) as month")
            ->selectRaw('COUNT(*) as total')
            ->where('enrollment_date', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy(DB::raw("strftime('%Y-%m', enrollment_date)"))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyLabels = [];
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $monthlyLabels[] = now()->subMonths($i)->locale('ar')->translatedFormat('F Y');
            $monthlyData[] = (int) ($monthlyTrends[$month]->total ?? 0);
        }

        // ── Attendance Overview (today) ──
        $today = now()->format('Y-m-d');
        $todayAttendance = Attendance::where('date', $today)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $todayPresent = (int) ($todayAttendance['present'] ?? 0);
        $todayAbsent = (int) ($todayAttendance['absent'] ?? 0);
        $todayExcused = (int) ($todayAttendance['excused'] ?? 0);
        $todayTotal = $todayPresent + $todayAbsent + $todayExcused;

        // ── Weekly Attendance ──
        $weekStart = now()->startOfWeek()->format('Y-m-d');
        $weekEnd = now()->endOfWeek()->format('Y-m-d');
        $weekAttendance = Attendance::whereBetween('date', [$weekStart, $weekEnd])
            ->selectRaw("date, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN status='excused' THEN 1 ELSE 0 END) as excused")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $weekLabels = [];
        $weekPresent = [];
        $weekAbsent = [];
        $weekExcused = [];
        foreach ($weekAttendance as $day) {
            $weekLabels[] = \Carbon\Carbon::parse($day->date)->locale('ar')->translatedFormat('l');
            $weekPresent[] = (int) $day->present;
            $weekAbsent[] = (int) $day->absent;
            $weekExcused[] = (int) $day->excused;
        }

        return view('admin.students.statistics', compact(
            'totalStudents', 'activeStudents', 'inactiveStudents', 'graduatedStudents', 'suspendedStudents',
            'maleStudents', 'femaleStudents', 'totalEnrollments',
            'genderLabels', 'genderData', 'genderColors',
            'statusLabels', 'statusData', 'statusColors',
            'centerLabels', 'centerData', 'centerStats',
            'projectLabels', 'projectData', 'projectStats',
            'courseLabels', 'courseData', 'courseStats',
            'periodLabels', 'periodData', 'periodStats',
            'monthlyLabels', 'monthlyData',
            'todayPresent', 'todayAbsent', 'todayExcused', 'todayTotal',
            'weekLabels', 'weekPresent', 'weekAbsent', 'weekExcused',
        ));
    }
}
