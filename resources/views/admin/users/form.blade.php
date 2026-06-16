@extends('admin.layouts.master')

@section('title', isset($user) ? 'تعديل مستخدم' : 'إضافة مستخدم')

@section('content')
<div class="page-header">
    <h4>{{ isset($user) ? 'تعديل المستخدم' : 'إضافة مستخدم' }}</h4>
    <p>
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">المستخدمين</a>
        / {{ isset($user) ? $user->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if (isset($user))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email ?? '') }}" required dir="ltr">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        كلمة المرور
                        @if (!isset($user))
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           {{ isset($user) ? '' : 'required' }}>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if (isset($user))
                        <div class="form-text text-muted">اتركه فارغاً إذا لم ترد تغيير كلمة المرور</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" class="form-control"
                           {{ isset($user) ? '' : 'required' }}>
                </div>

                <div class="mb-3">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror">
                        <option value="">— عادي —</option>
                        <option value="employee" {{ old('type', $user->type ?? '') == 'employee' ? 'selected' : '' }}>موظف</option>
                        <option value="beneficiary" {{ old('type', $user->type ?? '') == 'beneficiary' ? 'selected' : '' }}>مستفيد</option>
                        <option value="student" {{ old('type', $user->type ?? '') == 'student' ? 'selected' : '' }}>طالب</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                           {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">نشط</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
