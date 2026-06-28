<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\LeaveRequest;
use App\Models\Admin\Hr\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\LeaveRequest,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Hr\LeaveRequest,create')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 10);

        $employee = Employee::where('user_id', auth()->id())->first();

        $requests = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->when($employee && !\App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Hr\LeaveRequest', 'view', null, null, null),
                fn ($q) => $q->where('employee_id', $employee?->id))
            ->when($search, fn ($q) => $q->whereHas('employee', fn ($q) => $q->where('first_name_ar', 'like', "%{$search}%")->orWhere('last_name_ar', 'like', "%{$search}%")))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'per_page']));

        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name_ar')->get();

        return view('admin.hr.leave-requests.index', compact('requests', 'search', 'status', 'perPage', 'leaveTypes'));
    }

    public function create()
    {
        $employee = Employee::where('user_id', auth()->id())->firstOrFail();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name_ar')->get();
        $balances = $employee->leaveBalances()->with('leaveType')->where('year', now()->year)->get();

        return view('admin.hr.leave-requests.form', compact('employee', 'leaveTypes', 'balances'));
    }

    public function store(Request $request)
    {
        $employee = Employee::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:hr_leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Calculate working days (excluding weekends Fri/Sat)
        $daysCount = 0;
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dayOfWeek = $d->dayOfWeek;
            // Friday=5, Saturday=6 in Carbon (ISO-8601)
            if ($dayOfWeek !== 5 && $dayOfWeek !== 6) {
                $daysCount++;
            }
        }

        if ($daysCount === 0) {
            return back()->withErrors(['end_date' => 'لا يوجد أيام عمل في التاريخ المحدد'])->withInput();
        }

        // Check balance
        $balance = $employee->leaveBalances()
            ->where('leave_type_id', $leaveType->id)
            ->where('year', now()->year)
            ->first();

        if ($balance && ($balance->remaining_days < $daysCount)) {
            return back()->withErrors(['leave_type_id' => 'الرصيد غير كافٍ. المتبقي: ' . $balance->remaining_days . ' يوم'])->withInput();
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.hr.leave-requests.index')
            ->with('success', 'تم تقديم طلب الإجازة بنجاح');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'لا يمكن حذف طلب تمت الموافقة عليه أو رفضه');
        }

        $leaveRequest->delete();

        return redirect()->route('admin.hr.leave-requests.index')
            ->with('success', 'تم حذف الطلب بنجاح');
    }
}
