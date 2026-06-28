<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\ApprovalRule;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestApproval;
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

        $approval->update([
            'status' => 'approved',
            'notes' => $request->notes,
            'decided_at' => now(),
        ]);

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
            $purchaseRequest->update(['status' => 'approved']);
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

        $approval->update([
            'status' => 'rejected',
            'notes' => $request->notes,
            'decided_at' => now(),
        ]);

        $purchaseRequest->update(['status' => 'rejected']);

        return redirect()->back()
            ->with('success', 'تم رفض طلب الشراء');
    }
}
