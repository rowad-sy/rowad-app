@extends('admin.layouts.master')

@section('title', 'تسجيل الحضور')

@push('styles')
<style>
    .attendance-radio { display: flex; gap: 0.35rem; justify-content: center; }
    .attendance-radio .form-check { padding: 0; margin: 0; min-width: 60px; }
    .attendance-radio { display: flex; gap: 0.25rem; flex-wrap: wrap; }
    /* الراديو مخفي بصريًا لكنه يبقى قابلًا للتركيز بلوحة المفاتيح */
    .attendance-radio .form-check { position: relative; }
    .attendance-radio .form-check-input { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; }
    .attendance-radio .form-check-input:focus-visible + .form-check-label { outline: 2px solid var(--color-primary); outline-offset: 2px; }
    .attendance-radio .form-check-label {
        display: block;
        padding: 0.3rem 0.5rem;
        border: 1px solid var(--color-border);
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.15s ease;
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
    }
    .attendance-radio .form-check-input:checked + .form-check-label {
        color: #fff;
        border-color: transparent;
    }
    .attendance-radio .form-check-input:checked + .label-present { background: #198754; }
    .attendance-radio .form-check-input:checked + .label-absent { background: #dc3545; }
    .attendance-radio .form-check-input:checked + .label-excused { background: #ffc107; color: #000; }
    .attendance-radio .form-check-label:hover { border-color: #94a3b8; }

    .attendance-stats { font-size: 0.85rem; }
    .attendance-stats .stat { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; border-radius: 4px; background: var(--color-surface-muted); }
    .attendance-stats .stat-present { color: #198754; }
    .attendance-stats .stat-absent { color: #dc3545; }
    .attendance-stats .stat-excused { color: #b8860b; }
</style>
@endpush

@section('content')
@php
    $dateLabel = \Carbon\Carbon::parse($date)->locale('ar')->translatedFormat('l d F Y');
    $centerName = ($centerId ?? null) ? optional($centers->firstWhere('id', (int) $centerId))->name : null;
    $projectName = ($projectId ?? null) ? optional($projects->firstWhere('id', (int) $projectId))->name : null;
@endphp
<x-page-header title="تسجيل الحضور" description="تسجيل حضور وغياب الطلاب"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الحضور']]" />

<div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="نطاق التسجيل الحالي">
    <x-status-badge tone="brand"><i class="bi bi-calendar-event" aria-hidden="true"></i> {{ $dateLabel }}</x-status-badge>
    <x-status-badge><i class="bi bi-geo-alt" aria-hidden="true"></i> المركز: {{ $centerName ?? 'كل المراكز' }}</x-status-badge>
    <x-status-badge><i class="bi bi-briefcase" aria-hidden="true"></i> المشروع: {{ $projectName ?? 'كل المشاريع' }}</x-status-badge>
    <span class="small text-muted">تغيير الفلاتر يعيد تحميل القائمة؛ احفظ الحضور قبل ذلك.</span>
</div>

<div class="table-container">
    {{-- Filters --}}
    <x-filter-bar>
            <div class="col-md-2">
                <label class="form-label">التاريخ</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الدورة</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ (int)($courseId ?? '') === $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الفترة</label>
                <select name="period_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" {{ (int)($periodId ?? '') === $period->id ? 'selected' : '' }}>{{ $period->name_ar }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    {{-- Attendance Form --}}
    <form method="POST" action="{{ route('admin.students.attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        {{-- Stats bar --}}
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="attendance-stats d-flex flex-wrap gap-2" role="status" aria-live="polite">
                <span class="stat stat-present">
                    <i class="bi bi-check-circle"></i> حاضر: <span id="countPresent">0</span>
                </span>
                <span class="stat stat-absent">
                    <i class="bi bi-x-circle"></i> غائب: <span id="countAbsent">0</span>
                </span>
                <span class="stat stat-excused">
                    <i class="bi bi-clock"></i> متعذر: <span id="countExcused">0</span>
                </span>
                <span class="stat text-muted">
                    <i class="bi bi-people"></i> الإجمالي: {{ $students->count() }}
                </span>
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i> حفظ الحضور
                </button>
            </div>
        </div>

        {{-- Student table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الكود</th>
                        <th>الاسم</th>
                        <th>الجنس</th>
                        <th>المركز</th>
                        <th style="min-width:220px;">الحضور</th>
                        <th>ملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php $att = $attendanceMap->get($student->id); @endphp
                        <tr>
                            <td class="num">{{ $student->id }}</td>
                            <td class="ltr-cell text-nowrap">{{ $student->student_code }}</td>
                            <td class="fw-medium">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</td>
                            <td>
                                {{ $student->gender === 'male' ? 'ذكر' : 'أنثى' }}
                            </td>
                            <td>{{ $student->center?->name ?? '—' }}</td>
                            <td>
                                <input type="hidden" name="attendance[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                <div class="attendance-radio" role="radiogroup" aria-label="حضور {{ $student->first_name_ar }} {{ $student->last_name_ar }}">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input attendance-input"
                                               name="attendance[{{ $student->id }}][status]" value="present"
                                               id="p_{{ $student->id }}"
                                               {{ !$att ? 'checked' : ($att->status === 'present' ? 'checked' : '') }}>
                                        <label class="form-check-label label-present" for="p_{{ $student->id }}">حاضر</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input attendance-input"
                                               name="attendance[{{ $student->id }}][status]" value="absent"
                                               id="a_{{ $student->id }}"
                                               {{ $att && $att->status === 'absent' ? 'checked' : '' }}>
                                        <label class="form-check-label label-absent" for="a_{{ $student->id }}">غائب</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input attendance-input"
                                               name="attendance[{{ $student->id }}][status]" value="excused"
                                               id="e_{{ $student->id }}"
                                               {{ $att && $att->status === 'excused' ? 'checked' : '' }}>
                                        <label class="form-check-label label-excused" for="e_{{ $student->id }}">متعذر</label>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="attendance[{{ $student->id }}][note]"
                                       class="form-control form-control-sm" style="min-width:120px;"
                                       aria-label="ملاحظة {{ $student->first_name_ar }} {{ $student->last_name_ar }}"
                                       value="{{ $att->note ?? '' }}" placeholder="ملاحظة">
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="7" icon="bi-people" title="لا يوجد طلاب نشطاء" />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 d-flex justify-content-between align-items-center border-top">
            <div class="text-muted small">
                إجمالي الطلاب: {{ $students->count() }}
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> حفظ الحضور
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputs = document.querySelectorAll('.attendance-input');
    function updateCounts() {
        var present = document.querySelectorAll('.attendance-input[value="present"]:checked').length;
        var absent = document.querySelectorAll('.attendance-input[value="absent"]:checked').length;
        var excused = document.querySelectorAll('.attendance-input[value="excused"]:checked').length;
        document.getElementById('countPresent').textContent = present;
        document.getElementById('countAbsent').textContent = absent;
        document.getElementById('countExcused').textContent = excused;
    }
    inputs.forEach(function (el) { el.addEventListener('change', updateCounts); });
    updateCounts();
});
</script>
@endpush
