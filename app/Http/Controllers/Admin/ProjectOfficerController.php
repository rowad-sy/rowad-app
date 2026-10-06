<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\ProjectActivity;
use App\Models\Admin\Student\Student;
use Illuminate\Support\Facades\DB;

class ProjectOfficerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.project-officer.dashboard,view');
    }

    /*
     * لوحة مسؤول المشروع (الدراسة 4.4–4.6 + 19.6):
     * كل ما يعرضه مقيد بنطاقه (center/project/cohort) المشتق من hr_employees.
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

        $todayAttendance = \App\Models\Admin\Student\Attendance::query()
            ->when($cohortId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('cohort_id', $cohortId)))
            ->when($centerId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('center_id', $centerId)))
            ->whereDate('date', today())
            ->count();

        $myRequests = PurchaseRequest::query()
            ->where('user_id', auth()->id());

        $myRequestsCount = (clone $myRequests)->count();
        $myPendingPricing = (clone $myRequests)->where('status', 'review')->count();
        $myPriced = (clone $myRequests)->where('status', 'approved1')->count();
        $myInCycle = (clone $myRequests)
            ->whereIn('status', ['approved1', 'approved2', 'approved'])
            ->count();
        $myApproved = (clone $myRequests)->where('status', 'approved')->count();
        $myRejected = (clone $myRequests)->where('status', 'rejected')->count();

        $myRecentRequests = (clone $myRequests)
            ->with(['user', 'center', 'project', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $statusCounts = (clone $myRequests)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $myRecentActivities = ProjectActivity::query()
            ->with(['project', 'center', 'creator'])
            ->where(fn ($q) => $q->where('created_by', auth()->id())
                ->when($centerId, fn ($qc) => $qc->orWhere('center_id', $centerId))
                ->when($projectId, fn ($qp) => $qp->orWhere('project_id', $projectId)))
            ->orderBy('activity_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        $myActivitiesCount = ProjectActivity::query()
            ->where('created_by', auth()->id())
            ->count();

        $scopeCenterId = $centerId;
        $scopeProjectId = $projectId;
        $scopeCohortId = $cohortId;
        $scopeCenter = $employee?->center;
        $scopeProject = $employee?->project;
        $scopeCohort = $employee?->cohort;

        return view('admin.project-officer.dashboard', compact(
            'studentsCount', 'todayAttendance',
            'myRequestsCount', 'myPendingPricing', 'myPriced', 'myInCycle', 'myApproved', 'myRejected',
            'myRecentRequests', 'statusCounts', 'myRecentActivities', 'myActivitiesCount',
            'scopeCenterId', 'scopeProjectId', 'scopeCohortId',
            'scopeCenter', 'scopeProject', 'scopeCohort'
        ));
    }
}