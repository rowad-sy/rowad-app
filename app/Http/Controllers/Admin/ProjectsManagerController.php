<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectTask;
use App\Models\Admin\Student\Student;
use App\Models\WorkflowAction;
use Illuminate\Support\Facades\DB;

class ProjectsManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.projects-manager.dashboard,view');
    }

    /*
     * لوحة مدير المشاريع (تختلف عن لوحة مدير المشروع):
     * مدير المشاريع مسؤول عن جميع المشاريع وعن جميع مدراء المشاريع،
     * لذا النظرة هنا شاملة غير مقيدة بنطاق موظف معيّن.
     */
    public function dashboard()
    {
        $projectsCount = Project::count();
        $centersCount = Center::count();
        $studentsCount = Student::count();

        // ── المهام والتقويم ──
        $tasksByStatus = ProjectTask::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tasksTotal = $tasksByStatus->sum();
        $tasksDelayed = ProjectTask::where('status', 'delayed')->count();
        $recentTasks = ProjectTask::with(['assignedTo', 'createdBy', 'center'])
            ->orderBy('start_date', 'desc')
            ->limit(6)
            ->get();

        // أحداث التقويم القادمة (هذا الشهر والقادم)
        $upcomingTasks = ProjectTask::with(['assignedTo', 'createdBy', 'center'])
            ->whereDate('end_date', '>=', now()->startOfMonth())
            ->whereDate('start_date', '<=', now()->endOfMonth()->addMonth())
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        // ── خطط الحركة ──
        $movementStatusCounts = MovementPlan::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $movementTotal = $movementStatusCounts->sum();
        $movementAwaitingReview = MovementPlan::with(['creator', 'center', 'project'])
            ->withCount('entries')
            ->where('status', 'review')
            ->orderBy('plan_month')
            ->limit(8)
            ->get();

        // ── الخطط الإعلامية ──
        $mediaPlansTotal = MediaPlan::count();
        $mediaPlansThisMonth = MediaPlan::whereYear('month_date', now()->year)
            ->whereMonth('month_date', now()->month)
            ->count();
        $recentMediaPlans = MediaPlan::with(['center', 'project', 'creator'])
            ->withCount('events')
            ->orderBy('month_date', 'desc')
            ->limit(6)
            ->get();

        // ── الوثائق (تغييرات الوثائق + قيد المراجعة) ──
        $documentsUnderReview = AnnexDocument::with(['template', 'project', 'creator'])
            ->where('status', 'under_review')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();
        $documentsUnderReviewCount = AnnexDocument::where('status', 'under_review')->count();

        // آخر التغييرات على وثائق المشاريع (تحديث/إرسال من مدراء المشاريع)
        $recentDocChanges = WorkflowAction::with(['fromUser', 'workable'])
            ->where('workable_type', AnnexDocument::class)
            ->whereIn('action', ['update', 'submit'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // ── طلبات الشراء ──
        $prQuery = PurchaseRequest::query();
        $prStatusCounts = (clone $prQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $prTotal = $prStatusCounts->sum();
        $prAwaitingPm2Sign = (clone $prQuery)
            ->where('status', 'review')
            ->where('refer_to_approver1_id', auth()->id())
            ->count();
        // نفس شرط العدّاد أعلاه (محالة إلى المستخدم الحالي وحالتها بانتظار موافقته الأولى) لعرض العناصر نفسها
        $awaitingMyPm2Requests = (clone $prQuery)
            ->with(['user', 'center', 'project', 'items'])
            ->where('status', 'review')
            ->where('refer_to_approver1_id', auth()->id())
            ->orderBy('pr_date')
            ->limit(8)
            ->get();
        $recentPurchaseRequests = (clone $prQuery)
            ->with(['user', 'center', 'project', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // ── فريق مدراء المشاريع ──
        $projectManagers = Employee::with(['user', 'project', 'center'])
            ->whereNotNull('project_id')
            ->orderBy('project_id')
            ->take(20)
            ->get();
        $projectManagersCount = Employee::whereNotNull('project_id')->count();

        return view('admin.projects-manager.dashboard', compact(
            'projectsCount', 'centersCount', 'studentsCount',
            'tasksByStatus', 'tasksTotal', 'tasksDelayed', 'recentTasks', 'upcomingTasks',
            'movementStatusCounts', 'movementTotal', 'movementAwaitingReview',
            'mediaPlansTotal', 'mediaPlansThisMonth', 'recentMediaPlans',
            'documentsUnderReview', 'documentsUnderReviewCount', 'recentDocChanges',
            'prStatusCounts', 'prTotal', 'prAwaitingPm2Sign', 'awaitingMyPm2Requests', 'recentPurchaseRequests',
            'projectManagers', 'projectManagersCount'
        ));
    }
}