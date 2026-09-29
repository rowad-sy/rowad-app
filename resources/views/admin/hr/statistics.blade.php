@extends('admin.layouts.master')

@section('title', 'إحصائيات الموظفين')

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
<x-page-header :title="'إحصائيات الموظفين'" :description="'نظرة شاملة على بيانات الموظفين'"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'إحصائيات الموظفين']]">
    @if (array_filter($filters))
    <div>
        <a href="{{ route('admin.hr.employees.statistics') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> إلغاء الفلاتر
        </a>
    </div>
    @endif
</x-page-header>

{{-- ─── Filter Form ─── --}}
<form method="GET" action="{{ route('admin.hr.employees.statistics') }}" class="mb-4">
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
                <div class="col-md-4">
                    <label class="form-label small">الإدارة</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">كل الإدارات</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" {{ ($filters['department_id'] ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i> تطبيق
                    </button>
                    <a href="{{ route('admin.hr.employees.statistics') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> إعادة تعيين
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- ─── Summary Cards ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card-lg bg-primary text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-people"></i></div>
                <div>
                    <small class="opacity-75">إجمالي الموظفين</small>
                    <h3>{{ $total }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card-lg bg-success text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-check-circle"></i></div>
                <div>
                    <small class="opacity-75">نشط</small>
                    <h3>{{ $active }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card-lg bg-secondary text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-x-circle"></i></div>
                <div>
                    <small class="opacity-75">غير نشط</small>
                    <h3>{{ $inactive }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card-lg bg-info text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-gender-ambiguous"></i></div>
                <div>
                    <small class="opacity-75">ذكور / إناث</small>
                    <h3>{{ $male }} / {{ $female }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Charts Row 1 ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-gender-ambiguous me-1"></i> توزيع الجنس</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="genderChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small">
                    <span><span class="badge" style="background:#0d6efd;">&nbsp;</span> ذكر: {{ $male }}</span>
                    <span><span class="badge" style="background:#d63384;">&nbsp;</span> أنثى: {{ $female }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i> توزيع الحالة</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="statusChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small">
                    <span><span class="badge" style="background:#198754;">&nbsp;</span> نشط: {{ $active }}</span>
                    <span><span class="badge" style="background:#6c757d;">&nbsp;</span> غير نشط: {{ $inactive }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-heart me-1"></i> الحالة الاجتماعية</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="maritalChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small flex-wrap">
                    @foreach ($maritalLabels as $i => $label)
                        <span><span class="badge" style="background:{{ ['#0d6efd','#198754','#ffc107','#dc3545'][$i] ?? '#6c757d' }};">&nbsp;</span> {{ $label }}: {{ $maritalData[$i] ?? 0 }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Center + Department Distribution ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> توزيع الموظفين حسب المركز</h6></div>
            <div class="p-3">
                <div class="chart-container"><canvas id="centerChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>المركز</th><th>العدد</th><th>النسبة</th></tr></thead>
                    <tbody>
                        @foreach ($centerStats as $cs)
                            <tr>
                                <td>{{ $cs->name }}</td>
                                <td>{{ $cs->total }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-primary" style="width: {{ $total > 0 ? round($cs->total / $total * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $total > 0 ? round($cs->total / $total * 100, 1) : 0 }}%</small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> توزيع الموظفين حسب الإدارة</h6></div>
            <div class="p-3">
                <div class="chart-container"><canvas id="deptChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>الإدارة</th><th>العدد</th><th>النسبة</th></tr></thead>
                    <tbody>
                        @foreach ($deptStats as $ds)
                            <tr>
                                <td>{{ $ds->name_ar }}</td>
                                <td>{{ $ds->total }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-success" style="width: {{ $total > 0 ? round($ds->total / $total * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $total > 0 ? round($ds->total / $total * 100, 1) : 0 }}%</small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ─── Document Completion ─── --}}
<div class="row g-3">
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-file-earmark-check me-1"></i> إنجاز المستندات</h6></div>
            <div class="p-3">
                <div class="chart-container" style="height:260px;"><canvas id="docChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead><tr><th>المستند</th><th>مكتمل</th><th>غير مكتمل</th><th>نسبة الإنجاز</th></tr></thead>
                    <tbody>
                        @foreach ($docLabels as $i => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td><span class="badge bg-success">{{ $docComplete[$i] }}</span></td>
                                <td><span class="badge bg-secondary">{{ $docIncomplete[$i] }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-success" style="width: {{ $total > 0 ? round($docComplete[$i] / $total * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $total > 0 ? round($docComplete[$i] / $total * 100, 1) : 0 }}%</small>
                                    </div>
                                </td>
                            </tr>
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

    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($genderLabels) !!},
            datasets: [{ data: {!! json_encode($genderData) !!}, backgroundColor: {!! json_encode($genderColors) !!}, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($statusLabels) !!},
            datasets: [{ data: {!! json_encode($statusData) !!}, backgroundColor: {!! json_encode($statusColors) !!}, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('maritalChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($maritalLabels) !!},
            datasets: [{ data: {!! json_encode($maritalData) !!}, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('centerChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($centerLabels) !!},
            datasets: [{ data: {!! json_encode($centerData) !!}, backgroundColor: 'rgba(13, 110, 253, 0.7)', borderColor: '#0d6efd', borderWidth: 1 }]
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

    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($deptLabels) !!},
            datasets: [{ data: {!! json_encode($deptData) !!}, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderColor: '#198754', borderWidth: 1 }]
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

    new Chart(document.getElementById('docChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($docLabels) !!},
            datasets: [
                { label: 'مكتمل', data: {!! json_encode($docComplete) !!}, backgroundColor: '#198754' },
                { label: 'غير مكتمل', data: {!! json_encode($docIncomplete) !!}, backgroundColor: '#adb5bd' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 }, color: textColor } } },
            scales: {
                x: { stacked: true, ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } }
            }
        }
    });
});
</script>
@endpush
