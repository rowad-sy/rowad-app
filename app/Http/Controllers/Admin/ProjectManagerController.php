<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\ProjectActivity;
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

        $pendingPricingCount = (clone $prQuery)->whereIn('status', ['review', 'approved1', 'approved2'])->count();
        $awaitingMySignCount = (clone $prQuery)
            ->where(fn ($q) => $q
                ->where(fn ($s) => $s->where('status', 'review')->where('refer_to_approver1_id', auth()->id()))
                ->orWhere(fn ($s) => $s->where('status', 'approved1')->where('refer_to_approver2_id', auth()->id()))
                ->orWhere(fn ($s) => $s->where('status', 'approved2')->where('refer_to_approver3_id', auth()->id()))
                ->orWhere(fn ($s) => $s->where('status', 'approved')->where('refer_to_logistics_id', auth()->id())))
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

        $recentActivities = ProjectActivity::query()
            ->with(['project', 'creator'])
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('activity_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        $scopeCenterId = $centerId;
        $scopeProjectId = $projectId;
        $scopeCohortId = $cohortId;
        $scopeCenter = $employee?->center;
        $scopeProject = $employee?->project;
        $scopeCohort = $employee?->cohort;

        // بطاقات الفعاليات الخاصة بنطاق مدير المشروع
        $eventCardsCount = \App\Models\Admin\EventCard::query()
            ->where('created_by', auth()->id())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->count();
        $recentEventCards = \App\Models\Admin\EventCard::query()
            ->with(['project', 'referredUser'])
            ->where('created_by', auth()->id())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->limit(6)
            ->get();
        $awaitingMyEventCards = \App\Models\Admin\EventCard::query()
            ->with(['project', 'creator'])
            ->where('status', 'review')
            ->whereHas('activeReferrals', fn ($r) => $r->where('to_user_id', auth()->id()))
            ->orderBy('event_date')
            ->limit(6)
            ->get();

        return view('admin.project-manager.dashboard', compact(
            'studentsCount', 'tasksCount', 'myTasksCount', 'trainingPlansCount',
            'pendingPricingCount', 'awaitingMySignCount', 'approvedCount', 'executedCount',
            'recentPurchaseRequests', 'statusCounts', 'recentActivities',
            'scopeCenterId', 'scopeProjectId', 'scopeCohortId',
            'scopeCenter', 'scopeProject', 'scopeCohort',
            'eventCardsCount', 'recentEventCards', 'awaitingMyEventCards'
        ));
    }
}