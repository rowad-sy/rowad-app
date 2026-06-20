@extends('admin.layouts.master')

@section('title', 'تسجيل الحضور')

@push('styles')
<style>
    .attendance-radio { display: flex; gap: 0.35rem; justify-content: center; }
    .attendance-radio .form-check { padding: 0; margin: 0; min-width: 60px; }
    .attendance-radio .form-check-input { display: none; }
    .attendance-radio .form-check-label {
        display: block;
        padding: 0.3rem 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.15s ease;
        background: #f8f9fa;
        color: #6c757d;
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
    .attendance-stats .stat { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; border-radius: 4px; background: #f8f9fa; }
    .attendance-stats .stat-present { color: #198754; }
    .attendance-stats .stat-absent { color: #dc3545; }
    .attendance-stats .stat-excused { color: #b8860b; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h4>تسجيل الحضور</h4>
    <p>تسجيل حضور وغياب الطلاب</p>
</div>

<div class="table-container">
    {{-- Filters --}}
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">التاريخ</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}"
                       onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الدورة</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ (int)($courseId ?? '') === $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الفترة</label>
                <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" {{ (int)($periodId ?? '') === $period->id ? 'selected' : '' }}>{{ $period->name_ar }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- Attendance Form --}}
    <form method="POST" action="{{ route('admin.students.attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        {{-- Stats bar --}}
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="attendance-stats d-flex gap-2">
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
                <thead class="table-light">
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
                            <td>{{ $student->id }}</td>
                            <td><code>{{ $student->student_code }}</code></td>
                            <td class="fw-medium">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</td>
                            <td>
                                @if ($student->gender === 'male')
                                    <span class="badge bg-info text-white">ذكر</span>
                                @else
                                    <span class="badge bg-pink text-white">أنثى</span>
                                @endif
                            </td>
                            <td>{{ $student->center?->name ?? '—' }}</td>
                            <td>
                                <input type="hidden" name="attendance[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                <div class="attendance-radio">
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
                                       class="form-control form-control-sm" style="width:120px;"
                                       value="{{ $att->note ?? '' }}" placeholder="ملاحظة">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                لا يوجد طلاب نشطاء
                            </td>
                        </tr>
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
