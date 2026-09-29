@extends('admin.layouts.master')

@section('title', isset($equipment) ? 'تعديل معدة' : 'إضافة معدة')

@section('content')
<x-page-header :title="isset($equipment) ? 'تعديل المعدة' : 'إضافة معدة'"
               :breadcrumb="[['label' => 'المعدات التقنية', 'url' => route('admin.tech.equipment.index')], ['label' => isset($equipment) ? $equipment->name : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($equipment) ? route('admin.tech.equipment.update', $equipment) : route('admin.tech.equipment.store') }}">
                @csrf
                @if (isset($equipment))
                    @method('PUT')
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $equipment->name ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <input type="text" name="type"
                               class="form-control @error('type') is-invalid @enderror"
                               value="{{ old('type', $equipment->type ?? '') }}" required
                               placeholder="مثال: حاسوب, طابعة, راوتر">
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">الرقم التسلسلي</label>
                        <input type="text" name="serial_number"
                               class="form-control @error('serial_number') is-invalid @enderror"
                               value="{{ old('serial_number', $equipment->serial_number ?? '') }}">
                        @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة الفنية <span class="text-danger">*</span></label>
                        <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
                            <option value="a" {{ (old('condition', $equipment->condition ?? '') == 'a') ? 'selected' : '' }}>ممتاز</option>
                            <option value="b" {{ (old('condition', $equipment->condition ?? '') == 'b') ? 'selected' : '' }}>جيد</option>
                            <option value="c" {{ (old('condition', $equipment->condition ?? '') == 'c') ? 'selected' : '' }}>متوسط</option>
                            <option value="d" {{ (old('condition', $equipment->condition ?? '') == 'd') ? 'selected' : '' }}>سيئ</option>
                            <option value="e" {{ (old('condition', $equipment->condition ?? '') == 'e') ? 'selected' : '' }}>تالف</option>
                        </select>
                        @error('condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الغرفة</label>
                        <input type="text" name="room"
                               class="form-control @error('room') is-invalid @enderror"
                               value="{{ old('room', $equipment->room ?? '') }}"
                               placeholder="رقم الغرفة أو موقعها">
                        @error('room') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">اختر المركز</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ (old('center_id', $equipment->center_id ?? $defaultCenterId ?? '') == $center->id) ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">اختر المشروع</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ (old('project_id', $equipment->project_id ?? $defaultProjectId ?? '') == $project->id) ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $equipment->notes ?? '') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.tech.equipment.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
