@extends('admin.layouts.master')

@section('title', isset($student) ? 'تعديل طالب' : 'إضافة طالب')

@section('content')
<x-page-header :title="isset($student) ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد'"
               :breadcrumb="[['label' => 'الطلاب', 'url' => route('admin.students.index')], ['label' => isset($student) ? $student->first_name_ar . ' ' . $student->last_name_ar : 'جديد']]" />

@if (!isset($student))
{{-- شريط البحث عن طالب موجود (متوافق مع الجوال - داخل الصفحة وليس نافذة منبثقة) --}}
<div class="table-container mb-4" id="identitySearchSection">
    <div class="p-3">
        <label class="form-label"><i class="bi bi-search me-1"></i> البحث عن طالب موجود</label>
        <p class="text-muted small mb-3">أدخل رقم الهوية. إذا كان الطالب موجوداً ستظهر خيارات (تعديل / إضافة لمشروع آخر)، وإذا لم يكن موجوداً يمكنك إنشاء طالب جديد مباشرة.</p>
        <div class="row g-2">
            <div class="col-md-5 col-8">
                <input type="text" id="identitySearchInput" class="form-control" placeholder="رقم الهوية" dir="ltr" value="{{ $identityNumber ?? '' }}">
            </div>
            <div class="col-md-3 col-4">
                <button type="button" class="btn btn-primary w-100" id="identitySearchBtn" onclick="searchExistingStudent()">
                    <i class="bi bi-search me-1"></i> بحث
                </button>
            </div>
        </div>
        <div id="identitySearchResult" class="mt-3"></div>
    </div>
