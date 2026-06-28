@extends('admin.layouts.master')

@section('title', 'التايم شيت')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>التايم شيت</h4>
        <p>سجل حضور وغياب الموظفين الشهري</p>
    </div>
    <a href="{{ route('admin.hr.timesheets.print', request()->query()) }}" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-printer me-1"></i> طباعة
    </a>
</div>

<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small mb-1">الشهر</label>
            <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">المركز</label>
            <select name="center_id" class="form-select" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ $centerId == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">المشروع</label>
            <select name="project_id" class="form-select" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">القسم</label>
            <select name="department_id" class="form-select" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">بحث</label>
            <input type="text" name="search" class="form-control" placeholder="اسم الموظف..." value="{{ $search }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">&nbsp;</label>
            <button class="btn btn-outline-secondary w-100" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="timesheet-table" style="font-size:0.85rem;">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" style="min-width:120px;">الموظف</th>
                    <th rowspan="2" style="min-width:80px;">الكود</th>
                    <th colspan="{{ \Carbon\Carbon::parse($month . '-01')->daysInMonth }}" class="text-center">
                        {{ \Carbon\Carbon::parse($month . '-01')->locale('ar')->translatedFormat('F Y') }}
                    </th>
                    <th rowspan="2" style="min-width:50px;">حضور</th>
                    <th rowspan="2" style="min-width:50px;">غياب</th>
                </tr>
                <tr>
                    @for ($d = 1; $d <= \Carbon\Carbon::parse($month . '-01')->daysInMonth; $d++)
                        @php
                            $date = \Carbon\Carbon::parse(sprintf('%s-%02d', $month, $d));
                            $isWeekend = $date->dayOfWeek === 5 || $date->dayOfWeek === 6;
                        @endphp
                        <th class="{{ $isWeekend ? 'text-muted bg-light' : '' }}" style="min-width:28px;font-size:0.75rem;padding:2px;">
                            {{ $d }}
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody id="timesheet-body">
                <tr>
                    <td colspan="100" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split me-2"></i>
                        اختر الفلترة ثم اضغط بحث لعرض التايم شيت
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Load timesheet data via AJAX on filter change
    function loadTimesheet() {
        const params = new URLSearchParams(window.location.search);
        params.set('employee_ids', ''); // clear selection
        fetch('{{ route('admin.hr.timesheets.print') }}?' + params.toString(), {
            headers: { 'Accept': 'text/html' }
        })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newBody = doc.querySelector('#timesheet-body');
            if (newBody) {
                document.getElementById('timesheet-body').innerHTML = newBody.innerHTML;
            }
        })
        .catch(() => {});
    }

    // Auto-load on page load if filters are set
    @if ($search || $centerId || $projectId || $departmentId)
    loadTimesheet();
    @endif

    // Reload when filters change
    document.querySelectorAll('.form-card select, .form-card input').forEach(el => {
        el.addEventListener('change', function() {
            if (this.name !== 'month') return; // only month triggers auto-load
        });
    });
</script>
@endpush
