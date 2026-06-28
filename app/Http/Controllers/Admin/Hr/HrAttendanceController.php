<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeAttendance;
use App\Models\Admin\Hr\LeaveType;
use App\Models\Admin\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HrAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\EmployeeAttendance,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Hr\EmployeeAttendance,create')->only(['store']);
        $this->middleware('permission:App\Models\Admin\Hr\EmployeeAttendance,edit')->only(['update']);
    }

    public function index(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $centerId = $request->input('center_id');
        $projectId = $request->input('project_id');

        $employees = Employee::with(['center', 'project'])
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->where('status', 'active')
            ->orderBy('first_name_ar')
            ->get();

        // Load existing attendance for this date
        $attendances = EmployeeAttendance::where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name_ar')->get();

        return view('admin.hr.attendances.index', compact(
            'date', 'centerId', 'projectId',
            'employees', 'attendances',
            'centers', 'projects', 'leaveTypes'
        ));
    }

    public function store(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $attendances = $request->input('attendances', []);

        foreach ($attendances as $employeeId => $data) {
            $status = $data['status'] ?? 'present';
            $leaveTypeId = $data['leave_type_id'] ?? null;
            $notes = $data['notes'] ?? null;

            if ($status === 'excused' && !$leaveTypeId) {
                continue; // Skip if excused but no leave type selected
            }

            EmployeeAttendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                [
                    'status' => $status,
                    'leave_type_id' => $leaveTypeId,
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('admin.hr.attendances.index', ['date' => $date])
            ->with('success', 'تم تسجيل الحضور بنجاح');
    }

    public function update(Request $request, $employeeId)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $validated = $request->validate([
            'status' => 'required|in:present,absent,excused',
            'leave_type_id' => 'nullable|exists:hr_leave_types,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['status'] === 'excused' && !$validated['leave_type_id']) {
            return back()->with('error', 'يجب اختيار نوع الإجازة عند تسجيل غياب بعذر');
        }

        EmployeeAttendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            [
                'status' => $validated['status'],
                'leave_type_id' => $validated['leave_type_id'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'تم تحديث الحضور بنجاح');
    }
}
