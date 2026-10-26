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
        $status = array_key_exists((string) $request->input('status'), Project::STATUSES) ? $request->input('status') : null;
        $perPageOptions = [10, 15, 25, 50, 100];
        $requestedPerPage = (int) $request->query('per_page', 10);
        $perPage = in_array($requestedPerPage, $perPageOptions, true) ? $requestedPerPage : 10;

        $projects = Project::with(['centers', 'path'])
            ->when($status, fn ($q, $value) => $q->where('projects.status', $value))
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();

        $statusQuery = Project::query()
            ->when($search, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"));
        $statusCounts = (clone $statusQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $totalProjects = $statusQuery->count();

        return view('admin.projects.index', compact('projects', 'search', 'status', 'perPage', 'perPageOptions', 'statusCounts', 'totalProjects'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $centers = Center::orderBy('name')->get();
        $paths = \App\Models\Admin\ProjectPath::ordered()->get();
        $defaultPathId = $request->filled('path') ? (int) $request->input('path') : null;

        return view('admin.projects.form', compact('centers', 'paths', 'defaultPathId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:' . implode(',', array_keys(Project::STATUSES)),
            'code' => 'nullable|string|max:100',
            'sort_order' => 'required_with:path_id|nullable|integer|min:1|max:9999',
            'path_id' => 'nullable|exists:project_paths,id',
            'return_to' => 'nullable|in:paths_tree',
            'centers' => 'nullable|array',
            'centers.*' => 'exists:centers,id',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'code' => $validated['code'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'path_id' => $validated['path_id'] ?? null,
        ]);

        if (!empty($validated['centers'])) {
            $oldIds = $project->centers()->pluck('centers.id')->toArray();
            $project->centers()->attach($validated['centers']);
            $newIds = $project->centers()->pluck('centers.id')->toArray();
            AuditLogger::logPivot($project, 'centers', $oldIds, $newIds, "إضافة مراكز للمشروع {$project->name}");
        }

        if (($validated['return_to'] ?? null) === 'paths_tree') {
            return redirect()->route('admin.paths.tree')
                ->with('success', 'تم إضافة المشروع إلى المسار بنجاح');
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'تم إضافة المشروع بنجاح');
    }

    public function edit(Project $project)
    {
        $project->load('centers');
        $centers = Center::orderBy('name')->get();
        $paths = \App\Models\Admin\ProjectPath::ordered()->get();
        return view('admin.projects.form', compact('project', 'centers', 'paths'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:' . implode(',', array_keys(Project::STATUSES)),
            'code' => 'nullable|string|max:100',
            'sort_order' => 'required_with:path_id|nullable|integer|min:1|max:9999',
            'path_id' => 'nullable|exists:project_paths,id',
            'centers' => 'nullable|array',
            'centers.*' => 'exists:centers,id',
        ]);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'code' => $validated['code'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'path_id' => $validated['path_id'] ?? null,
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
