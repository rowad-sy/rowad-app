<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\ApprovalRule;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\ApprovalRule,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Logistics\ApprovalRule,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\ApprovalRule,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Logistics\ApprovalRule,delete')->only(['destroy']);
    }

    public function index()
    {
        $rules = ApprovalRule::with('approvers')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.logistics.approval-rules.index', compact('rules'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        return view('admin.logistics.approval-rules.form', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'required_approvals' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'approver_ids' => 'required|array',
            'approver_ids.*' => 'exists:users,id',
        ]);

        $rule = ApprovalRule::create([
            'name' => $validated['name'],
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'] ?? null,
            'required_approvals' => $validated['required_approvals'],
            'notes' => $validated['notes'],
        ]);

        $rule->approvers()->sync($validated['approver_ids']);

        return redirect()->route('admin.logistics.approval-rules.index')
            ->with('success', 'تم إضافة قاعدة الاعتماد بنجاح');
    }

    public function edit(ApprovalRule $approvalRule)
    {
        $users = User::orderBy('name')->get();
        $rule = $approvalRule;
        $rule->load('approvers');

        return view('admin.logistics.approval-rules.form', compact('rule', 'users'));
    }

    public function update(Request $request, ApprovalRule $approvalRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'required_approvals' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'approver_ids' => 'required|array',
            'approver_ids.*' => 'exists:users,id',
        ]);

        $approvalRule->update([
            'name' => $validated['name'],
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'] ?? null,
            'required_approvals' => $validated['required_approvals'],
            'notes' => $validated['notes'],
        ]);

        $approvalRule->approvers()->sync($validated['approver_ids']);

        return redirect()->route('admin.logistics.approval-rules.index')
            ->with('success', 'تم تحديث قاعدة الاعتماد بنجاح');
    }

    public function destroy(ApprovalRule $approvalRule)
    {
        $approvalRule->approvers()->detach();
        $approvalRule->delete();

        return redirect()->route('admin.logistics.approval-rules.index')
            ->with('success', 'تم حذف قاعدة الاعتماد بنجاح');
    }
}
