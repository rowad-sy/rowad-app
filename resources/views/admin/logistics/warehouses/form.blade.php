@extends('admin.logistics.layouts.master')

@section('title', isset($warehouse) ? 'تعديل مخزن' : 'إضافة مخزن')

@section('logistics-content')
<x-page-header :title="isset($warehouse) ? 'تعديل المخزن' : 'إضافة مخزن'"
               :breadcrumb="[['label' => 'المخازن', 'url' => route('admin.logistics.warehouses.index')], ['label' => isset($warehouse) ? $warehouse->name : 'جديد']]" />

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($warehouse) ? route('admin.logistics.warehouses.update', $warehouse) : route('admin.logistics.warehouses.store') }}">
                @csrf
                @if (isset($warehouse))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $warehouse->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">المركز</label>
                    <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                        <option value="">— اختر المركز —</option>
                        @foreach ($centers ?? [] as $center)
                            <option value="{{ $center->id }}" {{ old('center_id', $warehouse->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                        @endforeach
                    </select>
                    @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $warehouse->notes ?? '') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.logistics.warehouses.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
