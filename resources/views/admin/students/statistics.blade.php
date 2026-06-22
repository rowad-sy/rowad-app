@extends('admin.layouts.master')

@section('title', 'إحصائيات الطلاب')

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
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>إحصائيات الطلاب</h4>
        <p>نظرة شاملة على بيانات الطلاب والتسجيلات والحضور</p>
    </div>
    @if (array_filter($filters))
    <div>
        <a href="{{ route('admin.students.statistics') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> إلغاء الفلاتر
        </a>
    </div>
    @endif
</div>

{{-- ─── Filter Form ─── --}}
<form method="GET" action="{{ route('admin.students.statistics') }}" class="mb-4">
    <div class="table-container">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> الفلاتر</h6>
            <small class="text-muted">تصفية الإحصائيات حسب المعايير أدناه</small>
        </div>
        <div class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">المركز</label>
                    <select name="center_id" class="form-select form-select-sm">
                        <option value="">كل المراكز</option>
                        @foreach ($centers as $c)
                            <option value="{{ $c->id }}" {{ ($filters['center_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">المشروع</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" {{ ($filters['project_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">المقرر</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">كل المقررات</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ ($filters['course_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">الفترة</label>
                    <select name="period_id" class="form-select form-select-sm">
                        <option value="">كل الفترات</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" {{ ($filters['period_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-6 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i> تطبيق
                    </button>
                    <a href="{{ route('admin.students.statistics') }}" class="btn btn-outline-secondary btn-sm">
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
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <small class="opacity-75">إجمالي الطلاب</small>
                    <h3>{{ $totalStudents }}</h3>
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
                    <h3>{{ $activeStudents }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card-lg bg-info text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-journal-check"></i></div>
                <div>
                    <small class="opacity-75">متخرج</small>
                    <h3>{{ $graduatedStudents }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card-lg bg-warning text-white h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25"><i class="bi bi-person-up"></i></div>
                <div>
                    <small class="opacity-75">مسجل في مقررات</small>
                    <h3>{{ $totalEnrollments }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Gender + Status + Today Attendance (Row 1 Charts) ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-gender-ambiguous me-1"></i> توزيع الجنس</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="genderChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small">
                    <span><span class="badge" style="background:#0d6efd;">&nbsp;</span> ذكر: {{ $maleStudents }}</span>
                    <span><span class="badge" style="background:#d63384;">&nbsp;</span> أنثى: {{ $femaleStudents }}</span>
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
                    @foreach (['نشط', 'غير نشط', 'متخرج', 'موقوف'] as $i => $label)
                        <span><span class="badge" style="background:{{ ['#198754','#6c757d','#0d6efd','#ffc107'][$i] }};">&nbsp;</span> {{ $label }}: {{ [$activeStudents, $inactiveStudents, $graduatedStudents, $suspendedStudents][$i] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-clipboard-check me-1"></i> حضور اليوم</h6></div>
            <div class="p-3">
                <div class="chart-container-sm"><canvas id="todayAttendanceChart"></canvas></div>
                <div class="d-flex justify-content-center gap-3 mt-2 small">
                    <span class="text-success"><i class="bi bi-circle-fill me-1"></i> حاضر: {{ $todayPresent }}</span>
                    <span class="text-danger"><i class="bi bi-circle-fill me-1"></i> غائب: {{ $todayAbsent }}</span>
                    <span class="text-warning"><i class="bi bi-circle-fill me-1"></i> متعذر: {{ $todayExcused }}</span>
                    <span><i class="bi bi-people me-1"></i> المجموع: {{ $todayTotal }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Center + Project Distribution (Row 2 Charts) ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="table-container">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> توزيع الطلاب حسب المركز</h6>
            </div>
            <div class="p-3">
                <div class="chart-container"><canvas id="centerChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead class="table-light">
                        <tr><th>المركز</th><th>العدد</th><th>النسبة</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($centerStats as $cs)
                            <tr>
                                <td>{{ $cs->name }}</td>
                                <td>{{ $cs->total }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-primary" style="width: {{ $totalStudents > 0 ? round($cs->total / $totalStudents * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $totalStudents > 0 ? round($cs->total / $totalStudents * 100, 1) : 0 }}%</small>
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
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-briefcase me-1"></i> توزيع الطلاب حسب المشروع</h6>
            </div>
            <div class="p-3">
                <div class="chart-container"><canvas id="projectChart"></canvas></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead class="table-light">
                        <tr><th>المشروع</th><th>العدد</th><th>النسبة</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($projectStats as $ps)
                            <tr>
                                <td>{{ $ps->name }}</td>
                                <td>{{ $ps->total }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-success" style="width: {{ $totalStudents > 0 ? round($ps->total / $totalStudents * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $totalStudents > 0 ? round($ps->total / $totalStudents * 100, 1) : 0 }}%</small>
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

{{-- ─── Monthly Enrollment Trends (Line Chart) ─── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-graph-up me-1"></i> التوجه الشهري للتسجيلات (آخر 12 شهر)</h6></div>
            <div class="p-3">
                <div class="chart-container" style="height:280px;"><canvas id="monthlyChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Course + Period Enrollments + Weekly Attendance ─── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-book me-1"></i> التسجيلات حسب المقرر</h6></div>
            <div class="chart-container-sm p-3"><canvas id="courseChart"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-calendar-range me-1"></i> التسجيلات حسب الفترة</h6></div>
            <div class="chart-container-sm p-3"><canvas id="periodChart"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-calendar-week me-1"></i> الحضور الأسبوعي</h6></div>
            <div class="chart-container-sm p-3"><canvas id="weeklyChart"></canvas></div>
        </div>
    </div>
</div>

{{-- ─── Enrollment Tables ─── --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-book me-1"></i> المقررات الأكثر تسجيلاً</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead class="table-light"><tr><th>المقرر</th><th>عدد التسجيلات</th></tr></thead>
                    <tbody>
                        @foreach ($courseStats as $cs)
                            <tr>
                                <td>{{ $cs->name_ar }}</td>
                                <td><span class="badge bg-primary">{{ $cs->total }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-calendar-range me-1"></i> الفترات الأكثر تسجيلاً</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-sm-custom">
                    <thead class="table-light"><tr><th>الفترة</th><th>عدد التسجيلات</th></tr></thead>
                    <tbody>
                        @foreach ($periodStats as $ps)
                            <tr>
                                <td>{{ $ps->name_ar }}</td>
                                <td><span class="badge bg-info">{{ $ps->total }}</span></td>
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

    // Gender doughnut
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($genderLabels) !!},
            datasets: [{ data: {!! json_encode($genderData) !!}, backgroundColor: {!! json_encode($genderColors) !!}, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    // Status doughnut
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($statusLabels) !!},
            datasets: [{ data: {!! json_encode($statusData) !!}, backgroundColor: {!! json_encode($statusColors) !!}, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    // Today attendance doughnut
    new Chart(document.getElementById('todayAttendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['حاضر', 'غائب', 'متعذر'],
            datasets: [{ data: [{{ $todayPresent }}, {{ $todayAbsent }}, {{ $todayExcused }}], backgroundColor: ['#198754', '#dc3545', '#ffc107'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    // Center horizontal bar
    new Chart(document.getElementById('centerChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($centerLabels) !!},
            datasets: [{ data: {!! json_encode($centerData) !!}, backgroundColor: 'rgba(13, 110, 253, 0.7)', borderColor: '#0d6efd', borderWidth: 1 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } }, y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } } }
        }
    });

    // Project horizontal bar
    new Chart(document.getElementById('projectChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($projectLabels) !!},
            datasets: [{ data: {!! json_encode($projectData) !!}, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderColor: '#198754', borderWidth: 1 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } }, y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } } }
        }
    });

    // Monthly enrollment line chart
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyLabels) !!},
            datasets: [{
                label: 'تسجيلات جديدة',
                data: {!! json_encode($monthlyData) !!},
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } },
                y: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } }
            }
        }
    });

    // Course doughnut
    new Chart(document.getElementById('courseChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($courseLabels) !!},
            datasets: [{ data: {!! json_encode($courseData) !!}, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14','#20c997','#d63384','#0dcaf0','#6610f2'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 10 }, color: textColor } } } }
    });

    // Period doughnut
    new Chart(document.getElementById('periodChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($periodLabels) !!},
            datasets: [{ data: {!! json_encode($periodData) !!}, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 10 }, color: textColor } } } }
    });

    // Weekly attendance stacked bar
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($weekLabels) !!},
            datasets: [
                { label: 'حاضر', data: {!! json_encode($weekPresent) !!}, backgroundColor: '#198754' },
                { label: 'غائب', data: {!! json_encode($weekAbsent) !!}, backgroundColor: '#dc3545' },
                { label: 'متعذر', data: {!! json_encode($weekExcused) !!}, backgroundColor: '#ffc107' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 6, font: { size: 9 }, color: textColor } } },
            scales: {
                x: { stacked: true, ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } }
            }
        }
    });
});
</script>
@endpush
