@extends('admin.layouts.master')

@section('title', isset($department) ? 'تعديل إدارة' : 'إضافة إدارة')

@section('content')
<div class="page-header">
    <h4>{{ isset($department) ? 'تعديل الإدارة' : 'إضافة إدارة' }}</h4>
    <p>
        <a href="{{ route('admin.departments.index') }}" class="text-decoration-none">الإدارات</a>
        / {{ isset($department) ? $department->name_ar : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($department) ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
                @csrf
                @if (isset($department))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم (AR) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar"
                           class="form-control @error('name_ar') is-invalid @enderror"
                           value="{{ old('name_ar', $department->name_ar ?? '') }}" required>
                    @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الاسم (EN)</label>
                    <input type="text" name="name_en"
                           class="form-control @error('name_en') is-invalid @enderror"
                           value="{{ old('name_en', $department->name_en ?? '') }}" dir="ltr">
                    @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $department->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                               {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">فعال</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
