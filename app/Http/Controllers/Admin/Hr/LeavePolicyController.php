<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;

class LeavePolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\LeaveType,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Hr\LeaveType,create')->only(['store']);
        $this->middleware('permission:App\Models\Admin\Hr\LeaveType,edit')->only(['update']);
        $this->middleware('permission:App\Models\Admin\Hr\LeaveType,delete')->only(['destroy']);
    }

    public function index()
    {
        $leaveTypes = LeaveType::orderBy('name_ar')->get();
        $users = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.leave-policies.index', compact('leaveTypes', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'annual_days' => 'required|integer|min:0',
            'requires_approval' => 'boolean',
            'approver_ids' => 'nullable|array',
            'approver_ids.*' => 'exists:users,id',
            'color' => 'required|string|max:7',
            'icon' => 'required|string|max:50',
        ]);

        $validated['approver_ids'] = $validated['approver_ids'] ?? [];
        $validated['requires_approval'] = $request->boolean('requires_approval');

        LeaveType::create($validated);

        return redirect()->route('admin.hr.leave-policies.index')
            ->with('success', 'تم إضافة نوع الإجازة بنجاح');
    }

    public function update(Request $request, LeaveType $leavePolicy)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'annual_days' => 'required|integer|min:0',
            'requires_approval' => 'boolean',
            'approver_ids' => 'nullable|array',
            'approver_ids.*' => 'exists:users,id',
            'color' => 'required|string|max:7',
            'icon' => 'required|string|max:50',
        ]);

        $validated['approver_ids'] = $validated['approver_ids'] ?? [];
        $validated['requires_approval'] = $request->boolean('requires_approval');

        $leavePolicy->update($validated);

        return redirect()->route('admin.hr.leave-policies.index')
            ->with('success', 'تم تحديث سياسة الإجازة بنجاح');
    }

    public function destroy(LeaveType $leavePolicy)
    {
        $leavePolicy->delete();

        return redirect()->route('admin.hr.leave-policies.index')
            ->with('success', 'تم حذف نوع الإجازة بنجاح');
    }
}
