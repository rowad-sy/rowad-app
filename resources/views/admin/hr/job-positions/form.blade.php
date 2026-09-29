@extends('admin.layouts.master')

@section('title', isset($jobPosition) ? 'تعديل منصب وظيفي' : 'إضافة منصب وظيفي')

@section('content')
<x-page-header :title="isset($jobPosition) ? 'تعديل المنصب الوظيفي' : 'إضافة منصب وظيفي'"
               :breadcrumb="[['label' => 'المناصب الوظيفية', 'url' => route('admin.hr.job-positions.index')], ['label' => isset($jobPosition) ? $jobPosition->title_ar : 'جديد']]" />

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($jobPosition) ? route('admin.hr.job-positions.update', $jobPosition) : route('admin.hr.job-positions.store') }}">
                @csrf
                @if (isset($jobPosition))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">المسمى AR <span class="text-danger">*</span></label>
                    <input type="text" name="title_ar"
                           class="form-control @error('title_ar') is-invalid @enderror"
                           value="{{ old('title_ar', $jobPosition->title_ar ?? '') }}" required>
                    @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">المسمى EN</label>
                    <input type="text" name="title_en"
                           class="form-control @error('title_en') is-invalid @enderror"
                           value="{{ old('title_en', $jobPosition->title_en ?? '') }}" dir="ltr">
                    @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف AR</label>
                    <textarea name="description_ar" rows="3"
                              class="form-control @error('description_ar') is-invalid @enderror">{{ old('description_ar', $jobPosition->description_ar ?? '') }}</textarea>
                    @error('description_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف EN</label>
                    <textarea name="description_en" rows="3"
                              class="form-control @error('description_en') is-invalid @enderror">{{ old('description_en', $jobPosition->description_en ?? '') }}</textarea>
                    @error('description_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.hr.job-positions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
