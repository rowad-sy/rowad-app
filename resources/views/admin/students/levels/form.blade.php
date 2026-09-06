@extends('admin.layouts.master')

@section('title', isset($level) && $level->exists ? 'تعديل مستوى/صف' : 'إضافة مستوى/صف')

@section('content')
@php
    $isEdit = isset($level) && $level->exists;
    if (!$isEdit) {
        $level = new \App\Models\Admin\Student\AcademicLevel();
    }
    $level->loadMissing(['subjects', 'subjectInstructors']);
    $levelTypes = ['grade' => 'صف', 'level' => 'مستوى تدريب', 'childhood' => 'طفولة', 'kindergarten' => 'روضة', 'course' => 'دورة/دبلومة'];
@endphp
<div class="page-header">
    <h4>{{ $isEdit ? 'تعديل المستوى/الصف' : 'إضافة مستوى/صف جديد' }}</h4>
    <p>
        <a href="{{ route('admin.students.levels.index') }}" class="text-decoration-none">المستويات والصفوف</a>
        / {{ $isEdit ? $level->name_ar : 'جديد' }}
    </p>
</div>

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

<form method="POST" action="{{ $isEdit ? route('admin.students.levels.update', $level) : route('admin.students.levels.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المشروع <span class="text-danger">*</span></label>
            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $level->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم AR <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $level->name_ar ?? '') }}" required>
            @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم EN</label>
            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" dir="ltr" value="{{ old('name_en', $level->name_en ?? '') }}">
            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">الكود</label>
            <input type="text" name="code" class="form-control" dir="ltr" value="{{ old('code', $level->code ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">النوع</label>
            <select name="type" class="form-select">
                @php $currentType = old('type', $level->type ?? 'grade'); @endphp
                @if (!array_key_exists($currentType, $levelTypes))
                    <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
                @endif
                @foreach ($levelTypes as $val => $label)
                    <option value="{{ $val }}" {{ $currentType == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">الترتيب</label>
            <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $level->sort_order ?? 0) }}">
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><i class="bi bi-collection ms-1"></i> مواد المستوى ومدرّسوها</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSubjectBtn">
            <i class="bi bi-plus-lg me-1"></i> إضافة مادة
        </button>
    </div>
    <p class="text-muted small mb-3">
        اختر المواد التي تُدرّس في هذا الصف/المستوى، وحدّد مدرّسي كل مادة (يمكن أكثر من مدرّس) والمدرّس الرئيسي.
    </p>

    @php
        // بناء قائمة المهام لإعادة العرض عند الخطأ أو عند التعديل
        $savedRows = [];
        if ($isEdit) {
            foreach ($level->subjects as $subject) {
                $instrs = $level->subjectInstructors->where('subject_id', $subject->id);
                $savedRows[] = [
                    'subject_id' => $subject->id,
                    'instructors' => $instrs->pluck('instructor_id')->map(fn ($v) => (int) $v)->toArray(),
                    'main_instructor_id' => $instrs->firstWhere('is_main', true)?->instructor_id,
                ];
            }
        }
        $rows = old('subjects', $savedRows);
        $rows = is_array($rows) ? array_values($rows) : [];
    @endphp

    <div id="subjectsContainer">
        @foreach ($rows as $i => $row)
            <div class="subject-row card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="small">مادة #{{ $i + 1 }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="bi bi-trash"></i></button>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small">المادة <span class="text-danger">*</span></label>
                            <select name="subjects[{{ $i }}][subject_id]" class="form-select form-select-sm subject-select" required>
                                <option value="">اختر المادة</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ (int)($row['subject_id'] ?? 0) === $subject->id ? 'selected' : '' }}>{{ $subject->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">المدرّسون (يمكن اختيار أكثر من واحد)</label>
                            <div class="border rounded p-2">
                                @forelse ($instructors as $instructor)
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" class="form-check-input instructor-check"
                                               name="subjects[{{ $i }}][instructors][]"
                                               value="{{ $instructor->id }}"
                                               id="inst_{{ $i }}_{{ $instructor->id }}"
                                               data-main-value="{{ $instructor->id }}"
                                               {{ in_array($instructor->id, $row['instructors'] ?? [], true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inst_{{ $i }}_{{ $instructor->id }}">
                                            {{ $instructor->first_name_ar }} {{ $instructor->last_name_ar }}
                                        </label>
                                    </div>
                                @empty
                                    <span class="text-muted small">لا يوجد مدرّسون متاحون.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">المدرّس الرئيسي</label>
                            <div class="border rounded p-2">
                                @forelse ($instructors as $instructor)
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input main-instructor-radio"
                                               name="subjects[{{ $i }}][main_instructor_id]"
                                               value="{{ $instructor->id }}"
                                               id="main_{{ $i }}_{{ $instructor->id }}"
                                               {{ (int)($row['main_instructor_id'] ?? 0) === $instructor->id ? 'checked' : '' }}>
                                        <label class="form-check-label" for="main_{{ $i }}_{{ $instructor->id }}">
                                            {{ $instructor->first_name_ar }} {{ $instructor->last_name_ar }}
                                        </label>
                                    </div>
                                @empty
                                    <span class="text-muted small">لا يوجد مدرّسون متاحون.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء المستوى' }}
        </button>
        <a href="{{ route('admin.students.levels.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const instructors = @json($instructors->map(fn ($i) => ['id' => $i->id, 'name' => $i->first_name_ar . ' ' . $i->last_name_ar]));
    const subjects = @json($subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar]));

    let rowIndex = {{ count($rows) }};

    // حدّ فعالية أزرار الراديو الرئيسي — لا يمكن اختيار مدرّس غير محدّد في قائمة المدرّسين
    function syncMainRadios(rowElement) {
        const container = rowElement;
        const checks = container.querySelectorAll('.instructor-check');
        const radios = container.querySelectorAll('.main-instructor-radio');
        radios.forEach(r => {
            const isSelected = Array.from(checks).some(c => c.checked && c.value === r.value);
            r.disabled = !isSelected;
        });
    }

    document.querySelectorAll('.subject-row').forEach(function (row) {
        row.querySelectorAll('.instructor-check').forEach(function (cb) {
            cb.addEventListener('change', function () { syncMainRadios(row); });
        });
        syncMainRadios(row);
    });

    const rowTemplate = () => `
        <div class="subject-row card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong class="small">مادة #<span class="row-num"></span></strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="bi bi-trash"></i></button>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small">المادة <span class="text-danger">*</span></label>
                        <select name="subjects[${rowIndex}][subject_id]" class="form-select form-select-sm subject-select" required>
                            <option value="">اختر المادة</option>
                            ${subjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">المدرّسون (يمكن اختيار أكثر من واحد)</label>
                        <div class="border rounded p-2">
                            ${instructors.map(ins => `
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input instructor-check"
                                           name="subjects[${rowIndex}][instructors][]" value="${ins.id}"
                                           id="inst_${rowIndex}_${ins.id}">
                                    <label class="form-check-label" for="inst_${rowIndex}_${ins.id}">${ins.name}</label>
                                </div>`).join('')}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">المدرّس الرئيسي</label>
                        <div class="border rounded p-2">
                            ${instructors.map(ins => `
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input main-instructor-radio"
                                           name="subjects[${rowIndex}][main_instructor_id]" value="${ins.id}"
                                           id="main_${rowIndex}_${ins.id}" disabled>
                                    <label class="form-check-label" for="main_${rowIndex}_${ins.id}">${ins.name}</label>
                                </div>`).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    function renumber() {
        document.querySelectorAll('.subject-row').forEach(function (row, idx) {
            const num = row.querySelector('.row-num');
            if (num) num.textContent = idx + 1;
        });
    }

    document.getElementById('addSubjectBtn').addEventListener('click', function () {
        document.getElementById('subjectsContainer').insertAdjacentHTML('beforeend', rowTemplate());
        const newRow = document.getElementById('subjectsContainer').lastElementChild;
        newRow.querySelectorAll('.instructor-check').forEach(function (cb) {
            cb.addEventListener('change', function () { syncMainRadios(newRow); });
        });
        syncMainRadios(newRow);
        rowIndex++;
        renumber();
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-subject-btn')) {
            e.target.closest('.subject-row').remove();
            renumber();
        }
    });
});
</script>
@endpush
@endsection
