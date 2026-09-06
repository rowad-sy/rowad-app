@extends('admin.layouts.master')

@section('title', isset($cohort) ? 'تعديل فوج' : 'إضافة فوج')

@section('content')
@php $isEdit = isset($cohort); if (!$isEdit) { $cohort = new \App\Models\Admin\Cohort(); } @endphp
<div class="page-header">
    <h4>{{ $isEdit ? 'تعديل الفوج' : 'إضافة فوج' }}</h4>
    <p>
        <a href="{{ route('admin.cohorts.index') }}" class="text-decoration-none">الأفواج</a>
        / {{ $isEdit ? $cohort->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ $isEdit ? route('admin.cohorts.update', $cohort) : route('admin.cohorts.store') }}">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label">المشروع <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                        <option value="">اختر مشروع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $cohort->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">اسم الفوج <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cohort->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الفترة</label>
                        <select name="shift" class="form-select">
                            <option value="">—</option>
                            @foreach (['صباحي', 'مسائي', 'صباحي ومسائي'] as $shift)
                                <option value="{{ $shift }}" {{ old('shift', $cohort->shift ?? '') == $shift ? 'selected' : '' }}>{{ $shift }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الكود</label>
                        <input type="text" name="code" class="form-control" dir="ltr" value="{{ old('code', $cohort->code ?? '') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">مسؤول الفوج</label>
                    <select name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('manager_id', $cohort->manager_id ?? '') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name_ar }} {{ $employee->last_name_ar }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">الشخص المسؤول عن هذا الفوج</small>
                    @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active"
                               {{ (bool) old('is_active', $cohort->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">فوج نشط</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $cohort->notes ?? '') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.cohorts.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
