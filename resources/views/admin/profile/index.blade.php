@extends('admin.layouts.master')

@section('title', 'الملف الشخصي')

@section('content')
<div class="page-header">
    <h4>الملف الشخصي</h4>
    <p>عرض وتعديل معلومات حسابك</p>
</div>

<div class="row g-4">
    {{-- معلومات المستخدم --}}
    <div class="col-md-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-person-circle me-1"></i> معلومات الحساب
            </h5>

            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                    <small class="text-muted">لا يمكن تغيير البريد الإلكتروني</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">تاريخ التسجيل</label>
                    <input type="text" class="form-control"
                           value="{{ $user->created_at->locale('ar')->translatedFormat('l d F Y') }}" disabled>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> حفظ التغييرات
                </button>
            </form>
        </div>
    </div>

    {{-- تغيير كلمة المرور --}}
    <div class="col-md-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-lock me-1"></i> تغيير كلمة المرور
            </h5>

            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                    <input type="password" name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror" required>
                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-key me-1"></i> تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
