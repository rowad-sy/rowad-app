<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\Employee,view');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['center_id', 'project_id', 'department_id']);
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        $cacheKey = 'hr_stats_' . md5(serialize($filters));
        $cacheTtl = $hasFilters ? 0 : 300;

        $data = $hasFilters
            ? $this->computeStats($filters)
            : Cache::remember($cacheKey, $cacheTtl, fn () => $this->computeStats($filters));

        $data['filters'] = $filters;
        $data['centers'] = Center::orderBy('name')->get(['id', 'name']);
        $data['projects'] = Project::orderBy('name')->get(['id', 'name']);
        $data['departments'] = Department::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar']);

        return view('admin.hr.statistics', $data);
    }

    private function computeStats(array $filters): array
    {
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        $base = Employee::query()
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('center_id', $filters['center_id']))
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('project_id', $filters['project_id']))
            ->when(!empty($filters['department_id']), fn ($q) => $q->where('department_id', $filters['department_id']));

        // Summary
        $total = (clone $base)->count();
        $active = (clone $base)->where('status', 'active')->count();
        $inactive = (clone $base)->where('status', 'inactive')->count();
        $male = (clone $base)->where('gender', 'male')->count();
        $female = (clone $base)->where('gender', 'female')->count();

        // Status
        $statusLabels = ['نشط', 'غير نشط'];
        $statusData = [$active, $inactive];
        $statusColors = ['#198754', '#6c757d'];

        // Gender
        $genderLabels = ['ذكر', 'أنثى'];
        $genderData = [$male, $female];
        $genderColors = ['#0d6efd', '#d63384'];

        // Marital status
        $maritalStats = (clone $base)
            ->selectRaw("marital_status, COUNT(*) as total")
            ->whereNotNull('marital_status')
            ->groupBy('marital_status')
            ->pluck('total', 'marital_status');
        $maritalMap = ['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'];
        $maritalLabels = [];
        $maritalData = [];
        foreach ($maritalMap as $key => $label) {
            $count = (int) ($maritalStats[$key] ?? 0);
            if ($count > 0 || ! $hasFilters) {
                $maritalLabels[] = $label;
                $maritalData[] = $count;
            }
        }

        // Center distribution
        $centerStats = Center::select('centers.id', 'centers.name')
            ->selectRaw('COUNT(hr_employees.id) as total')
            ->leftJoin('hr_employees', 'centers.id', '=', 'hr_employees.center_id')
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('hr_employees.project_id', $filters['project_id']))
            ->when(!empty($filters['department_id']), fn ($q) => $q->where('hr_employees.department_id', $filters['department_id']))
            ->groupBy('centers.id', 'centers.name')
            ->orderByDesc('total')
            ->get();
        $centerLabels = $centerStats->pluck('name')->toArray();
        $centerData = $centerStats->pluck('total')->toArray();

        // Department distribution
        $deptStats = Department::select('departments.id', 'departments.name_ar')
            ->selectRaw('COUNT(hr_employees.id) as total')
            ->leftJoin('hr_employees', 'departments.id', '=', 'hr_employees.department_id')
            ->when(!empty($filters['center_id']), fn ($q) => $q->where('hr_employees.center_id', $filters['center_id']))
            ->when(!empty($filters['project_id']), fn ($q) => $q->where('hr_employees.project_id', $filters['project_id']))
            ->groupBy('departments.id', 'departments.name_ar')
            ->orderByDesc('total')
            ->get();
        $deptLabels = $deptStats->pluck('name_ar')->toArray();
        $deptData = $deptStats->pluck('total')->toArray();

        // Document completion
        $docFlags = [
            'has_photo' => 'صورة شخصية',
            'has_cv' => 'السيرة الذاتية',
            'has_id_copy' => 'صورة الهوية',
            'has_qualification' => 'المؤهل العلمي',
            'has_contract_doc' => 'العقد',
            'has_offer_letter' => 'عرض العمل',
        ];
        $docLabels = [];
        $docComplete = [];
        $docIncomplete = [];
        foreach ($docFlags as $col => $label) {
            $complete = (clone $base)->where($col, true)->count();
            $docLabels[] = $label;
            $docComplete[] = $complete;
            $docIncomplete[] = $total - $complete;
        }

        return compact(
            'total', 'active', 'inactive', 'male', 'female',
            'statusLabels', 'statusData', 'statusColors',
            'genderLabels', 'genderData', 'genderColors',
            'maritalLabels', 'maritalData',
            'centerLabels', 'centerData', 'centerStats',
            'deptLabels', 'deptData', 'deptStats',
            'docLabels', 'docComplete', 'docIncomplete',
        );
    }
}
