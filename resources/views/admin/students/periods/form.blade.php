@extends('admin.layouts.master')

@section('title', isset($period) ? 'تعديل فترة' : 'إضافة فترة')

@section('content')
<x-page-header :title="isset($period) ? 'تعديل الفترة' : 'إضافة فترة جديدة'"
               :breadcrumb="[['label' => 'الفترات', 'url' => route('admin.students.periods.index')], ['label' => isset($period) ? $period->name_ar : 'جديد']]" />

<form method="POST" action="{{ isset($period) ? route('admin.students.periods.update', $period) : route('admin.students.periods.store') }}">
    @csrf
    @if (isset($period))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $period->project_id ?? $defaultProjectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">الاسم <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                   value="{{ old('name_ar', $period->name_ar ?? '') }}" required>
            @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">السنة <span class="text-danger">*</span></label>
            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror"
                   value="{{ old('year', $period->year ?? date('Y')) }}" min="2000" max="2100" required>
            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div class="form-check form-switch mt-2">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                       {{ old('is_active', $period->is_active ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">نشطة</label>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">تاريخ البداية</label>
            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                   value="{{ old('start_date', $period->start_date ?? '') }}">
            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">تاريخ النهاية</label>
            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                   value="{{ old('end_date', $period->end_date ?? '') }}">
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label class="form-label">المقررات المرتبطة</label>
            <div class="row g-2">
                @forelse ($courses as $course)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" class="form-check-input"
                                   id="course_{{ $course->id }}"
                                   {{ isset($period) && in_array($course->id, old('course_ids', $period->courses->pluck('id')->toArray())) ? 'checked' : '' }}>
                            <label class="form-check-label" for="course_{{ $course->id }}">
                                {{ $course->name_ar }}
                                <small class="text-muted">({{ $course->duration ? $course->duration . 'ي' : '—' }})</small>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted small">لا توجد مقررات متاحة. قم بإضافة مقررات أولاً.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.periods.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
