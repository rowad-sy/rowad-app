<?php

namespace App\Http\Controllers\Admin\Tech;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechEquipment;
use App\Models\Admin\Tech\TechIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TechStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Tech\TechIssue,view');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['center_id', 'project_id']);
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        $cacheKey = 'tech_stats_' . md5(serialize($filters));
        $cacheTtl = $hasFilters ? 0 : 300;

        $data = $hasFilters
            ? $this->computeStats($filters)
            : Cache::remember($cacheKey, $cacheTtl, fn () => $this->computeStats($filters));

        $data['filters'] = $filters;
        $data['centers'] = Center::orderBy('name')->get(['id', 'name']);
        $data['projects'] = Project::orderBy('name')->get(['id', 'name']);

        return view('admin.tech.statistics', $data);
    }

    private function computeStats(array $filters): array
    {
        $issueQuery = TechIssue::query()
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('center_id', $filters['center_id']))
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('project_id', $filters['project_id']));

        $equipmentQuery = TechEquipment::query()
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('center_id', $filters['center_id']))
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('project_id', $filters['project_id']));

        // Summary
        $totalIssues = (clone $issueQuery)->count();
        $openIssues = (clone $issueQuery)->where('status', 'open')->count();
        $inProgressIssues = (clone $issueQuery)->where('status', 'in_progress')->count();
        $completedIssues = (clone $issueQuery)->where('status', 'completed')->count();
        $blockedIssues = (clone $issueQuery)->where('status', 'blocked')->count();
        $urgentIssues = (clone $issueQuery)->where('priority', 'urgent')->count();
        $highIssues = (clone $issueQuery)->where('priority', 'high')->count();

        $totalEquipment = (clone $equipmentQuery)->count();

        // Issue status distribution
        $statusLabels = ['مفتوحة', 'قيد التنفيذ', 'مكتملة', 'مغلقة'];
        $statusData = [$openIssues, $inProgressIssues, $completedIssues, $blockedIssues];
        $statusColors = ['#0d6efd', '#ffc107', '#198754', '#dc3545'];

        // Issue priority distribution
        $priorityStats = (clone $issueQuery)
            ->selectRaw("priority, COUNT(*) as total")
            ->groupBy('priority')
            ->pluck('total', 'priority');
        $priorityMap = ['low' => 'منخفض', 'medium' => 'متوسط', 'high' => 'مرتفع', 'urgent' => 'عاجل'];
        $priorityLabels = [];
        $priorityData = [];
        foreach ($priorityMap as $key => $label) {
            $priorityLabels[] = $label;
            $priorityData[] = (int) ($priorityStats[$key] ?? 0);
        }

        // Issues by center
        $centerIssueStats = Center::select('centers.id', 'centers.name')
            ->selectRaw('COUNT(tech_issues.id) as total')
            ->leftJoin('tech_issues', 'centers.id', '=', 'tech_issues.center_id')
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('tech_issues.project_id', $filters['project_id']))
            ->groupBy('centers.id', 'centers.name')
            ->orderByDesc('total')
            ->get();
        $centerIssueLabels = $centerIssueStats->pluck('name')->toArray();
        $centerIssueData = $centerIssueStats->pluck('total')->toArray();

        // Equipment type distribution
        $typeStats = (clone $equipmentQuery)
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();
        $typeLabels = $typeStats->pluck('type')->toArray();
        $typeData = $typeStats->pluck('total')->toArray();

        // Equipment condition distribution
        $conditionStats = (clone $equipmentQuery)
            ->selectRaw("condition, COUNT(*) as total")
            ->groupBy('condition')
            ->pluck('total', 'condition');
        $conditionMap = ['a' => 'ممتاز', 'b' => 'جيد', 'c' => 'متوسط', 'd' => 'سيئ', 'e' => 'تالف'];
        $conditionLabels = [];
        $conditionData = [];
        foreach ($conditionMap as $key => $label) {
            $conditionLabels[] = $label;
            $conditionData[] = (int) ($conditionStats[$key] ?? 0);
        }

        // Equipment by center
        $centerEquipStats = Center::select('centers.id', 'centers.name')
            ->selectRaw('COUNT(tech_equipment.id) as total')
            ->leftJoin('tech_equipment', 'centers.id', '=', 'tech_equipment.center_id')
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('tech_equipment.project_id', $filters['project_id']))
            ->groupBy('centers.id', 'centers.name')
            ->orderByDesc('total')
            ->get();
        $centerEquipLabels = $centerEquipStats->pluck('name')->toArray();
        $centerEquipData = $centerEquipStats->pluck('total')->toArray();

        return compact(
            'totalIssues', 'openIssues', 'inProgressIssues', 'completedIssues', 'blockedIssues',
            'urgentIssues', 'highIssues',
            'totalEquipment',
            'statusLabels', 'statusData', 'statusColors',
            'priorityLabels', 'priorityData',
            'centerIssueLabels', 'centerIssueData', 'centerIssueStats',
            'typeLabels', 'typeData',
            'conditionLabels', 'conditionData',
            'centerEquipLabels', 'centerEquipData', 'centerEquipStats',
        );
    }
}
