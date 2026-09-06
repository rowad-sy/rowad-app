@extends('admin.layouts.master')

@section('title', isset($plan) ? 'تعديل خطة تدريبية' : 'إنشاء خطة تدريبية')

@section('content')
@php
    $isEdit = isset($plan) && $plan->exists;
    $days = [1 => 'السبت', 2 => 'الأحد', 3 => 'الاثنين', 4 => 'الثلاثاء', 5 => 'الأربعاء', 6 => 'الخميس', 7 => 'الجمعة'];
    if (!$isEdit) {
        $plan = new \App\Models\Admin\Student\TrainingPlan();
    }
    $oldLessons = old('lessons', $plan->lessons->map(fn ($l) => $l->toArray())->toArray());
    $oldLessons = is_array($oldLessons) ? $oldLessons : [];
@endphp
<div class="page-header">
    <h4>{{ $isEdit ? 'تعديل الخطة التدريبية' : 'إنشاء خطة تدريبية جديدة' }}</h4>
    <p>
        <a href="{{ route('admin.students.training-plans.index') }}" class="text-decoration-none">الخطط التدريبية</a>
        / {{ $isEdit ? $plan->name_ar : 'جديد' }}
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

@if (session('conflicts') && count(session('conflicts')))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>تنبيه تعارض المدرّسين:</strong>
        <ul class="mb-0 mt-1">
            @foreach (session('conflicts') as $conflict)
                <li>{{ $conflict }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (isset($course) && $course)
    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="bi bi-book fs-5"></i>
        <div>
            <strong>تم تجهيز النموذج تلقائياً من المقرر:</strong> {{ $course->name_ar }}
            <span class="text-muted small">— المشروع وقوائم الصفوف/المستويات والمواد مقيّدة بعناصر هذا المقرر.</span>
        </div>
    </div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.students.training-plans.update', $plan) : route('admin.students.training-plans.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المشروع <span class="text-danger">*</span></label>
            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $plan->project_id ?? $defaultProjectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">اسم الخطة AR <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $plan->name_ar ?? '') }}" required>
            @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم EN</label>
            <input type="text" name="name_en" class="form-control" dir="ltr" value="{{ old('name_en', $plan->name_en ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $plan->start_date?->format('Y-m-d') ?? '') }}" required>
            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">تاريخ النهاية <span class="text-danger">*</span></label>
            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $plan->end_date?->format('Y-m-d') ?? '') }}" required>
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                @foreach (['draft' => 'مسودة', 'active' => 'نشطة', 'completed' => 'مكتملة', 'cancelled' => 'ملغاة'] as $val => $label)
                    <option value="{{ $val }}" {{ old('status', $plan->status ?? 'active') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">الوصف</label>
            <textarea name="description" rows="2" class="form-control">{{ old('description', $plan->description ?? '') }}</textarea>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><i class="bi bi-calendar-week ms-1"></i> دروس الجدول الأسبوعي</h5>
        <div class="d-flex gap-2">
            <span class="text-muted small align-self-center">أضف درساً لكل أسبوع/يوم/وقت</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addLessonBtn">
                <i class="bi bi-plus-lg me-1"></i> إضافة درس
            </button>
        </div>
    </div>
    <p class="text-muted small mb-3">سيُمنع تكليف نفس المدرّس بصفّين في نفس اليوم والوقت.</p>

    <div id="lessonsContainer">
        @foreach ($oldLessons as $i => $lesson)
            <div class="lesson-row card mb-2">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <input type="hidden" name="lessons[{{ $i }}][id]" value="{{ $lesson['id'] ?? '' }}">
                        <div class="col-md-1">
                            <input type="number" name="lessons[{{ $i }}][week_number]" class="form-control form-control-sm" placeholder="أسبوع" min="1" value="{{ $lesson['week_number'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <select name="lessons[{{ $i }}][day_of_week]" class="form-select form-select-sm">
                                <option value="">اليوم</option>
                                @foreach ($days as $d => $label)
                                    <option value="{{ $d }}" {{ ($lesson['day_of_week'] ?? '') == $d ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="lessons[{{ $i }}][academic_level_id]" class="form-select form-select-sm">
                                <option value="">الصف/المستوى</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" {{ ($lesson['academic_level_id'] ?? '') == $level->id ? 'selected' : '' }}>{{ $level->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="lessons[{{ $i }}][subject_id]" class="form-select form-select-sm">
                                <option value="">المادة</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ ($lesson['subject_id'] ?? '') == $subject->id ? 'selected' : '' }}>{{ $subject->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="lessons[{{ $i }}][instructor_id]" class="form-select form-select-sm">
                                <option value="">المدرّس</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" {{ ($lesson['instructor_id'] ?? '') == $instructor->id ? 'selected' : '' }}>
                                        {{ $instructor->first_name_ar }} {{ $instructor->last_name_ar }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input type="time" name="lessons[{{ $i }}][start_time]" class="form-control form-control-sm" value="{{ $lesson['start_time'] ?? '' }}">
                        </div>
                        <div class="col-md-1">
                            <input type="time" name="lessons[{{ $i }}][end_time]" class="form-control form-control-sm" value="{{ $lesson['end_time'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="lessons[{{ $i }}][lesson_name]" class="form-control form-control-sm" placeholder="اسم الدرس" value="{{ $lesson['lesson_name'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="lessons[{{ $i }}][location]" class="form-control form-control-sm" placeholder="المكان" value="{{ $lesson['location'] ?? '' }}">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-lesson-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الخطة' }}
        </button>
        <a href="{{ route('admin.students.training-plans.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const days = @json($days);
    const levels = @json($levels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name_ar]));
    const subjects = @json($subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar]));
    const instructors = @json($instructors->map(fn ($i) => ['id' => $i->id, 'name' => $i->first_name_ar . ' ' . $i->last_name_ar]));

    let lessonIndex = {{ count($oldLessons) }};

    const rowTemplate = () => `
        <div class="lesson-row card mb-2">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <input type="hidden" name="lessons[${lessonIndex}][id]" value="">
                    <div class="col-md-1">
                        <input type="number" name="lessons[${lessonIndex}][week_number]" class="form-control form-control-sm" placeholder="أسبوع" min="1">
                    </div>
                    <div class="col-md-2">
                        <select name="lessons[${lessonIndex}][day_of_week]" class="form-select form-select-sm">
                            <option value="">اليوم</option>
                            ${Object.entries(days).map(([d, l]) => `<option value="${d}">${l}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="lessons[${lessonIndex}][academic_level_id]" class="form-select form-select-sm">
                            <option value="">الصف/المستوى</option>
                            ${levels.map(l => `<option value="${l.id}">${l.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="lessons[${lessonIndex}][subject_id]" class="form-select form-select-sm">
                            <option value="">المادة</option>
                            ${subjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="lessons[${lessonIndex}][instructor_id]" class="form-select form-select-sm">
                            <option value="">المدرّس</option>
                            ${instructors.map(i => `<option value="${i.id}">${i.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input type="time" name="lessons[${lessonIndex}][start_time]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1">
                        <input type="time" name="lessons[${lessonIndex}][end_time]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="lessons[${lessonIndex}][lesson_name]" class="form-control form-control-sm" placeholder="اسم الدرس">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="lessons[${lessonIndex}][location]" class="form-control form-control-sm" placeholder="المكان">
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-lesson-btn"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>`;

    document.getElementById('addLessonBtn').addEventListener('click', function () {
        document.getElementById('lessonsContainer').insertAdjacentHTML('beforeend', rowTemplate());
        lessonIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-lesson-btn')) {
            e.target.closest('.lesson-row').remove();
        }
    });
});
</script>
@endpush
@endsection
