<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Exports\ProjectPathsExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectPath;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('admin.paths.index', compact('paths', 'search'));
    }

    public function create()
    {
        $projects = Project::ordered()->get();
        $canCreateProjects = PermissionHelper::can(auth()->user(), Project::class, 'create');
        $newProjectOrder = 1;

        return view('admin.paths.form', compact('projects', 'canCreateProjects', 'newProjectOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1|max:9999',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'projects' => 'nullable|array',
            'projects.*' => 'exists:projects,id',
            'new_project' => 'nullable|array',
            'new_project.name' => 'nullable|string|max:255',
            'new_project.code' => 'nullable|string|max:100',
            'new_project.sort_order' => 'required_with:new_project.name|nullable|integer|min:1|max:9999',
            'new_project.status' => 'nullable|in:' . implode(',', array_keys(Project::STATUSES)),
        ]);

        $newProjectName = trim((string) ($validated['new_project']['name'] ?? ''));
        abort_unless($newProjectName === '' || PermissionHelper::can(auth()->user(), Project::class, 'create'), 403);

        $path = DB::transaction(function () use ($validated, $newProjectName) {
            $path = ProjectPath::create([
                'name' => $validated['name'],
                'sort_order' => $validated['sort_order'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            $projectIds = $validated['projects'] ?? [];
            if ($newProjectName !== '') {
                $projectIds[] = Project::create([
                    'name' => $newProjectName,
                    'code' => $validated['new_project']['code'] ?? null,
                    'status' => $validated['new_project']['status'] ?? 'active',
                    'sort_order' => $validated['new_project']['sort_order'],
                ])->id;
            }

            $this->syncProjects($path, $projectIds);

            return $path;
        });

        return redirect()->route('admin.paths.index')
            ->with('success', 'تم إضافة المسار بنجاح');
    }

    public function edit(ProjectPath $path)
    {
        $projects = Project::ordered()->get();
        $path->load('projects');
        $canCreateProjects = PermissionHelper::can(auth()->user(), Project::class, 'create');
        $newProjectOrder = max(1, ((int) $path->projects->max('sort_order')) + 1);

        return view('admin.paths.form', compact('path', 'projects', 'canCreateProjects', 'newProjectOrder'));
    }

    public function update(Request $request, ProjectPath $path)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1|max:9999',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'projects' => 'nullable|array',
            'projects.*' => 'exists:projects,id',
            'new_project' => 'nullable|array',
            'new_project.name' => 'nullable|string|max:255',
            'new_project.code' => 'nullable|string|max:100',
            'new_project.sort_order' => 'required_with:new_project.name|nullable|integer|min:1|max:9999',
            'new_project.status' => 'nullable|in:' . implode(',', array_keys(Project::STATUSES)),
        ]);

        $newProjectName = trim((string) ($validated['new_project']['name'] ?? ''));
        abort_unless($newProjectName === '' || PermissionHelper::can(auth()->user(), Project::class, 'create'), 403);

        DB::transaction(function () use ($path, $validated, $newProjectName) {
            $path->update([
                'name' => $validated['name'],
                'sort_order' => $validated['sort_order'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            $projectIds = $validated['projects'] ?? [];
            if ($newProjectName !== '') {
                $projectIds[] = Project::create([
                    'name' => $newProjectName,
                    'code' => $validated['new_project']['code'] ?? null,
                    'status' => $validated['new_project']['status'] ?? 'active',
                    'sort_order' => $validated['new_project']['sort_order'],
                ])->id;
            }

            $this->syncProjects($path, $projectIds);
        });

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
        $query = ProjectPath::with(['projects' => fn ($q) => $q
            ->when($request->filled('status'), fn ($s) => $s->where('status', $request->status))
            ->ordered()])
            ->ordered();

        if ($request->filled('status')) {
            $query->whereHas('projects', fn ($q) => $q->where('status', $request->status));
        }

        $paths = $query->get();
        $orphanProjects = Project::whereNull('path_id')->ordered()->get();
        $statuses = Project::STATUSES;
        $statusCounts = Project::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.paths.tree', compact('paths', 'orphanProjects', 'statuses', 'statusCounts'));
    }

    public function exportExcel(Request $request)
    {
        $columns = $this->columnsQuery($request);
        if (! $request->boolean('include_orphans')) {
            $columns = $columns->reject(fn (array $column) => $column['orphan'] ?? false)->values();
        }

        return Excel::download(
            new ProjectPathsExport($columns),
            'paths-projects-' . now()->format('Y_m_d') . '.xlsx',
        );
    }

    /*
     * عمود لكل مسار (تنسيق «قائمة المشاريع» المرجعي). كل عنصر: اسم/كود/حالة خام لتستفيد
     * منه صفحة الطباعة (شارات ملونة) وتصدير Excel (سطر نصي) معاً.
     */
    private function columnsQuery(Request $request): \Illuminate\Support\Collection
    {
        $status = $request->filled('status') ? $request->status : null;

        $map = fn ($projects) => $projects
            ->when($status, fn ($p) => $p->where('status', $status))
            ->map(fn (Project $project) => [
                'name' => $project->name,
                'code' => $project->code,
                'sort_order' => $project->sort_order,
                'status' => $project->statusLabel(),
                'status_key' => $project->status,
            ])
            ->values()
            ->all();

        $query = ProjectPath::with(['projects' => fn ($q) => $q->ordered()])
            ->ordered();

        if ($status) {
            $query->whereHas('projects', fn ($q) => $q->where('status', $status));
        }

        $columns = $query->get()->map(fn (ProjectPath $path) => [
            'title' => $path->name,
            'sort_order' => $path->sort_order,
            'code' => $path->code,
            'orphan' => false,
            'items' => $map($path->projects),
        ]);

        $orphans = $map(Project::whereNull('path_id')->ordered()->get());

        if ($orphans) {
            $columns->push(['title' => 'مشاريع بدون مسار', 'sort_order' => null, 'code' => null, 'orphan' => true, 'items' => $orphans]);
        }

        return $columns->filter(fn (array $col) => $col['items'] !== [])->values();
    }

    public function exportPdf(Request $request)
    {
        $columns = $this->columnsQuery($request);
        $orphanColumn = $columns->firstWhere('orphan', true);
        $orphanCount = count($orphanColumn['items'] ?? []);
        $includeOrphans = $request->boolean('include_orphans');

        if (! $includeOrphans) {
            $columns = $columns->reject(fn (array $column) => $column['orphan'] ?? false)->values();
        }

        return view('admin.paths.print', compact('columns', 'orphanCount', 'includeOrphans'));
    }
}
