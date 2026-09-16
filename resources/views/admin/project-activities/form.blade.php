@extends('admin.layouts.master')

@section('title', isset($activity) ? 'تعديل نشاط' : 'نشاط جديد')

@section('content')
<div class="page-header">
    <h4>{{ isset($activity) ? 'تعديل النشاط' : 'تسجيل نشاط جديد' }}</h4>
    <p>
        <a href="{{ route('admin.project-activities.index') }}" class="text-decoration-none">الأنشطة</a> / {{ isset($activity) ? 'تعديل' : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ isset($activity) ? route('admin.project-activities.update', $activity) : route('admin.project-activities.store') }}">
                @csrf
                @isset($activity) @method('PUT') @endisset

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المسؤول عن النشاط</label>
                        <input type="text" name="responsible" class="form-control" value="{{ old('responsible', $activity->responsible ?? '') }}"
                               placeholder="اسم المسؤول عن النشاط">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ النشاط <span class="text-danger">*</span></label>
                        <input type="date" name="activity_date" class="form-control @error('activity_date') is-invalid @enderror"
                               value="{{ old('activity_date', ($activity ?? null)?->activity_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                        @error('activity_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الجهة المستفيدة</label>
                    <input type="text" name="beneficiary" class="form-control" value="{{ old('beneficiary', $activity->beneficiary ?? '') }}"
                           placeholder="مثال: أطفال مركز الرواد / المجتمع المضيف...">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">عدد المستفيدين ذكور</label>
                        <input type="number" name="male_count" min="0" class="form-control" value="{{ old('male_count', $activity->male_count ?? 0) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">عدد المستفيدين إناث</label>
                        <input type="number" name="female_count" min="0" class="form-control" value="{{ old('female_count', $activity->female_count ?? 0) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">سير النشاط</label>
                    <textarea name="progress" rows="4" class="form-control"
                              placeholder="وصف مختصر لسير النشاط وما تم إنجازه...">{{ old('progress', $activity->progress ?? '') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">المعوقات <span class="text-muted small">(اختياري)</span></label>
                    <textarea name="obstacles" rows="3" class="form-control"
                              placeholder="أي معوقات واجهت النشاط...">{{ old('obstacles', $activity->obstacles ?? '') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $activity->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ old('center_id', $activity->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button class="btn btn-primary">{{ isset($activity) ? 'حفظ التعديلات' : 'تسجيل النشاط' }}</button>
                <a href="{{ route('admin.project-activities.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection