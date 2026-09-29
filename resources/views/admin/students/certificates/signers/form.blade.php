@extends('admin.layouts.master')

@section('title', isset($signer) ? 'تعديل موقع' : 'إضافة موقع')

@section('content')
<x-page-header :title="isset($signer) ? 'تعديل الموقع: ' . $signer->name_ar : 'إضافة موقع جديد'"
               :breadcrumb="[['label' => 'الموقعون', 'url' => route('admin.students.certificates.signers.index')], ['label' => isset($signer) ? $signer->name_ar : 'جديد']]" />

<form method="POST" enctype="multipart/form-data"
      action="{{ isset($signer) ? route('admin.students.certificates.signers.update', $signer) : route('admin.students.certificates.signers.store') }}">
    @csrf
    @if (isset($signer))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">الاسم <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                   value="{{ old('name_ar', $signer->name_ar ?? '') }}" required>
            @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">الدور <span class="text-danger">*</span></label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                @foreach (\App\Models\Admin\Student\CertificateSigner::ROLES as $key => $label)
                    <option value="{{ $key }}" {{ old('role', $signer->role ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">صورة التوقيع</label>
            <input type="file" name="signature" accept="image/png,image/jpeg" class="form-control @error('signature') is-invalid @enderror">
            @error('signature') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">PNG بخلفية شفافة هو الأفضل. يظهر في الشهادة عند الطباعة.</div>
        </div>

        @if (isset($signer) && $signer->signature_path)
        <div class="col-12">
            <label class="form-label">التوقيع الحالي</label>
            <div>
                <img src="{{ asset('storage/' . $signer->signature_path) }}" style="max-height:80px;max-width:250px;object-fit:contain;background:#f8fafc;border:1px solid #dee2e6;border-radius:6px;padding:4px;" alt="التوقيع الحالي">
            </div>
        </div>
        @endif
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.certificates.signers.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
