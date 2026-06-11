@extends('admin.layouts.master')

@section('title', isset($center) ? 'تعديل مركز' : 'إضافة مركز')

@section('content')
<div class="page-header">
    <h4>{{ isset($center) ? 'تعديل المركز' : 'إضافة مركز' }}</h4>
    <p>
        <a href="{{ route('admin.centers.index') }}" class="text-decoration-none">المراكز</a>
        / {{ isset($center) ? $center->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($center) ? route('admin.centers.update', $center) : route('admin.centers.store') }}">
                @csrf
                @if (isset($center))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">اسم المركز <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $center->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">العنوان</label>
                    <input type="text" name="address"
                           class="form-control @error('address') is-invalid @enderror"
                           value="{{ old('address', $center->address ?? '') }}">
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الهاتف</label>
                    <input type="text" name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $center->phone ?? '') }}" dir="ltr">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.centers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
