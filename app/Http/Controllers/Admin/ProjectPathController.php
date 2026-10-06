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
        $query = ProjectPath::with(['projects' => fn ($q) => $q
            ->when($request->filled('status'), fn ($s) => $s->where('status', $request->status))
            ->orderBy('name')])
            ->orderBy('id');

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

    public function exportExcel(Request $request)
    {
        $columns = $this->columnsQuery($request);
        $width = $columns->max(fn (array $col) => count($col['items'])) ?: 0;

        $headings = $columns->map(fn (array $col) => $col['title'])->all();
        $rows = [];

        for ($i = 0; $i < $width; $i++) {
            $rows[] = $columns->map(function (array $col) use ($i) {
                $item = $col['items'][$i] ?? null;

                return $item ? sprintf('%d. %s%s — %s', $i + 1, $item['name'], $item['code'] ? ' ('.$item['code'].')' : '', $item['status']) : '';
            })->all();
        }

        $columns_closures = array_map(
            fn ($index) => fn (array $row) => $row[$index] ?? '',
            array_keys($headings)
        );

        return Excel::download(
            new BaseExport(collect($rows), $headings, $columns_closures),
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
                'status' => $project->statusLabel(),
                'status_key' => $project->status,
            ])
            ->values()
            ->all();

        $query = ProjectPath::with(['projects' => fn ($q) => $q->orderBy('name')])
            ->orderBy('id');

        if ($status) {
            $query->whereHas('projects', fn ($q) => $q->where('status', $status));
        }

        $columns = $query->get()->map(fn (ProjectPath $path) => [
            'title' => trim($path->name . ' (' . ($path->code ?? '') . ')'),
            'code' => $path->code,
            'items' => $map($path->projects),
        ]);

        $orphans = $map(Project::whereNull('path_id')->orderBy('name')->get());

        if ($orphans) {
            $columns->push(['title' => 'مشاريع بدون مسار', 'code' => null, 'items' => $orphans]);
        }

        return $columns->filter(fn (array $col) => $col['items'] !== [])->values();
    }

    public function exportPdf(Request $request)
    {
        $columns = $this->columnsQuery($request);

        return view('admin.paths.print', compact('columns'));
    }
}
