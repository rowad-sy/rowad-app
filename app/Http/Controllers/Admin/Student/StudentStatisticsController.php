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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Student,view');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['center_id', 'project_id', 'course_id', 'period_id', 'date_from', 'date_to']);
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        // Default scope from user's permission
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Student');
        if (!$request->has('center_id') && count($scope['center_ids']) === 1) {
            $filters['center_id'] = (string) $scope['center_ids'][0];
        }
        if (!$request->has('project_id') && count($scope['project_ids']) === 1) {
            $filters['project_id'] = (string) $scope['project_ids'][0];
        }

        // Cache key: unfiltered = static, filtered = unique per filter set
        $cacheKey = 'student_stats_' . md5(serialize($filters));
        $cacheTtl = $hasFilters ? 0 : 300; // 5min cache only for unfiltered

        $data = $hasFilters
            ? $this->computeStats($filters, $scope)
            : Cache::remember($cacheKey, $cacheTtl, fn () => $this->computeStats($filters, $scope));

        // Always pass filter values + dropdowns to view
        $data['filters'] = $filters;
        $data['centers'] = Center::orderBy('name')->get(['id', 'name']);
        $data['projects'] = Project::orderBy('name')->get(['id', 'name']);
        $data['courses'] = Course::orderBy('name_ar')->get(['id', 'name_ar']);
        $data['periods'] = Period::orderBy('name_ar')->get(['id', 'name_ar']);

        return view('admin.students.statistics', $data);
    }

    private function computeStats(array $filters, array $scope): array
    {
        // ── Scoped Queries ──
        $studentQuery = $this->scopeStudentQuery($filters, $scope);
        $enrollmentQuery = $this->scopeEnrollmentQuery($filters, $scope);
        $attendanceQuery = $this->scopeAttendanceQuery($filters, $scope);

        // ── Summary Counts ──
        $totalStudents = (clone $studentQuery)->count();
        $activeStudents = (clone $studentQuery)->where('status', 'active')->count();
        $inactiveStudents = (clone $studentQuery)->where('status', 'inactive')->count();
        $graduatedStudents = (clone $studentQuery)->where('status', 'graduated')->count();
        $suspendedStudents = (clone $studentQuery)->where('status', 'suspended')->count();
        $maleStudents = (clone $studentQuery)->where('gender', 'male')->count();
        $femaleStudents = (clone $studentQuery)->where('gender', 'female')->count();
        $totalEnrollments = (clone $enrollmentQuery)->count();

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
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('students.project_id', $filters['project_id']))
            ->unless($scope['sees_all'], fn ($q) => !empty($scope['center_ids']) ? $q->whereIn('centers.id', $scope['center_ids']) : $q)
            ->groupBy('centers.id', 'centers.name')
            ->orderByDesc('total')
            ->get();
        $centerLabels = $centerStats->pluck('name')->toArray();
        $centerData = $centerStats->pluck('total')->toArray();

        // ── Project Distribution ──
        $projectStats = Project::select('projects.id', 'projects.name')
            ->selectRaw('COUNT(students.id) as total')
            ->leftJoin('students', 'projects.id', '=', 'students.project_id')
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('students.center_id', $filters['center_id']))
            ->unless($scope['sees_all'], fn ($q) => !empty($scope['project_ids']) ? $q->whereIn('projects.id', $scope['project_ids']) : $q)
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('total')
            ->get();
        $projectLabels = $projectStats->pluck('name')->toArray();
        $projectData = $projectStats->pluck('total')->toArray();

        // ── Course Enrollment Distribution (from scoped enrollments) ──
        $courseStats = Course::select('courses.id', 'courses.name_ar')
            ->selectRaw('COUNT(se.id) as total')
            ->leftJoin('student_enrollments as se', 'courses.id', '=', 'se.course_id')
            ->when(!empty($filters['period_id']), fn ($q) => $q->where('se.period_id', $filters['period_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('se.enrollment_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('se.enrollment_date', '<=', $filters['date_to']))
            ->when(
                !empty($filters['center_id']) || !empty($filters['project_id']) || (!$scope['sees_all'] && (!empty($scope['center_ids']) || !empty($scope['project_ids']))),
                fn ($q) => $q->whereIn('se.student_id', (clone $studentQuery)->select('id'))
            )
            ->groupBy('courses.id', 'courses.name_ar')
            ->orderByDesc('total')
            ->get();
        $courseLabels = $courseStats->pluck('name_ar')->toArray();
        $courseData = $courseStats->pluck('total')->toArray();

        // ── Period Enrollment Distribution (from scoped enrollments) ──
        $periodStats = Period::select('periods.id', 'periods.name_ar')
            ->selectRaw('COUNT(se.id) as total')
            ->leftJoin('student_enrollments as se', 'periods.id', '=', 'se.period_id')
            ->when(!empty($filters['course_id']), fn ($q) => $q->where('se.course_id', $filters['course_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('se.enrollment_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('se.enrollment_date', '<=', $filters['date_to']))
            ->when(
                !empty($filters['center_id']) || !empty($filters['project_id']) || (!$scope['sees_all'] && (!empty($scope['center_ids']) || !empty($scope['project_ids']))),
                fn ($q) => $q->whereIn('se.student_id', (clone $studentQuery)->select('id'))
            )
            ->groupBy('periods.id', 'periods.name_ar')
            ->orderByDesc('total')
            ->get();
        $periodLabels = $periodStats->pluck('name_ar')->toArray();
        $periodData = $periodStats->pluck('total')->toArray();

        // ── Monthly Enrollment Trends (last 12 months) ──
        $monthStart = now()->subMonths(12)->startOfMonth();
        $monthlyTrends = (clone $enrollmentQuery)
            ->selectRaw("DATE_FORMAT(enrollment_date, '%Y-%m') as month")
            ->selectRaw('COUNT(*) as total')
            ->where('enrollment_date', '>=', $monthStart)
            ->groupBy(DB::raw("DATE_FORMAT(enrollment_date, '%Y-%m')"))
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
        $todayAttendance = (clone $attendanceQuery)
            ->where('date', $today)
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
        $weekAttendance = (clone $attendanceQuery)
            ->whereBetween('date', [$weekStart, $weekEnd])
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
            $weekLabels[] = Carbon::parse($day->date)->locale('ar')->translatedFormat('l');
            $weekPresent[] = (int) $day->present;
            $weekAbsent[] = (int) $day->absent;
            $weekExcused[] = (int) $day->excused;
        }

        return compact(
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
        );
    }

    private function scopeStudentQuery(array $filters, array $scope): \Illuminate\Database\Eloquent\Builder
    {
        return Student::query()
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('center_id', $filters['center_id']))
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('project_id', $filters['project_id']))
            ->unless($scope['sees_all'] || !empty($filters['center_id']), fn ($q) => !empty($scope['center_ids']) ? $q->whereIn('center_id', $scope['center_ids']) : $q)
            ->unless($scope['sees_all'] || !empty($filters['project_id']), fn ($q) => !empty($scope['project_ids']) ? $q->whereIn('project_id', $scope['project_ids']) : $q);
    }

    private function scopeEnrollmentQuery(array $filters, array $scope): \Illuminate\Database\Eloquent\Builder
    {
        $q = StudentEnrollment::query();

        if (!empty($filters['course_id'])) {
            $q->where('course_id', $filters['course_id']);
        }
        if (!empty($filters['period_id'])) {
            $q->where('period_id', $filters['period_id']);
        }
        if (!empty($filters['date_from'])) {
            $q->where('enrollment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $q->where('enrollment_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['center_id']) || !empty($filters['project_id'])) {
            $q->whereIn('student_id', (clone $this->scopeStudentQuery($filters, $scope))->select('id'));
        } elseif (!$scope['sees_all'] && (!empty($scope['center_ids']) || !empty($scope['project_ids']))) {
            $q->whereIn('student_id', (clone $this->scopeStudentQuery($filters, $scope))->select('id'));
        }

        return $q;
    }

    private function scopeAttendanceQuery(array $filters, array $scope): \Illuminate\Database\Eloquent\Builder
    {
        $q = Attendance::query();

        if (!empty($filters['center_id']) || !empty($filters['project_id'])) {
            $q->whereIn('student_id', (clone $this->scopeStudentQuery($filters, $scope))->select('id'));
        } elseif (!$scope['sees_all'] && (!empty($scope['center_ids']) || !empty($scope['project_ids']))) {
            $q->whereIn('student_id', (clone $this->scopeStudentQuery($filters, $scope))->select('id'));
        }

        return $q;
    }
}
