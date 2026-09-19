<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\EventCard;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MonthlyReports\MonthlyReport;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Student\Student;

class ProjectHubController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Project,view');
    }

    /*
     * صفحة مشروع مخصصة: كل ما يخص المشروع مفلتراً عليه بلمسة واحدة
     * (طلاب، وثائق، تقارير شهرية، خطط إعلامية وحركة، طلبات شراء، بطاقات فعاليات).
     */
    public function show(Project $project)
    {
        $project->load(['path', 'centers']);

        $month = now()->month;
        $year = now()->year;

        $counts = [
            'students' => $this->studentsQuery($project)->count(),
            'documents' => AnnexDocument::where('project_id', $project->id)->count(),
            'monthly_reports' => MonthlyReport::where('project_id', $project->id)->count(),
            'media_plans' => MediaPlan::where('project_id', $project->id)->count(),
            'media_plans_month' => MediaPlan::where('project_id', $project->id)
                ->whereYear('month_date', $year)->whereMonth('month_date', $month)->count(),
            'movement_plans' => MovementPlan::where('project_id', $project->id)->count(),
            'purchase_requests' => PurchaseRequest::where('project_id', $project->id)->count(),
            'event_cards' => EventCard::where('project_id', $project->id)->count(),
        ];

        return view('admin.projects.hub', compact('project', 'counts', 'month', 'year'));
    }

    private function studentsQuery(Project $project)
    {
        return Student::where(function ($q) use ($project) {
            $q->where('project_id', $project->id)
                ->orWhereHas('projects', fn ($p) => $p->where('projects.id', $project->id));
        });
    }
}
