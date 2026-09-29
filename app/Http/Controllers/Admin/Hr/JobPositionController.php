<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\JobPosition;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\JobPosition,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Hr\JobPosition,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Hr\JobPosition,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Hr\JobPosition,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $positions = JobPosition::when($search, function ($q, $search) {
            return $q->where('title_ar', 'like', "%{$search}%")
                ->orWhere('title_en', 'like', "%{$search}%");
        })->orderBy('title_ar')->paginate(10)->withQueryString();

        return view('admin.hr.job-positions.index', compact('positions', 'search'));
    }

    public function create()
    {
        return view('admin.hr.job-positions.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
        ]);

        JobPosition::create($validated);

        return redirect()->route('admin.hr.job-positions.index')
            ->with('success', 'تم إضافة المنصب الوظيفي بنجاح');
    }

    public function edit(JobPosition $jobPosition)
    {
        return view('admin.hr.job-positions.form', compact('jobPosition'));
    }

    public function update(Request $request, JobPosition $jobPosition)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
        ]);

        $jobPosition->update($validated);

        return redirect()->route('admin.hr.job-positions.index')
            ->with('success', 'تم تحديث المنصب الوظيفي بنجاح');
    }

    public function destroy(JobPosition $jobPosition)
    {
        $jobPosition->delete();

        return redirect()->route('admin.hr.job-positions.index')
            ->with('success', 'تم حذف المنصب الوظيفي بنجاح');
    }
}
