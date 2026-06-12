<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Department,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Department,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Department,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Department,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $departments = Department::when($search, function ($q, $search) {
            return $q->where('name_ar', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%");
        })->orderBy('name_ar')->paginate(10);

        return view('admin.departments.index', compact('departments', 'search'));
    }

    public function create()
    {
        return view('admin.departments.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'تم إضافة الإدارة بنجاح');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'تم تحديث الإدارة بنجاح');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'تم حذف الإدارة بنجاح');
    }
}
