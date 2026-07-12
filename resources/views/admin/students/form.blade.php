@extends('admin.layouts.master')

@section('title', isset($student) ? 'تعديل طالب' : 'إضافة طالب')

@section('content')
<div class="page-header">
    <h4>{{ isset($student) ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد' }}</h4>
    <p>
        <a href="{{ route('admin.students.index') }}" class="text-decoration-none">الطلاب</a>
        / {{ isset($student) ? $student->first_name_ar . ' ' . $student->last_name_ar : 'جديد' }}
    </p>
</div>

<form method="POST" action="{{ isset($student) ? route('admin.students.update', $student) : route('admin.students.store') }}">
    @csrf
    @if (isset($student))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">المستخدم (اختياري)</label>
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                <option value="">— بدون مستخدم —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $student->user_id ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">كود الطالب <span class="text-danger">*</span></label>
            <input type="text" name="student_code" class="form-control @error('student_code') is-invalid @enderror"
                   value="{{ old('student_code', $student->student_code ?? '') }}" required>
            @error('student_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">نوع الهوية</label>
            <select name="identity_type" class="form-select @error('identity_type') is-invalid @enderror">
                <option value="">— اختر —</option>
                <option value="national_id" {{ old('identity_type', $student->identity_type ?? '') === 'national_id' ? 'selected' : '' }}>بطاقة هوية</option>
                <option value="passport" {{ old('identity_type', $student->identity_type ?? '') === 'passport' ? 'selected' : '' }}>جواز سفر</option>
                <option value="resident_id" {{ old('identity_type', $student->identity_type ?? '') === 'resident_id' ? 'selected' : '' }}>إقامة</option>
                <option value="other" {{ old('identity_type', $student->identity_type ?? '') === 'other' ? 'selected' : '' }}>أخرى</option>
            </select>
            @error('identity_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">رقم الهوية</label>
            <input type="text" name="identity_number" class="form-control @error('identity_number') is-invalid @enderror"
                   value="{{ old('identity_number', $student->identity_number ?? '') }}" dir="ltr">
            @error('identity_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                <option value="active" {{ old('status', $student->status ?? 'active') === 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ old('status', $student->status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                <option value="graduated" {{ old('status', $student->status ?? '') === 'graduated' ? 'selected' : '' }}>متخرج</option>
                <option value="suspended" {{ old('status', $student->status ?? '') === 'suspended' ? 'selected' : '' }}>موقوف</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">تاريخ التسجيل</label>
            <input type="date" name="enrollment_date" class="form-control @error('enrollment_date') is-invalid @enderror"
                   value="{{ old('enrollment_date', $student->enrollment_date ?? '') }}">
            @error('enrollment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم AR <span class="text-danger">*</span></label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="first_name_ar" class="form-control @error('first_name_ar') is-invalid @enderror"
                           value="{{ old('first_name_ar', $student->first_name_ar ?? '') }}" placeholder="الاسم" required>
                </div>
                <div class="col-6">
                    <input type="text" name="last_name_ar" class="form-control @error('last_name_ar') is-invalid @enderror"
                           value="{{ old('last_name_ar', $student->last_name_ar ?? '') }}" placeholder="اللقب" required>
                </div>
            </div>
            @error('first_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @error('last_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم EN</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="first_name_en" class="form-control @error('first_name_en') is-invalid @enderror"
                           value="{{ old('first_name_en', $student->first_name_en ?? '') }}" placeholder="First Name" dir="ltr">
                </div>
                <div class="col-6">
                    <input type="text" name="last_name_en" class="form-control @error('last_name_en') is-invalid @enderror"
                           value="{{ old('last_name_en', $student->last_name_en ?? '') }}" placeholder="Last Name" dir="ltr">
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">اسم الأب</label>
            <input type="text" name="father_name" class="form-control"
                   value="{{ old('father_name', $student->father_name ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">اسم الأم</label>
            <input type="text" name="mother_name" class="form-control"
                   value="{{ old('mother_name', $student->mother_name ?? '') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">الجنس <span class="text-danger">*</span></label>
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="male" {{ old('gender', $student->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                <option value="female" {{ old('gender', $student->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">تاريخ الميلاد</label>
            <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                   value="{{ old('birth_date', $student->birth_date ?? '') }}">
            @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">مكان الميلاد</label>
            <input type="text" name="birth_place" class="form-control"
                   value="{{ old('birth_place', $student->birth_place ?? '') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">الجنسية</label>
            <input type="text" name="nationality" class="form-control"
                   value="{{ old('nationality', $student->nationality ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">الهاتف</label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $student->phone ?? '') }}" dir="ltr">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $student->email ?? '') }}" dir="ltr">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">المركز</label>
            <select name="center_id" class="form-select">
                <option value="">اختر مركز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ old('center_id', $student->center_id ?? $defaultCenterId ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">المشاريع</label>
            <input type="hidden" name="sync_project_ids" value="1">
            <select name="project_ids[]" class="form-select" multiple onchange="filterCoursesByProjects()">
                @foreach ($projects as $project)
                    @php
                        $selected = false;
                        if (isset($student)) {
                            $selected = $student->projects->contains($project->id);
                        } elseif (old('project_ids')) {
                            $selected = in_array($project->id, old('project_ids'));
                        }
                    @endphp
                    <option value="{{ $project->id }}" {{ $selected ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">اختر مشروعاً واحداً أو أكثر لفلترة المقررات</small>
        </div>

        <div class="col-md-6">
            <label class="form-label">العنوان</label>
            <textarea name="address" rows="2" class="form-control">{{ old('address', $student->address ?? '') }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">ملاحظات</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $student->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="form-card mt-4">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-journal-text me-1"></i> التسجيلات في المقررات</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEnrollmentRow()">
                <i class="bi bi-plus-lg"></i> إضافة تسجيل
            </button>
        </div>
        <div class="p-3">
            <table class="table table-bordered mb-0" id="enrollmentsTable">
                <thead class="table-light">
                    <tr>
                        <th>المقرر <span class="text-danger">*</span></th>
                        <th>الفترة <span class="text-danger">*</span></th>
                        <th>تاريخ التسجيل</th>
                        <th>الحالة</th>
                        <th>الدرجة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $enrollIdx = 0; @endphp
                    @if (isset($student) && $student->enrollments->isNotEmpty())
                        @foreach ($student->enrollments as $enrollment)
                        <tr>
                            <td>
                                <select name="enrollments[{{ $enrollIdx }}][course_id]" class="form-select form-select-sm" required>
                                    <option value="">— اختر —</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" data-project-id="{{ $course->project_id }}" {{ $enrollment->course_id == $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="enrollments[{{ $enrollIdx }}][period_id]" class="form-select form-select-sm" required>
                                    <option value="">— اختر —</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" {{ $enrollment->period_id == $period->id ? 'selected' : '' }}>{{ $period->name_ar }} ({{ $period->year }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="date" name="enrollments[{{ $enrollIdx }}][enrollment_date]" class="form-control form-control-sm"
                                       value="{{ old('enrollments.' . $enrollIdx . '.enrollment_date', $enrollment->enrollment_date?->format('Y-m-d')) }}">
                            </td>
                            <td>
                                <select name="enrollments[{{ $enrollIdx }}][status]" class="form-select form-select-sm">
                                    <option value="enrolled" {{ $enrollment->status === 'enrolled' ? 'selected' : '' }}>مسجل</option>
                                    <option value="completed" {{ $enrollment->status === 'completed' ? 'selected' : '' }}>مكتمل</option>
                                    <option value="dropped" {{ $enrollment->status === 'dropped' ? 'selected' : '' }}>منسحب</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="enrollments[{{ $enrollIdx }}][grade]" class="form-control form-control-sm" min="0" max="100" step="0.01"
                                       value="{{ old('enrollments.' . $enrollIdx . '.grade', $enrollment->grade) }}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @php $enrollIdx++; @endphp
                        @endforeach
                    @endif
                    <tr class="no-enrollments-row {{ isset($student) && $student->enrollments->isNotEmpty() ? 'd-none' : '' }}">
                        <td colspan="6" class="text-center text-muted py-3">
                            <i class="bi bi-inbox d-block fs-4 mb-1"></i>
                            لا يوجد تسجيلات. أضف تسجيلاً جديداً.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
let enrollIdx = {{ $enrollIdx ?? 0 }};

function addEnrollmentRow() {
    var tbody = document.querySelector('#enrollmentsTable tbody');
    var noRow = tbody.querySelector('.no-enrollments-row');
    if (noRow) noRow.classList.add('d-none');

    var html = `<tr>
        <td>
            <select name="enrollments[${enrollIdx}][course_id]" class="form-select form-select-sm" required>
                <option value="">— اختر —</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" data-project-id="{{ $course->project_id }}">{{ $course->name_ar }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="enrollments[${enrollIdx}][period_id]" class="form-select form-select-sm" required>
                <option value="">— اختر —</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->name_ar }} ({{ $period->year }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="date" name="enrollments[${enrollIdx}][enrollment_date]" class="form-control form-control-sm">
        </td>
        <td>
            <select name="enrollments[${enrollIdx}][status]" class="form-select form-select-sm">
                <option value="enrolled">مسجل</option>
                <option value="completed">مكتمل</option>
                <option value="dropped">منسحب</option>
            </select>
        </td>
        <td>
            <input type="number" name="enrollments[${enrollIdx}][grade]" class="form-control form-control-sm" min="0" max="100" step="0.01">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;

    tbody.insertAdjacentHTML('beforeend', html);
    enrollIdx++;
    filterCoursesByProjects();
}

function filterCoursesByProjects() {
    var projectSelect = document.querySelector('select[name="project_ids[]"]');
    if (!projectSelect) return;

    var selectedIds = Array.from(projectSelect.selectedOptions).map(o => o.value);
    var allCourseSelects = document.querySelectorAll('select[name$="[course_id]"]');

    allCourseSelects.forEach(function(select) {
        var options = select.querySelectorAll('option[data-project-id]');
        if (selectedIds.length === 0) {
            options.forEach(function(o) { o.style.display = ''; });
            return;
        }
        options.forEach(function(o) {
            if (o.value === '') return;
            o.style.display = selectedIds.includes(o.getAttribute('data-project-id')) ? '' : 'none';
        });
        if (select.selectedOptions.length === 0 || select.selectedOptions[0].style.display === 'none') {
            select.value = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    filterCoursesByProjects();
});
</script>
@endpush







