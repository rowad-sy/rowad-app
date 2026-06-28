@extends('admin.layouts.master')

@section('title', 'الحضور والغياب')

@push('styles')
<style>
    .attendance-cell { text-align: center; padding: 0.5rem; }
    .attendance-cell .form-select, .attendance-cell .form-control { font-size: 0.85rem; }
    .status-present { background: #d1e7dd !important; }
    .status-absent { background: #f8d7da !important; }
    .status-excused { background: #fff3cd !important; }
    .attendance-table th { white-space: nowrap; font-size: 0.85rem; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h4>الحضور والغياب</h4>
    <p>تسجيل ومتابعة حضور وانصراف الموظفين</p>
</div>

<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">التاريخ</label>
            <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
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
        <div class="col-md-2">
            <label class="form-label small mb-1">&nbsp;</label>
            <a href="{{ route('admin.hr.timesheets.index', ['date' => $date]) }}" class="btn btn-outline-info w-100">
                <i class="bi bi-table me-1"></i> التايم شيت
            </a>
        </div>
    </form>
</div>

<form method="POST" action="{{ route('admin.hr.attendances.store') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-bordered align-middle attendance-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>المركز</th>
                        <th>المشروع</th>
                        <th>الحالة</th>
                        <th>نوع الإجازة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $att = $attendances->get($employee->id);
                            $status = $att?->status ?? 'present';
                        @endphp
                        <tr class="status-{{ $status }}">
                            <td>{{ $employee->employee_code }}</td>
                            <td class="fw-medium">{{ $employee->first_name_ar }} {{ $employee->last_name_ar }}</td>
                            <td>{{ $employee->center?->name ?? '—' }}</td>
                            <td>{{ $employee->project?->name ?? '—' }}</td>
                            <td class="attendance-cell">
                                <select name="attendances[{{ $employee->id }}][status]"
                                        class="form-select form-select-sm status-select"
                                        onchange="toggleLeaveType(this, {{ $employee->id }})">
                                    <option value="present" {{ $status === 'present' ? 'selected' : '' }}>حاضر</option>
                                    <option value="absent" {{ $status === 'absent' ? 'selected' : '' }}>غائب</option>
                                    <option value="excused" {{ $status === 'excused' ? 'selected' : '' }}>بعذر</option>
                                </select>
                            </td>
                            <td class="attendance-cell">
                                <select name="attendances[{{ $employee->id }}][leave_type_id]"
                                        class="form-select form-select-sm leave-type-select"
                                        id="leave-type-{{ $employee->id }}">
                                    <option value="">—</option>
                                    @foreach ($leaveTypes as $lt)
                                        <option value="{{ $lt->id }}" {{ $att?->leave_type_id == $lt->id ? 'selected' : '' }}>
                                            {{ $lt->name_ar }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="attendance-cell">
                                <input type="text" name="attendances[{{ $employee->id }}][notes]"
                                       class="form-control form-control-sm" value="{{ $att?->notes ?? '' }}"
                                       placeholder="ملاحظة">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">لا يوجد موظفين نشطين</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($employees->isNotEmpty())
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> حفظ الحضور
        </button>
    </div>
    @endif
</form>
@endsection

@push('scripts')
<script>
    function toggleLeaveType(select, employeeId) {
        const leaveTypeSelect = document.getElementById('leave-type-' + employeeId);
        const row = select.closest('tr');
        row.className = 'status-' + select.value;
        if (select.value === 'excused') {
            leaveTypeSelect.required = true;
            leaveTypeSelect.closest('td').querySelector('.form-select').style.borderColor = '#ffc107';
        } else {
            leaveTypeSelect.required = false;
            leaveTypeSelect.closest('td').querySelector('.form-select').style.borderColor = '';
        }
    }
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.dispatchEvent(new Event('change'));
    });
</script>
@endpush
