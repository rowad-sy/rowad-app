@extends('admin.logistics.layouts.master')

@section('title', isset($item) ? 'تعديل مادة' : 'إضافة مادة')

@section('logistics-content')
<div class="page-header">
    <h4>{{ isset($item) ? 'تعديل المادة' : 'إضافة مادة' }}</h4>
    <p>
        <a href="{{ route('admin.logistics.warehouses.items', $warehouse) }}" class="text-decoration-none">{{ $warehouse->name }}</a>
        / {{ isset($item) ? $item->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($item) ? route('admin.logistics.warehouses.items.update', [$warehouse, $item]) : route('admin.logistics.warehouses.items.store', $warehouse) }}">
                @csrf
                @if (isset($item))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $item->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" name="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', $item->quantity ?? '1') }}" required min="0" step="any">
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الوحدة <span class="text-danger">*</span></label>
                        <input type="text" name="unit"
                               class="form-control @error('unit') is-invalid @enderror"
                               value="{{ old('unit', $item->unit ?? '') }}" required placeholder="قطعة, كرتون, كغم">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.logistics.warehouses.items', $warehouse) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
