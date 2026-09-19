@extends('admin.layouts.master')

@section('title', 'صفحة مشروع: ' . $project->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="text-muted small fw-bold mb-1"><i class="bi bi-briefcase me-1"></i> صفحة مشروع</div>
        <h4 class="mb-1">{{ $project->name }}</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span>
            @if ($project->code)
                <span class="badge text-bg-light border">الكود: {{ $project->code }}</span>
            @endif
            @if ($project->path)
                <span class="badge text-bg-light border"><i class="bi bi-signpost-split me-1"></i>{{ $project->path->name }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.paths.tree') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-diagram-2 me-1"></i> الشجرة</a>
        @canPermission('App\Models\Admin\Project', 'edit')
            <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i> تعديل</a>
        @endcanPermission
    </div>
</div>

@if ($project->description)
    <div class="alert" style="background: var(--color-badge-bg); color: var(--color-text-main); border: 1px solid var(--color-border);">
        {{ $project->description }}
    </div>
@endif

<p class="text-muted small">
    <i class="bi bi-funnel me-1"></i> كل الأزرار أدناه تفتح الصفحات المقابلة مفلترة على هذا المشروع.
    الفلتر الزمني الافتراضي: <strong>{{ \Illuminate\Support\Carbon::create($year, $month)->translatedFormat('F Y') }}</strong> (يمكن تغييره داخل كل صفحة).
</p>

<div class="row g-3">
    @php
        $pid = $project->id;
        $tiles = [
            ['label' => 'الطلاب', 'icon' => 'bi-mortarboard', 'count' => $counts['students'], 'route' => route('admin.students.index', ['project_id' => $pid])],
            ['label' => 'وثائق المشروع', 'icon' => 'bi-file-earmark-text', 'count' => $counts['documents'], 'route' => route('admin.project-docs.documents.index', ['project_id' => $pid])],
            ['label' => 'التقرير الشهري', 'icon' => 'bi-calendar-month', 'count' => $counts['monthly_reports'], 'route' => route('admin.monthly-reports.index', ['project_id' => $pid])],
            ['label' => 'الخطة الإعلامية', 'icon' => 'bi-megaphone', 'count' => $counts['media_plans'], 'route' => route('admin.media-plans.index', ['project_id' => $pid, 'month' => $month, 'year' => $year])],
            ['label' => 'خطط الحركة', 'icon' => 'bi-truck', 'count' => $counts['movement_plans'], 'route' => route('admin.movement-plans.index', ['project_id' => $pid])],
            ['label' => 'طلبات الشراء', 'icon' => 'bi-cart', 'count' => $counts['purchase_requests'], 'route' => route('admin.logistics.purchase-requests.index', ['project_id' => $pid])],
            ['label' => 'بطاقات الفعاليات', 'icon' => 'bi-calendar-event', 'count' => $counts['event_cards'], 'route' => route('admin.event-cards.index', ['project_id' => $pid])],
            ['label' => 'إحصائيات المهام', 'icon' => 'bi-bar-chart', 'count' => null, 'route' => route('admin.projects.statistics')],
        ];
    @endphp
    @foreach ($tiles as $tile)
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ $tile['route'] }}" class="hub-tile">
                <i class="bi {{ $tile['icon'] }}"></i>
                <span>{{ $tile['label'] }}</span>
                @if (!is_null($tile['count']))
                    <span class="hub-count">{{ $tile['count'] }} عنصر</span>
                @endif
            </a>
        </div>
    @endforeach
</div>
@endsection
