<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\ProjectTask;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\TrainingPlan;
use Illuminate\Support\Facades\DB;

class ProjectManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.project-manager.dashboard,view');
    }

    /*
     * لوحة مدير المشروع (الدراسة القسم 4.1/4.3):
     * النطاق مشتق من سجل الموظف hr_employees (center/project/cohort).
     */
    public function dashboard()
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        $centerId = $employee?->center_id;
        $projectId = $employee?->project_id;
        $cohortId = $employee?->cohort_id;

        $studentsCount = Student::query()
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($cohortId, fn ($q) => $q->where('cohort_id', $cohortId))
            ->count();

        $tasksCount = ProjectTask::query()
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->count();

        $myTasksCount = ProjectTask::query()
            ->where(function ($q) {
                $q->where('assigned_to', auth()->id())
                    ->orWhere('created_by', auth()->id());
            })
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->count();

        $trainingPlansCount = TrainingPlan::query()
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->count();

        $prQuery = PurchaseRequest::query()
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId));

        $pendingPricingCount = (clone $prQuery)->where('status', 'pending')->count();
        $awaitingMySignCount = (clone $prQuery)
            ->where('status', 'priced')
            ->where('refer_to_direct_manager_id', auth()->id())
            ->count();
        $approvedCount = (clone $prQuery)->where('status', 'approved')->count();
        $executedCount = (clone $prQuery)->where('status', 'executed')->count();

        $recentPurchaseRequests = (clone $prQuery)
            ->with(['user', 'center', 'project', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $statusCounts = (clone $prQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $scopeCenterId = $centerId;
        $scopeProjectId = $projectId;
        $scopeCohortId = $cohortId;
        $scopeCenter = $employee?->center;
        $scopeProject = $employee?->project;
        $scopeCohort = $employee?->cohort;

        return view('admin.project-manager.dashboard', compact(
            'studentsCount', 'tasksCount', 'myTasksCount', 'trainingPlansCount',
            'pendingPricingCount', 'awaitingMySignCount', 'approvedCount', 'executedCount',
            'recentPurchaseRequests', 'statusCounts',
            'scopeCenterId', 'scopeProjectId', 'scopeCohortId',
            'scopeCenter', 'scopeProject', 'scopeCohort'
        ));
    }
}