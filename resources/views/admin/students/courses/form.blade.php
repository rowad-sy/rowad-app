@extends('admin.layouts.master')

@section('title', isset($course) ? 'تعديل مقرر' : 'إضافة مقرر')

@section('content')
<div class="page-header">
    <h4>{{ isset($course) ? 'تعديل المقرر' : 'إضافة مقرر جديد' }}</h4>
    <p>
        <a href="{{ route('admin.students.courses.index') }}" class="text-decoration-none">المقررات</a>
        / {{ isset($course) ? $course->name_ar : 'جديد' }}
    </p>
</div>

<form method="POST" action="{{ isset($course) ? route('admin.students.courses.update', $course) : route('admin.students.courses.store') }}">
    @csrf
    @if (isset($course))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $course->project_id ?? $defaultProjectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
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
            <textarea name="description" rows="2" class="form-control">{{ old('description', $course->description ?? '') }}</textarea>
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

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.courses.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
