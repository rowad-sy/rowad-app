@extends('admin.layouts.master')

@section('title', 'إحصائيات التقنية')

@push('styles')
<style>
    .stat-card-lg { border-radius: 12px; border: none; }
    .stat-card-lg .card-body { padding: 1.25rem; }
    .stat-card-lg .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-card-lg h3 { margin: 0; font-weight: 700; }
    .chart-container { position: relative; height: 250px; }
    .chart-container-sm { position: relative; height: 200px; }
    .table-sm-custom { font-size: 0.85rem; }
    .table-sm-custom td, .table-sm-custom th { padding: 0.4rem 0.75rem; }
    .progress-thin { height: 6px; border-radius: 3px; }
</style>
@endpush

@section('content')
<x-page-header :title="'إحصائيات التقنية'" :description="'نظرة شاملة على التذاكر والمعدات التقنية'"
               :breadcrumb="[['label' => 'التقنية'], ['label' => 'إحصائيات التقنية']]">
    @if (array_filter($filters))
    <div>
        <a href="{{ route('admin.tech.statistics') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> إلغاء الفلاتر
        </a>
    </div>
    @endif
</x-page-header>

{{-- ─── Filter Form ─── --}}
<form method="GET" action="{{ route('admin.tech.statistics') }}" class="mb-4">
    <div class="table-container">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> الفلاتر</h6>
            <small class="text-muted">تصفية الإحصائيات حسب المعايير أدناه</small>
        </div>
        <div class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">المركز</label>
                    <select name="center_id" class="form-select form-select-sm">
                        <option value="">كل المراكز</option>
                        @foreach ($centers as $c)
                            <option value="{{ $c->id }}" {{ ($filters['center_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">المشروع</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" {{ ($filters['project_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i> تطبيق
                    </button>
                    <a href="{{ route('admin.tech.statistics') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> إعادة تعيين
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- ─── Summary Cards ─── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi label="إجمالي التذاكر" :value="$totalIssues" icon="bi-ticket" tone="brand" /></div>
    <div class="col-6 col-md-3"><x-kpi label="عاجلة + مرتفعة" :value="$urgentIssues + $highIssues" icon="bi-exclamation-triangle" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi label="مكتملة" :value="$completedIssues" icon="bi-check-circle" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi label="إجمالي المعدات" :value="$totalEquipment" icon="bi-pc-display" tone="info" /></div>
</div>

{{-- ─── Charts Row 1: Issue Stats ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i> توزيع حالة التذاكر</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="statusChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small flex-wrap">
                    @foreach ($statusLabels as $i => $label)
                        <span><span class="badge" style="background:{{ $statusColors[$i] }};">&nbsp;</span> {{ $label }}: {{ $statusData[$i] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-flag me-1"></i> توزيع الأولوية</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="priorityChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small flex-wrap">
                    @foreach ($priorityLabels as $i => $label)
                        <span><span class="badge" style="background:{{ ['#6c757d','#0dcaf0','#ffc107','#dc3545'][$i] }};">&nbsp;</span> {{ $label }}: {{ $priorityData[$i] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> التذاكر حسب المركز</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="centerIssueChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>المركز</th><th>العدد</th></tr></thead>
                    <tbody>
                        @foreach ($centerIssueStats as $cs)
                            <tr><td>{{ $cs->name }}</td><td>{{ $cs->total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ─── Charts Row 2: Equipment Stats ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-box me-1"></i> توزيع أنواع المعدات</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="typeChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>النوع</th><th>العدد</th></tr></thead>
                    <tbody>
                        @foreach ($typeLabels as $i => $label)
                            <tr><td>{{ $label }}</td><td>{{ $typeData[$i] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-clipboard-check me-1"></i> توزيع الحالة الفنية</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="conditionChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small flex-wrap">
                    @foreach ($conditionLabels as $i => $label)
                        <span><span class="badge" style="background:{{ ['#198754','#0d6efd','#ffc107','#dc3545','#212529'][$i] }};">&nbsp;</span> {{ $label }}: {{ $conditionData[$i] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> المعدات حسب المركز</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="centerEquipChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>المركز</th><th>العدد</th></tr></thead>
                    <tbody>
                        @foreach ($centerEquipStats as $cs)
                            <tr><td>{{ $cs->name }}</td><td>{{ $cs->total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.querySelector('html').getAttribute('data-bs-theme') === 'dark';
    const textColor = isDark ? '#e2e8f0' : '#475569';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($statusLabels) !!},
            datasets: [{ data: {!! json_encode($statusData) !!}, backgroundColor: {!! json_encode($statusColors) !!}, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('priorityChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($priorityLabels) !!},
            datasets: [{ data: {!! json_encode($priorityData) !!}, backgroundColor: ['#6c757d','#0dcaf0','#ffc107','#dc3545'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('centerIssueChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($centerIssueLabels) !!},
            datasets: [{ data: {!! json_encode($centerIssueData) !!}, backgroundColor: 'rgba(13, 110, 253, 0.7)', borderColor: '#0d6efd', borderWidth: 1 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($typeLabels) !!},
            datasets: [{ data: {!! json_encode($typeData) !!}, backgroundColor: 'rgba(13, 202, 240, 0.7)', borderColor: '#0dcaf0', borderWidth: 1 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('conditionChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($conditionLabels) !!},
            datasets: [{ data: {!! json_encode($conditionData) !!}, backgroundColor: ['#198754','#0d6efd','#ffc107','#dc3545','#212529'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('centerEquipChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($centerEquipLabels) !!},
            datasets: [{ data: {!! json_encode($centerEquipData) !!}, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderColor: '#198754', borderWidth: 1 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
