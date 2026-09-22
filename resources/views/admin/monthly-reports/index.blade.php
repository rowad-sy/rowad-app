@extends('admin.layouts.master')

@section('title', 'التقارير الشهرية')

@section('content')
<div class="page-header">
    <h4>التقارير الشهرية</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / التقارير الشهرية
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="status" class="form-select" style="width: auto;">
            <option value="">كل الحالات</option>
            @foreach (\App\Models\Admin\MonthlyReports\MonthlyReport::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ ($status ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="project_id" class="form-select" style="width: auto;">
            <option value="">كل المشاريع</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ ($projectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary">تصفية</button>
    </form>
    @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'view')
    <a href="{{ route('admin.monthly-reports.templates.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-collection"></i> القوالب
    </a>
    @endcanPermission
    @canPermission('App\Models\Admin\MonthlyReports\MonthlyReport', 'create')
    <a href="{{ route('admin.monthly-reports.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> إضافة تقرير
    </a>
    @endcanPermission
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>التقرير</th>
                    <th>القالب</th>
                    <th>المشروع</th>
                    <th>الفترة</th>
                    <th>الحالة</th>
                    <th>المنشئ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                <tr>
                    <td>
                        <a href="{{ route('admin.monthly-reports.show', $report) }}" class="fw-semibold text-decoration-none">
                            {{ $report->title ?: ($report->template->title_ar ?? 'تقرير') }}
                        </a>
                        <div class="text-muted small" dir="ltr">#{{ $report->id }} · V{{ $report->template_version }}</div>
                    </td>
                    <td>{{ $report->template?->title_ar }}</td>
                    <td>{{ $report->project?->name ?? '—' }}</td>
                    <td>{{ $report->period ?? '—' }}</td>
                    <td>
                        @php $badge = [
                            'draft' => 'secondary',
                            'under_review' => 'warning text-dark',
                            'approved' => 'success',
                            'rejected' => 'danger',
                        ][$report->status] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $badge }}">{{ \App\Models\Admin\MonthlyReports\MonthlyReport::STATUSES[$report->status] ?? $report->status }}</span>
                    </td>
                    <td>{{ $report->creator?->name }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.monthly-reports.show', $report) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                            <i class="bi bi-eye"></i>
                        </a>
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReport', 'edit')
                        @if ($report->status !== 'approved')
                        <a href="{{ route('admin.monthly-reports.edit', $report) }}" class="btn btn-sm btn-outline-primary" title="تعبئة/تعديل">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                        @endcanPermission
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReport', 'view')
                        <a href="{{ route('admin.monthly-reports.print', $report) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="طباعة A4">
                            <i class="bi bi-printer"></i>
                        </a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReport', 'create')
                        <form method="POST" action="{{ route('admin.monthly-reports.duplicate', $report) }}" class="d-inline"
                              onsubmit="return confirm('إنشاء نسخة جديدة من هذا التقرير بكل محتواه لتعديلها؟')">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" title="نسخ كتقرير جديد">
                                <i class="bi bi-files"></i>
                            </button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد تقارير بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $reports->links() }}</div>
@endsection