</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        <strong>يرجى تصحيح الأخطاء التالية:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ isset($student) ? route('admin.students.update', $student) : route('admin.students.store') }}">
    @csrf
    @if (isset($student))
        @method('PUT')
    @endif

    <p class="text-muted small mb-3">الحقول المميّزة بعلامة <span class="text-danger" aria-hidden="true">*</span> مطلوبة.</p>

    <fieldset class="form-card mb-4">
        <legend class="section-title"><i class="bi bi-person-vcard" aria-hidden="true"></i> بيانات الحساب والهوية</legend>
        <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="f-user_id">المستخدم (اختياري)</label>
            <select id="f-user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                <option value="">— بدون مستخدم —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $student->user_id ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-student_code">كود الطالب <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(مطلوب)</span></label>
            <input id="f-student_code" type="text" name="student_code" class="form-control @error('student_code') is-invalid @enderror"
                   value="{{ old('student_code', $student->student_code ?? '') }}" required>
            @error('student_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label" for="f-identity_type">نوع الهوية</label>
            <select id="f-identity_type" name="identity_type" class="form-select @error('identity_type') is-invalid @enderror">
                <option value="">— اختر —</option>
                <option value="national_id" {{ old('identity_type', $student->identity_type ?? '') === 'national_id' ? 'selected' : '' }}>بطاقة هوية</option>
                <option value="passport" {{ old('identity_type', $student->identity_type ?? '') === 'passport' ? 'selected' : '' }}>جواز سفر</option>
                <option value="resident_id" {{ old('identity_type', $student->identity_type ?? '') === 'resident_id' ? 'selected' : '' }}>إقامة</option>
                <option value="other" {{ old('identity_type', $student->identity_type ?? '') === 'other' ? 'selected' : '' }}>أخرى</option>
            </select>
            @error('identity_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-identity_number">رقم الهوية</label>
            <input id="f-identity_number" type="text" name="identity_number" class="form-control @error('identity_number') is-invalid @enderror"
                   value="{{ old('identity_number', $student->identity_number ?? $identityNumber ?? '') }}" dir="ltr">
            @error('identity_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-status">الحالة</label>
            <select id="f-status" name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="active" {{ old('status', $student->status ?? 'active') === 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ old('status', $student->status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                <option value="graduated" {{ old('status', $student->status ?? '') === 'graduated' ? 'selected' : '' }}>متخرج</option>
                <option value="suspended" {{ old('status', $student->status ?? '') === 'suspended' ? 'selected' : '' }}>موقوف</option>
            </select>
            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="f-enrollment_date">تاريخ التسجيل</label>
            <input id="f-enrollment_date" type="date" name="enrollment_date" class="form-control @error('enrollment_date') is-invalid @enderror"
                   value="{{ old('enrollment_date', ($student->enrollment_date ?? null)?->format('Y-m-d') ?? '') }}">
            @error('enrollment_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        </div>
    </fieldset>

    <fieldset class="form-card mb-4">
        <legend class="section-title"><i class="bi bi-person" aria-hidden="true"></i> البيانات الشخصية</legend>
        <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="f-first_name_ar">الاسم AR <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(مطلوب)</span></label>
            <div class="row g-2">
                <div class="col-6">
                    <input id="f-first_name_ar" type="text" name="first_name_ar" class="form-control @error('first_name_ar') is-invalid @enderror"
                           value="{{ old('first_name_ar', $student->first_name_ar ?? '') }}" placeholder="الاسم" required>
                </div>
                <div class="col-6">
                    <input id="f-last_name_ar" type="text" name="last_name_ar" class="form-control @error('last_name_ar') is-invalid @enderror"
                           value="{{ old('last_name_ar', $student->last_name_ar ?? '') }}" placeholder="اللقب" required>
                </div>
            </div>
            @error('first_name_ar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @error('last_name_ar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="f-first_name_en">الاسم EN</label>
            <div class="row g-2">
                <div class="col-6">
                    <input id="f-first_name_en" type="text" name="first_name_en" class="form-control @error('first_name_en') is-invalid @enderror"
                           value="{{ old('first_name_en', $student->first_name_en ?? '') }}" placeholder="First Name" dir="ltr">
                </div>
                <div class="col-6">
                    <input id="f-last_name_en" type="text" name="last_name_en" class="form-control @error('last_name_en') is-invalid @enderror"
                           value="{{ old('last_name_en', $student->last_name_en ?? '') }}" placeholder="Last Name" dir="ltr">
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="f-father_name">اسم الأب</label>
            <input id="f-father_name" type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror"
                   value="{{ old('father_name', $student->father_name ?? '') }}">
            @error('father_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label" for="f-mother_name">اسم الأم</label>
            <input id="f-mother_name" type="text" name="mother_name" class="form-control @error('mother_name') is-invalid @enderror"
                   value="{{ old('mother_name', $student->mother_name ?? '') }}">
            @error('mother_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-gender">الجنس <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(مطلوب)</span></label>
            <select id="f-gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="male" {{ old('gender', $student->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                <option value="female" {{ old('gender', $student->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
            </select>
            @error('gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-birth_date">تاريخ الميلاد</label>
            <input id="f-birth_date" type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                   value="{{ old('birth_date', ($student->birth_date ?? null)?->format('Y-m-d') ?? '') }}">
            @error('birth_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-birth_place">مكان الميلاد</label>
            <input id="f-birth_place" type="text" name="birth_place" class="form-control @error('birth_place') is-invalid @enderror"
                   value="{{ old('birth_place', $student->birth_place ?? '') }}">
            @error('birth_place') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="f-nationality">الجنسية</label>
            <input id="f-nationality" type="text" name="nationality" class="form-control @error('nationality') is-invalid @enderror"
                   value="{{ old('nationality', $student->nationality ?? '') }}">
            @error('nationality') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        </div>
    </fieldset>

    <fieldset class="form-card mb-4">
        <legend class="section-title"><i class="bi bi-telephone" aria-hidden="true"></i> التواصل والعنوان</legend>
        <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="f-phone">الهاتف</label>
            <input id="f-phone" type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $student->phone ?? '') }}" dir="ltr">
            @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label" for="f-email">البريد الإلكتروني</label>
            <input id="f-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $student->email ?? '') }}" dir="ltr">
            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="f-address">العنوان</label>
            <textarea id="f-address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $student->address ?? '') }}</textarea>
            @error('address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        </div>
    </fieldset>

    <fieldset class="form-card mb-4">
        <legend class="section-title"><i class="bi bi-diagram-3" aria-hidden="true"></i> الانتماء الدراسي</legend>
        <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="f-center_id">المركز</label>
            <select id="f-center_id" name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                <option value="">اختر مركز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ old('center_id', $student->center_id ?? $defaultCenterId ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
            @error('center_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="f-project_ids">المشاريع</label>
            <input type="hidden" name="sync_project_ids" value="1">
            <select id="f-project_ids" name="project_ids[]" class="form-select @error('project_ids') is-invalid @enderror" multiple onchange="filterCoursesByProjects(); filterCohorts()">
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
            @error('project_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="student_cohort">الفوج</label>
            <select name="cohort_id" id="student_cohort" class="form-select @error('cohort_id') is-invalid @enderror">
                <option value="">—</option>
                @foreach ($cohorts as $cohort)
                    <option value="{{ $cohort->id }}" data-project-id="{{ $cohort->project_id }}"
                        {{ old('cohort_id', $student->cohort_id ?? $defaultCohortId ?? '') == $cohort->id ? 'selected' : '' }}>
                        {{ $cohort->name }} ({{ $cohort->project?->name }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">الفوج (مجموعة الطلاب) — صباحي/مسائي</small>
            @error('cohort_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        </div>
    </fieldset>

    <fieldset class="form-card mb-4">
        <legend class="section-title"><i class="bi bi-chat-left-text" aria-hidden="true"></i> ملاحظات</legend>
        <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="f-notes">ملاحظات</label>
            <textarea id="f-notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $student->notes ?? '') }}</textarea>
            @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        </div>
    </fieldset>

    <div class="form-card mb-4">
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

    <div class="d-grid d-sm-flex gap-2 mt-2 mb-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ
        </button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary px-4">إلغاء</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
// بعد فشل التحقق: الانتقال إلى أول حقل به خطأ
document.addEventListener('DOMContentLoaded', function () {
    var bad = document.querySelector('.is-invalid');
    if (bad) { bad.scrollIntoView({ block: 'center' }); bad.focus({ preventScroll: true }); }
});
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
    filterCohorts();
}

function filterCohorts() {
    var projectSelect = document.querySelector('select[name="project_ids[]"]');
    var cohortSelect = document.getElementById('student_cohort');
    if (!projectSelect || !cohortSelect) return;

    var selectedIds = Array.from(projectSelect.selectedOptions).map(o => o.value);
    var options = cohortSelect.querySelectorAll('option[data-project-id]');

    if (selectedIds.length === 0) {
        options.forEach(function (o) { o.style.display = ''; });
        return;
    }

    options.forEach(function (o) {
        if (o.value === '') return;
        o.style.display = selectedIds.includes(o.getAttribute('data-project-id')) ? '' : 'none';
    });

    if (cohortSelect.selectedOptions.length === 0 || cohortSelect.selectedOptions[0].style.display === 'none') {
        cohortSelect.value = '';
    }
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
    filterCohorts();

    var identityInput = document.getElementById('identitySearchInput');
    if (identityInput) {
        identityInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchExistingStudent(); }
        });
    }
});

function searchExistingStudent() {
    var input = document.getElementById('identitySearchInput');
    var result = document.getElementById('identitySearchResult');
    var btn = document.getElementById('identitySearchBtn');
    var number = input.value.trim();

    if (!number) { input.focus(); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري البحث...';
    result.innerHTML = '';

    fetch('{{ route("admin.students.check-identity") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ identity_number: number }),
    })
    .then(function (res) {
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        return res.json();
    })
    .then(function (data) {
        if (data.found === false) {
            // الطالب غير موجود → إشارة واضحة + تعبئة رقم الهوية في النموذج
            result.innerHTML =
                '<div class="alert alert-warning">' +
                    '<i class="bi bi-person-plus me-1"></i> لم يتم العثور على طالب بهذا الرقم. يمكنك <strong>إكمال نموذج إنشاء طالب جديد</strong> أدناه (رقم الهوية معبأ تلقائياً).' +
                '</div>';
            var formIdentity = document.querySelector('input[name="identity_number"]');
            if (formIdentity) formIdentity.value = number;
            document.querySelector('input[name="first_name_ar"]')?.focus();
            return;
        }

        if (data.can_edit) {
            // يملك صلاحية التعديل → زر للانتقال مباشرة
            result.innerHTML =
                '<div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                    '<span><i class="bi bi-check-circle me-1"></i> الطالب موجود ويمكنك تعديله: <strong>' + data.student.name + '</strong>&nbsp;(الكود: <code>' + data.student.code + '</code>)</span>' +
                    '<a href="' + data.redirect + '" class="btn btn-sm btn-success">' +
                        '<i class="bi bi-pencil me-1"></i> تعديل الطالب' +
                    '</a>' +
                '</div>';
            return;
        }

        // طالب موجود لكن لا يمكن تعديله → عرض الإضافة لمشاريع أخرى inline
        var checkboxes = '';
        (data.available_projects || []).forEach(function (p) {
            checkboxes +=
                '<div class="form-check">' +
                    '<input class="form-check-input" type="checkbox" name="project_ids[]" value="' + p.id + '" id="proj_' + p.id + '">' +
                    '<label class="form-check-label" for="proj_' + p.id + '">' + p.name + '</label>' +
                '</div>';
        });

        result.innerHTML =
            '<div class="alert alert-info">' +
                '<div class="d-flex align-items-center justify-content-between gap-2 mb-2">' +
                    '<span><strong>' + data.student.name + '</strong>' +
                    '&nbsp;(الكود: <code>' + data.student.code + '</code>)<br>' +
                    '<small class="text-muted">المشاريع الحالية: ' + ((data.student.projects || []).join('، ') || 'لا يوجد') + '</small></span>' +
                '</div>' +
                '<hr class="my-2">' +
                '<form method="POST" action="{{ url("admin/students") }}/' + data.student.id + '/add-to-projects" id="addToProjectInlineForm">' +
                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                    '<input type="hidden" name="student_id" value="' + data.student.id + '">' +
                    '<label class="form-label small">إضافة الطالب لمشاريع:</label>' +
                    '<div class="d-flex flex-wrap gap-2 mb-2">' + checkboxes + '</div>' +
                    '<button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-folder-plus me-1"></i> إضافة للمشاريع المحددة</button>' +
                    '&nbsp;<a href="' + (data.redirect || '') + '" class="btn btn-sm btn-outline-primary">تعديل الطالب</a>' +
                    '&nbsp;<a href="{{ url("admin/students") }}/' + data.student.id + '" class="btn btn-sm btn-outline-secondary">عرض الملف</a>' +
                '</form>' +
            '</div>';
    })
    .catch(function () {
        result.innerHTML = '<div class="alert alert-danger">' +
            '<i class="bi bi-exclamation-triangle me-1"></i> حدث خطأ أثناء البحث. أعد المحاولة أو تحقق من الاتصال.' +
            '</div>';
    })
    .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search me-1"></i> بحث';
    });
}
</script>
@endpush







