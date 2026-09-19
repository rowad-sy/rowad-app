<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectPath;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProjectPathController extends Controller
{
    public function __construct()
    {
        // الإدارة فقط محميّة بالصلاحية؛ الشجرة والتصدير متاحان لكل موظف (ميزة بوابة)
        $this->middleware('permission:App\Models\Admin\ProjectPath,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\ProjectPath,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\ProjectPath,delete')->only(['destroy']);
        $this->middleware('permission:App\Models\Admin\ProjectPath,view')->only(['index']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $paths = ProjectPath::withCount('projects')
            ->when($search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('admin.paths.index', compact('paths', 'search'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        return view('admin.paths.form', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'projects' => 'nullable|array',
            'projects.*' => 'exists:projects,id',
        ]);

        $path = ProjectPath::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncProjects($path, $validated['projects'] ?? []);

        return redirect()->route('admin.paths.index')
            ->with('success', 'تم إضافة المسار بنجاح');
    }

    public function edit(ProjectPath $path)
    {
        $projects = Project::orderBy('name')->get();
        $path->load('projects');
        return view('admin.paths.form', compact('path', 'projects'));
    }

    public function update(Request $request, ProjectPath $path)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'projects' => 'nullable|array',
            'projects.*' => 'exists:projects,id',
        ]);

        $path->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncProjects($path, $validated['projects'] ?? []);

        return redirect()->route('admin.paths.index')
            ->with('success', 'تم تحديث المسار بنجاح');
    }

    public function destroy(ProjectPath $path)
    {
        Project::where('path_id', $path->id)->update(['path_id' => null]);
        $path->delete();

        return redirect()->route('admin.paths.index')
            ->with('success', 'تم حذف المسار بنجاح');
    }

    /*
     * مشروع واحد ينتمي لمسار واحد؛ عند اختيار مشاريع للمسار نفصلها عن أي
     * مسار آخر تلقائياً.
     */
    private function syncProjects(ProjectPath $path, array $projectIds): void
    {
        $oldIds = $path->projects()->pluck('id')->toArray();
        Project::whereIn('id', $projectIds)->update(['path_id' => $path->id]);
        Project::where('path_id', $path->id)->whereNotIn('id', $projectIds)->update(['path_id' => null]);
        $newIds = $path->projects()->pluck('id')->toArray();
        AuditLogger::logPivot($path, 'projects', $oldIds, $newIds, "تحديث مشاريع المسار {$path->name}");
    }

    /*
     * شجرة المسارات والمشاريع — تُفتح تلقائياً ويمكن طي أي فرع.
     */
    public function tree(Request $request)
    {
        $query = ProjectPath::with(['projects' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->whereHas('projects', fn ($q) => $q->where('status', $request->status));
        }

        $paths = $query->get();
        $orphanProjects = Project::whereNull('path_id')->orderBy('name')->get();
        $statuses = Project::STATUSES;
        $statusCounts = Project::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.paths.tree', compact('paths', 'orphanProjects', 'statuses', 'statusCounts'));
    }

    public function exportExcel()
    {
        $rows = [];
        $i = 0;

        $paths = ProjectPath::with(['projects' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        foreach ($paths as $path) {
            foreach ($path->projects as $project) {
                $rows[] = [
                    'no' => ++$i,
                    'path' => $path->name,
                    'name' => $project->name,
                    'code' => $project->code,
                    'status' => $project->statusLabel(),
                ];
            }
        }

        $orphanProjects = Project::whereNull('path_id')->orderBy('name')->get();
        foreach ($orphanProjects as $project) {
            $rows[] = [
                'no' => ++$i,
                'path' => 'بدون مسار',
                'name' => $project->name,
                'code' => $project->code,
                'status' => $project->statusLabel(),
            ];
        }

        $collection = collect($rows);

        return Excel::download(
            new BaseExport(
                $collection,
                ['م/ت', 'المسار', 'اسم المشروع', 'كود المشروع', 'حالة المشروع'],
                ['no', 'path', 'name', 'code', 'status'],
            ),
            'paths-projects-' . now()->format('Y_m_d') . '.xlsx',
        );
    }

    /*
     * نسخة PDF عبر window.print — A4 أفقي مع لوغو المؤسسة على اليسار.
     */
    public function exportPdf()
    {
        $paths = ProjectPath::with(['projects' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
        $orphanProjects = Project::whereNull('path_id')->orderBy('name')->get();

        return view('admin.paths.print', compact('paths', 'orphanProjects'));
    }
}
