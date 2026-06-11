<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use Illuminate\Http\Request;

class CenterController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Center,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Center,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Center,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Center,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $centers = Center::when($search, function ($q, $search) {
            return $q->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%");
        })->orderBy('name')->paginate(10);

        return view('admin.centers.index', compact('centers', 'search'));
    }

    public function create()
    {
        return view('admin.centers.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        Center::create($validated);

        return redirect()->route('admin.centers.index')
            ->with('success', 'تم إضافة المركز بنجاح');
    }

    public function edit(Center $center)
    {
        return view('admin.centers.form', compact('center'));
    }

    public function update(Request $request, Center $center)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        $center->update($validated);

        return redirect()->route('admin.centers.index')
            ->with('success', 'تم تحديث المركز بنجاح');
    }

    public function destroy(Center $center)
    {
        $center->delete();

        return redirect()->route('admin.centers.index')
            ->with('success', 'تم حذف المركز بنجاح');
    }
}
