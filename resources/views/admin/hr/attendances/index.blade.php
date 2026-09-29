@extends('admin.layouts.master')

@section('title', 'الحضور والغياب')

@push('styles')
<style>
    .attendance-cell { text-align: center; padding: 0.5rem; }
    .attendance-cell .form-select, .attendance-cell .form-control { font-size: 0.85rem; }
    .attendance-cell .form-select { min-width: 110px; }
    .attendance-cell .form-control { min-width: 140px; }
    /* لون الصف مساعد فقط؛ الحالة مقروءة من قائمة الحالة نفسها (حاضر/غائب/بعذر) */
    .attendance-table tr.status-present > td { background: var(--status-success-bg); }
    .attendance-table tr.status-absent > td { background: var(--status-danger-bg); }
    .attendance-table tr.status-excused > td { background: var(--status-warning-bg); }
    .attendance-table th { white-space: nowrap; font-size: 0.85rem; }
</style>
@endpush

@section('content')
@php
    $dateLabel = \Carbon\Carbon::parse($date)->locale('ar')->translatedFormat('l d F Y');
    $centerName = $centerId ? optional($centers->firstWhere('id', (int) $centerId))->name : null;
    $projectName = $projectId ? optional($projects->firstWhere('id', (int) $projectId))->name : null;
@endphp
<x-page-header title="الحضور والغياب" description="تسجيل ومتابعة حضور وانصراف الموظفين"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'الحضور والغياب']]">
    <a href="{{ route('admin.hr.timesheets.index', ['date' => $date]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-table me-1" aria-hidden="true"></i> التايم شيت
    </a>
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-12 col-md-3 filter-field">
            <label class="form-label" for="f-date">التاريخ</label>
            <input type="date" id="f-date" name="date" class="form-control" value="{{ $date }}">
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-center_id">المركز</label>
            <select id="f-center_id" name="center_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ $centerId == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-project_id">المشروع</label>
            <select id="f-project_id" name="project_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="نطاق التسجيل الحالي">
    <x-status-badge tone="brand"><i class="bi bi-calendar-event" aria-hidden="true"></i> {{ $dateLabel }}</x-status-badge>
    <x-status-badge><i class="bi bi-geo-alt" aria-hidden="true"></i> المركز: {{ $centerName ?? 'كل المراكز' }}</x-status-badge>
    <x-status-badge><i class="bi bi-briefcase" aria-hidden="true"></i> المشروع: {{ $projectName ?? 'كل المشاريع' }}</x-status-badge>
    <span class="small text-muted">تغيير الفلاتر يعيد تحميل القائمة؛ احفظ الحضور قبل ذلك.</span>
</div>

<form method="POST" action="{{ route('admin.hr.attendances.store') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-bordered align-middle attendance-table mb-0">
                <thead>
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
                            <td class="ltr-cell text-nowrap">{{ $employee->employee_code }}</td>
                            <td class="fw-medium">{{ $employee->first_name_ar }} {{ $employee->last_name_ar }}</td>
                            <td>{{ $employee->center?->name ?? '—' }}</td>
                            <td>{{ $employee->project?->name ?? '—' }}</td>
                            <td class="attendance-cell">
                                <select name="attendances[{{ $employee->id }}][status]"
                                        aria-label="حالة {{ $employee->first_name_ar }} {{ $employee->last_name_ar }}"
                                        class="form-select form-select-sm status-select"
                                        onchange="toggleLeaveType(this, {{ $employee->id }})">
                                    <option value="present" {{ $status === 'present' ? 'selected' : '' }}>حاضر</option>
                                    <option value="absent" {{ $status === 'absent' ? 'selected' : '' }}>غائب</option>
                                    <option value="excused" {{ $status === 'excused' ? 'selected' : '' }}>بعذر</option>
                                </select>
                            </td>
                            <td class="attendance-cell">
                                <select name="attendances[{{ $employee->id }}][leave_type_id]"
                                        aria-label="نوع إجازة {{ $employee->first_name_ar }} {{ $employee->last_name_ar }}"
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
                                       aria-label="ملاحظات {{ $employee->first_name_ar }} {{ $employee->last_name_ar }}"
                                       class="form-control form-control-sm" value="{{ $att?->notes ?? '' }}"
                                       placeholder="ملاحظة">
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="7" icon="bi-people" title="لا يوجد موظفون نشطون" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($employees->isNotEmpty())
    <div class="d-grid d-sm-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1" aria-hidden="true"></i> حفظ الحضور
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
