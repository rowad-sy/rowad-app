@extends('admin.layouts.master')

@section('title', (isset($course) ? 'تعديل مقرر' : 'إضافة مقرر'))

@section('content')
@php
    $isEdit = isset($course) && $course->exists;
    if (!$isEdit) {
        $course = new \App\Models\Admin\Student\Course();
    }
    $course->loadMissing(['periods', 'levels', 'subjects.exams', 'offerings']);
    $examTypes = ['pre' => 'قبلي', 'post' => 'بعدي', 'quiz' => 'دوري', 'final' => 'نهائي', 'other' => 'أخرى'];
@endphp
<div class="page-header">
    <h4>{{ $isEdit ? 'تعديل المقرر' : 'إضافة مقرر جديد' }}</h4>
    <p>
        <a href="{{ route('admin.students.courses.index') }}" class="text-decoration-none">إدارة المقررات</a>
        / {{ $isEdit ? $course->name_ar : 'جديد' }}
        <span class="ms-2">
            <a href="{{ route('admin.students.courses.help') }}" class="text-decoration-none small">
                <i class="bi bi-question-circle"></i> معلومات ونصائح
            </a>
        </span>
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

<form method="POST" action="{{ $isEdit ? route('admin.students.courses.update', $course) : route('admin.students.courses.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $course->project_id ?? $defaultProjectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم AR <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                   value="{{ old('name_ar', $course->name_ar ?? '') }}" required>
            @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم EN</label>
            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                   value="{{ old('name_en', $course->name_en ?? '') }}" dir="ltr">
            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">المدة (أيام)</label>
            <input type="number" name="duration" class="form-control @error('duration') is-invalid @enderror"
                   value="{{ old('duration', $course->duration ?? '') }}" min="1">
            @error('duration') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-9">
            <label class="form-label">الوصف</label>
            <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $course->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label class="form-label">الفترات المرتبطة</label>
            <div class="row g-2">
                @forelse ($periods as $period)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="period_ids[]" value="{{ $period->id }}" class="form-check-input"
                                   id="period_{{ $period->id }}"
                                   {{ in_array($period->id, old('period_ids', $course->periods->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="period_{{ $period->id }}">
                                {{ $period->name_ar }}
                                <small class="text-muted">({{ $period->year }})</small>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted small">لا توجد فترات متاحة. قم بإضافة فترات أولاً.</div>
                @endforelse
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><i class="bi bi-layers ms-1"></i> المستويات / الأقسام (اختياري)</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addLevelBtn">
            <i class="bi bi-plus-lg me-1"></i> إضافة مستوى
        </button>
    </div>
    <p class="text-muted small mb-3">
        للدورات المهنية (مثل: الكوافيرة، الخياطة) أضف مستوياتها: كوافيرة ← مستوى أول / مستوى ثاني.
        للمقررات الأكاديمية والروضة لا تحتاج مستويات — يكفي المقرر ومواده.
    </p>

    <div id="levelsContainer" class="row g-2 mb-3">
        @php
            $levels = old('levels', $course->levels ?? []);
            $levels = is_array($levels) ? $levels : [];
        @endphp
        @foreach ($levels as $i => $level)
            <div class="col-md-6 level-row">
                <div class="card">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-center">
                            <input type="hidden" name="levels[{{ $i }}][id]" value="{{ $level['id'] ?? '' }}">
                            <div class="col-md-5">
                                <input type="text" name="levels[{{ $i }}][name_ar]" class="form-control form-control-sm" placeholder="اسم المستوى (مثال: مستوى أول) *" value="{{ $level['name_ar'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <select name="levels[{{ $i }}][type]" class="form-select form-select-sm">
                                    @foreach ($levelTypes as $val => $label)
                                        <option value="{{ $val }}" {{ ($level['type'] ?? 'level') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="levels[{{ $i }}][code]" class="form-control form-control-sm" placeholder="رمز" value="{{ $level['code'] ?? '' }}">
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-level-btn"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <hr class="my-4">

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><i class="bi bi-book ms-1"></i> المواد الدراسية</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSubjectBtn">
            <i class="bi bi-plus-lg me-1"></i> إضافة مادة
        </button>
    </div>
    <p class="text-muted small mb-3">أضف المادة ثم أضف امتحاناتها (قبلي، بعدي، دوري، نهائي...) مع علامة كل امتحان.</p>

    <div id="subjectsContainer">
        @php
            $subjects = old('subjects', $course->subjects ?? []);
            $subjects = is_array($subjects) ? $subjects : [];
        @endphp
        @foreach ($subjects as $i => $subject)
            <div class="subject-row card mb-2">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="small fw-bold"><i class="bi bi-journal-text me-1"></i> مادة {{ $i + 1 }}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="bi bi-trash"></i></button>
                </div>
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center mb-2">
                        <input type="hidden" name="subjects[{{ $i }}][id]" value="{{ $subject['id'] ?? '' }}">
                        <div class="col-md-3">
                            <input type="text" name="subjects[{{ $i }}][name_ar]" class="form-control form-control-sm" placeholder="اسم المادة AR *" value="{{ $subject['name_ar'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="subjects[{{ $i }}][name_en]" class="form-control form-control-sm" placeholder="Name EN" dir="ltr" value="{{ $subject['name_en'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" step="0.5" min="0" name="subjects[{{ $i }}][hours]" class="form-control form-control-sm" placeholder="الساعات" value="{{ $subject['hours'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" step="0.5" min="0" name="subjects[{{ $i }}][weight]" class="form-control form-control-sm" placeholder="الوزن" value="{{ $subject['weight'] ?? '' }}">
                        </div>
                    </div>

                    <div class="bg-light rounded p-2">
                        <div class="exams-container">
                            @php
                                $exams = $subject['exams'] ?? [];
                            @endphp
                            @foreach ($exams as $j => $exam)
                                <div class="exam-row row g-2 align-items-center mb-1">
                                    <input type="hidden" name="subjects[{{ $i }}][exams][{{ $j }}][id]" value="{{ $exam['id'] ?? '' }}">
                                    <div class="col-md-4">
                                        <input type="text" name="subjects[{{ $i }}][exams][{{ $j }}][name_ar]" class="form-control form-control-sm" placeholder="الامتحان (قبلي/بعدي/دوري/نهائي)" value="{{ $exam['name_ar'] ?? '' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="subjects[{{ $i }}][exams][{{ $j }}][type]" class="form-select form-select-sm">
                                            <option value="">نوع الامتحان</option>
                                            @foreach ($examTypes as $val => $label)
                                                <option value="{{ $val }}" {{ ($exam['type'] ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" step="0.5" min="0" name="subjects[{{ $i }}][exams][{{ $j }}][max_score]" class="form-control form-control-sm" placeholder="العلامة العليا" value="{{ $exam['max_score'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-exam-btn"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-exam-btn">
                            <i class="bi bi-plus-lg me-1"></i> إضافة امتحان للمادة
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <hr class="my-4">

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><i class="bi bi-calendar-week ms-1"></i> عروض المقرر (بمدرّس / فترة)</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addOfferingBtn">
            <i class="bi bi-plus-lg me-1"></i> إضافة عرض
        </button>
    </div>
    <p class="text-muted small mb-3">عدة أنواع تدريب لذات المقرر (مثال: ICDL صباحاً بمدرّس، ICDL مساءً بآخر) أو دورة بفترة معينة.</p>

    <div id="offeringsContainer">
        @php
            $offerings = old('offerings', $course->offerings ?? []);
            $offerings = is_array($offerings) ? $offerings : [];
        @endphp
        @foreach ($offerings as $i => $offering)
            <div class="offering-row card mb-2">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <input type="hidden" name="offerings[{{ $i }}][id]" value="{{ $offering['id'] ?? '' }}">
                        <div class="col-md-3">
                            <select name="offerings[{{ $i }}][period_id]" class="form-select form-select-sm">
                                <option value="">الفترة</option>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->id }}" {{ ($offering['period_id'] ?? '') == $period->id ? 'selected' : '' }}>{{ $period->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="offerings[{{ $i }}][name_ar]" class="form-control form-control-sm" placeholder="اسم العرض (مثال: صباحية / نوع تدريب)" value="{{ $offering['name_ar'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <select name="offerings[{{ $i }}][instructor_id]" class="form-select form-select-sm">
                                <option value="">المدرّس (اختياري)</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" {{ ($offering['instructor_id'] ?? '') == $instructor->id ? 'selected' : '' }}>
                                        {{ $instructor->first_name_ar }} {{ $instructor->last_name_ar }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="offerings[{{ $i }}][session_time]" class="form-control form-control-sm" placeholder="الوقت" value="{{ $offering['session_time'] ?? '' }}">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-offering-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.courses.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const instructors = @json($instructors->map(fn ($i) => ['id' => $i->id, 'name' => $i->first_name_ar . ' ' . $i->last_name_ar]));
    const periods = @json($periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name_ar]));
    const examTypes = @json($examTypes);
    const levelTypes = @json($levelTypes);

    let levelIndex = {{ count($levels) }};
    let subjectIndex = {{ count($subjects) }};
    let offeringIndex = {{ count($offerings) }};

    const examRowTemplate = function (si, ei) {
        const typeOptions = Object.entries(examTypes).map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
        return `
        <div class="exam-row row g-2 align-items-center mb-1">
            <input type="hidden" name="subjects[${si}][exams][${ei}][id]" value="">
            <div class="col-md-4">
                <input type="text" name="subjects[${si}][exams][${ei}][name_ar]" class="form-control form-control-sm" placeholder="الامتحان (قبلي/بعدي/دوري/نهائي)">
            </div>
            <div class="col-md-3">
                <select name="subjects[${si}][exams][${ei}][type]" class="form-select form-select-sm">
                    <option value="">نوع الامتحان</option>
                    ${typeOptions}
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.5" min="0" name="subjects[${si}][exams][${ei}][max_score]" class="form-control form-control-sm" placeholder="العلامة العليا">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-exam-btn"><i class="bi bi-trash"></i></button>
            </div>
        </div>`;
    };

    const subjectTemplate = (i) => `
        <div class="subject-row card mb-2">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span class="small fw-bold"><i class="bi bi-journal-text me-1"></i> مادة ${i + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="bi bi-trash"></i></button>
            </div>
            <div class="card-body py-2">
                <div class="row g-2 align-items-center mb-2">
                    <input type="hidden" name="subjects[${i}][id]" value="">
                    <div class="col-md-3">
                        <input type="text" name="subjects[${i}][name_ar]" class="form-control form-control-sm" placeholder="اسم المادة AR *">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="subjects[${i}][name_en]" class="form-control form-control-sm" placeholder="Name EN" dir="ltr">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.5" min="0" name="subjects[${i}][hours]" class="form-control form-control-sm" placeholder="الساعات">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.5" min="0" name="subjects[${i}][weight]" class="form-control form-control-sm" placeholder="الوزن">
                    </div>
                </div>
                <div class="bg-light rounded p-2">
                    <div class="exams-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-exam-btn">
                        <i class="bi bi-plus-lg me-1"></i> إضافة امتحان للمادة
                    </button>
                </div>
            </div>
        </div>`;

    const levelTemplate = (i) => `
        <div class="col-md-6 level-row">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <input type="hidden" name="levels[${i}][id]" value="">
                        <div class="col-md-5">
                            <input type="text" name="levels[${i}][name_ar]" class="form-control form-control-sm" placeholder="اسم المستوى (مثال: مستوى أول) *">
                        </div>
                        <div class="col-md-3">
                            <select name="levels[${i}][type]" class="form-select form-select-sm">
                                ${Object.entries(levelTypes).map(([v, l]) => `<option value="${v}">${l}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="levels[${i}][code]" class="form-control form-control-sm" placeholder="رمز">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-level-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    const offeringTemplate = (i) => {
        const periodOptions = periods.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        const instructorOptions = instructors.map(ins => `<option value="${ins.id}">${ins.name}</option>`).join('');
        return `
        <div class="offering-row card mb-2">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <input type="hidden" name="offerings[${i}][id]" value="">
                    <div class="col-md-3">
                        <select name="offerings[${i}][period_id]" class="form-select form-select-sm">
                            <option value="">الفترة</option>
                            ${periodOptions}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="offerings[${i}][name_ar]" class="form-control form-control-sm" placeholder="اسم العرض (مثال: صباحية / نوع تدريب)">
                    </div>
                    <div class="col-md-3">
                        <select name="offerings[${i}][instructor_id]" class="form-select form-select-sm">
                            <option value="">المدرّس (اختياري)</option>
                            ${instructorOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="offerings[${i}][session_time]" class="form-control form-control-sm" placeholder="الوقت">
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-offering-btn"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>`;
    };

    document.getElementById('addLevelBtn').addEventListener('click', function () {
        document.getElementById('levelsContainer').insertAdjacentHTML('beforeend', levelTemplate(levelIndex));
        levelIndex++;
    });

    document.getElementById('addSubjectBtn').addEventListener('click', function () {
        document.getElementById('subjectsContainer').insertAdjacentHTML('beforeend', subjectTemplate(subjectIndex));
        subjectIndex++;
    });

    document.getElementById('addOfferingBtn').addEventListener('click', function () {
        document.getElementById('offeringsContainer').insertAdjacentHTML('beforeend', offeringTemplate(offeringIndex));
        offeringIndex++;
    });

    document.addEventListener('click', function (e) {
        const subjectRow = e.target.closest('.subject-row');
        if (e.target.closest('.add-exam-btn') && subjectRow) {
            const container = subjectRow.querySelector('.exams-container');
            const si = [...document.querySelectorAll('.subject-row')].indexOf(subjectRow);
            container.insertAdjacentHTML('beforeend', examRowTemplate(si, container.querySelectorAll('.exam-row').length));
            return;
        }

        if (e.target.closest('.remove-subject-btn')) {
            subjectRow.remove();
        }
        if (e.target.closest('.remove-exam-btn')) {
            e.target.closest('.exam-row').remove();
        }
        if (e.target.closest('.remove-level-btn')) {
            e.target.closest('.level-row').remove();
        }
        if (e.target.closest('.remove-offering-btn')) {
            e.target.closest('.offering-row').remove();
        }
    });
});
</script>
@endpush
@endsection