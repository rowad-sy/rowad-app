<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeAttendance;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Hr\LeaveType;
use App\Models\Admin\Hr\WorkSchedule;
use App\Models\Admin\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\Employee,view');
    }

    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $centerId = $request->input('center_id');
        $projectId = $request->input('project_id');
        $departmentId = $request->input('department_id');
        $search = $request->input('search');

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name_ar')->get();

        return view('admin.hr.timesheets.index', compact(
            'month', 'centerId', 'projectId', 'departmentId', 'search',
            'centers', 'projects', 'departments'
        ));
    }

    public function print(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $centerId = $request->input('center_id');
        $projectId = $request->input('project_id');
        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $employeeIds = $request->input('employee_ids');

        $employees = Employee::with(['center', 'department', 'project'])
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('first_name_ar', 'like', "%{$search}%")
                        ->orWhere('last_name_ar', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($employeeIds, fn ($q) => $q->whereIn('id', $employeeIds))
            ->where('status', 'active')
            ->orderBy('first_name_ar')
            ->get();

        $parsed = Carbon::parse($month . '-01');
        $year = $parsed->year;
        $monthNum = $parsed->month;
        $daysInMonth = $parsed->daysInMonth;

        // Build daily data for each employee
        $timesheets = [];
        $leaveTypes = LeaveType::where('is_active', true)->pluck('name_ar', 'id');

        foreach ($employees as $employee) {
            $schedules = $employee->workSchedules->keyBy('day_of_week');
            $attendances = EmployeeAttendance::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

            $daily = [];
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalExcused = 0;
            $leaveCounts = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::parse(sprintf('%s-%02d-%02d', $year, $monthNum, $day));
                $dayOfWeek = $date->dayOfWeek;
                $dateKey = $date->format('Y-m-d');
                $schedule = $schedules->get($dayOfWeek);

                $isDayOff = $schedule && $schedule->is_day_off;
                $scheduledHours = $isDayOff ? null : ($schedule ? ($schedule->start_time . '-' . $schedule->end_time) : '08:00-16:00');

                $attendance = $attendances->get($dateKey);
                $status = $attendance?->status ?? ($isDayOff ? 'off' : 'present');
                $leaveTypeId = $attendance?->leave_type_id;

                if ($status === 'present') $totalPresent++;
                elseif ($status === 'absent') $totalAbsent++;
                elseif ($status === 'excused') {
                    $totalExcused++;
                    if ($leaveTypeId) {
                        $leaveCounts[$leaveTypeId] = ($leaveCounts[$leaveTypeId] ?? 0) + 1;
                    }
                }

                $daily[] = [
                    'day' => $day,
                    'date' => $dateKey,
                    'day_name' => $date->locale('ar')->translatedFormat('D'),
                    'is_off' => $isDayOff,
                    'hours' => $scheduledHours,
                    'status' => $status,
                    'leave_type_id' => $leaveTypeId,
                ];
            }

            $position = JobPosition::find($employee->job_position_id);
            $leaveDetails = [];
            foreach ($leaveCounts as $ltId => $count) {
                $leaveDetails[] = ($leaveTypes[$ltId] ?? 'إجازة') . ": $count";
            }

            $timesheets[] = [
                'employee' => $employee,
                'position' => $position?->title_ar ?? '',
                'daily' => $daily,
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'total_excused' => $totalExcused,
                'leave_details' => implode(' | ', $leaveDetails),
            ];
        }

        $arabicMonth = $parsed->locale('ar')->translatedFormat('F Y');

        return view('admin.hr.timesheets.print', compact(
            'timesheets', 'month', 'year', 'monthNum', 'daysInMonth',
            'arabicMonth'
        ));
    }
}
