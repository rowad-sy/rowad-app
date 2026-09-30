@extends('admin.layouts.master')

@section('title', 'تغيير كلمة المرور الإجباري')

@section('content')
<x-page-header title="تغيير كلمة المرور" description="يجب عليك تغيير كلمة المرور قبل المتابعة" />

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-card">
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-shield-lock me-1"></i>
                تم إنشاء حسابك من قبل الإدارة مع كلمة مرور مؤقتة.
                من أجل أن تستطيع استخدام النظام، يجب عليك تغيير كلمة المرور الخاصة بك الآن.
            </div>

            <form method="POST" action="{{ route('admin.password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                    <input type="password" name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror" required autofocus>
                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text text-muted">يجب أن تكون 8 أحرف على الأقل</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key me-1"></i> تغيير كلمة المرور
                    </button>
                </div>
            </form>

            <div class="mt-3">
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 text-decoration-none">
                        <i class="bi bi-box-arrow-left me-1"></i> تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
