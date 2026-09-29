<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\LeaveBalance;
use App\Models\Admin\Hr\LeaveRequest;
use App\Models\Admin\Hr\LeaveType;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\LeaveRequest,edit');
    }

    public function index()
    {
        $user = auth()->user();

        // Find leave types where current user is an approver
        $leaveTypeIds = LeaveType::where('requires_approval', true)
            ->whereJsonContains('approver_ids', $user->id)
            ->pluck('id');

        $requests = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->whereIn('leave_type_id', $leaveTypeIds)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20)->withQueryString();

        return view('admin.hr.leave-approvals.index', compact('requests'));
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        // Verify current user is an approver for this leave type
        $leaveType = $leaveRequest->leaveType;
        if (!$leaveType || !in_array($user->id, $leaveType->approver_ids ?? [])) {
            return back()->with('error', 'ليس لديك صلاحية الموافقة على هذا الطلب');
        }

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً');
        }

        DB::transaction(function () use ($leaveRequest, $user) {
            $oldStatus = $leaveRequest->status;
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditLogger::record(
                model: $leaveRequest,
                event: 'approved',
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved', 'approved_by' => $user->id],
                description: "موافقة على طلب إجازة #{$leaveRequest->id}",
            );

            // Update or create leave balance
            $year = now()->year;
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $leaveRequest->employee_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'year' => $year,
                ],
                [
                    'total_days' => $leaveRequest->leaveType->annual_days,
                    'used_days' => 0,
                ]
            );

            $oldUsedDays = $balance->used_days;
            $balance->increment('used_days', $leaveRequest->days_count);

            AuditLogger::record(
                model: $balance,
                event: 'updated',
                oldValues: ['used_days' => $oldUsedDays],
                newValues: ['used_days' => $balance->fresh()->used_days],
                description: "تحديث رصيد الإجازات للموظف #{$leaveRequest->employee_id}",
            );
        });

        return redirect()->route('admin.hr.leave-approvals.index')
            ->with('success', 'تمت الموافقة على طلب الإجازة بنجاح');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        $leaveType = $leaveRequest->leaveType;
        if (!$leaveType || !in_array($user->id, $leaveType->approver_ids ?? [])) {
            return back()->with('error', 'ليس لديك صلاحية رفض هذا الطلب');
        }

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً');
        }

        $oldStatus = $leaveRequest->status;
        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLogger::record(
            model: $leaveRequest,
            event: 'rejected',
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'rejected', 'approved_by' => $user->id],
            description: "رفض طلب إجازة #{$leaveRequest->id}",
        );

        return redirect()->route('admin.hr.leave-approvals.index')
            ->with('success', 'تم رفض طلب الإجازة');
    }
}
