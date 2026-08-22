<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Project,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Project,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Project,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Project,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $projects = Project::with('centers')
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })->orderBy('name')->paginate(10);

        return view('admin.projects.index', compact('projects', 'search'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        return view('admin.projects.form', compact('centers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'centers' => 'nullable|array',
            'centers.*' => 'exists:centers,id',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['centers'])) {
            $oldIds = $project->centers()->pluck('centers.id')->toArray();
            $project->centers()->attach($validated['centers']);
            $newIds = $project->centers()->pluck('centers.id')->toArray();
            AuditLogger::logPivot($project, 'centers', $oldIds, $newIds, "إضافة مراكز للمشروع {$project->name}");
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'تم إضافة المشروع بنجاح');
    }

    public function edit(Project $project)
    {
        $project->load('centers');
        $centers = Center::orderBy('name')->get();
        return view('admin.projects.form', compact('project', 'centers'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'centers' => 'nullable|array',
            'centers.*' => 'exists:centers,id',
        ]);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $oldIds = $project->centers()->pluck('centers.id')->toArray();
        $project->centers()->sync($validated['centers'] ?? []);
        $newIds = $project->centers()->pluck('centers.id')->toArray();
        AuditLogger::logPivot($project, 'centers', $oldIds, $newIds, "تحديث مراكز المشروع {$project->name}");

        return redirect()->route('admin.projects.index')
            ->with('success', 'تم تحديث المشروع بنجاح');
    }

    public function destroy(Project $project)
    {
        $oldIds = $project->centers()->pluck('centers.id')->toArray();
        $project->centers()->detach();
        if (!empty($oldIds)) {
            AuditLogger::logPivot($project, 'centers', $oldIds, [], "حذف جميع المراكز من المشروع {$project->name}");
        }
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'تم حذف المشروع بنجاح');
    }
}
