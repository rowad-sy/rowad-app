<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\ApprovalRule;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestApproval;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PurchaseRequestApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\PurchaseRequest,edit')->only(['approve', 'reject']);
    }

    public function approve(PurchaseRequest $purchaseRequest, Request $request)
    {
        $user = auth()->user();

        $isApprover = $purchaseRequest->approvals()
            ->where('user_id', $user->id)
            ->whereNull('decided_at')
            ->exists();

        if (!$isApprover) {
            return redirect()->back()
                ->with('error', 'أنت لست أحد المعتمدين على هذا الطلب');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $approval = PurchaseRequestApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('user_id', $user->id)
            ->whereNull('decided_at')
            ->firstOrFail();

        $oldStatus = $approval->status;
        $approval->update([
            'status' => 'approved',
            'notes' => $request->notes,
            'decided_at' => now(),
        ]);

        AuditLogger::record(
            model: $approval,
            event: 'approved',
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'approved', 'notes' => $request->notes],
            description: "اعتماد طلب الشراء #{$purchaseRequest->id}",
        );

        $total = $purchaseRequest->expected_total_price;

        $rule = ApprovalRule::where('min_amount', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->where('max_amount', '>=', $total)->orWhereNull('max_amount');
            })
            ->first();

        $requiredApprovals = $rule?->required_approvals ?? 1;

        $approvedCount = $purchaseRequest->approvals()
            ->where('status', 'approved')
            ->count();

        if ($approvedCount >= $requiredApprovals) {
            $oldStatus = $purchaseRequest->status;
            $purchaseRequest->update(['status' => 'approved']);

            AuditLogger::record(
                model: $purchaseRequest,
                event: 'status_changed',
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved'],
                description: "تم اعتماد طلب الشراء #{$purchaseRequest->id} نهائياً بعد تجميع {$approvedCount} موافقات",
            );
        }

        return redirect()->back()
            ->with('success', 'تم اعتماد طلب الشراء بنجاح');
    }

    public function reject(PurchaseRequest $purchaseRequest, Request $request)
    {
        $user = auth()->user();

        $isApprover = $purchaseRequest->approvals()
            ->where('user_id', $user->id)
            ->whereNull('decided_at')
            ->exists();

        if (!$isApprover) {
            return redirect()->back()
                ->with('error', 'أنت لست أحد المعتمدين على هذا الطلب');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $approval = PurchaseRequestApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('user_id', $user->id)
            ->whereNull('decided_at')
            ->firstOrFail();

        $oldStatus = $approval->status;
        $approval->update([
            'status' => 'rejected',
            'notes' => $request->notes,
            'decided_at' => now(),
        ]);

        AuditLogger::record(
            model: $approval,
            event: 'rejected',
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'rejected', 'notes' => $request->notes],
            description: "رفض طلب الشراء #{$purchaseRequest->id}",
        );

        $oldPrStatus = $purchaseRequest->status;
        $purchaseRequest->update(['status' => 'rejected']);

        AuditLogger::record(
            model: $purchaseRequest,
            event: 'status_changed',
            oldValues: ['status' => $oldPrStatus],
            newValues: ['status' => 'rejected'],
            description: "تم رفض طلب الشراء #{$purchaseRequest->id}",
        );

        return redirect()->back()
            ->with('success', 'تم رفض طلب الشراء');
    }
}
