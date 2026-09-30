@extends('admin.layouts.master')

@section('title', 'الملف الشخصي')

@section('content')
<x-page-header title="الملف الشخصي" description="عرض وتعديل معلومات حسابك">
        <x-audit-history :model="'App\Models\User'" :model-id="$user->id" />
        @if ($employee)
            <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-primary">
                <i class="bi bi-person-workspace me-1"></i> الملف الوظيفي
            </a>
        @endif
        @if ($student)
            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-primary">
                <i class="bi bi-mortarboard me-1"></i> الملف الدراسي
            </a>
        @endif
</x-page-header>

@if ($user->must_change_password)
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-shield-lock me-1"></i>
        يجب عليك تغيير كلمة المرور الخاصة بك للتمكن من استخدام النظام.
        <a href="{{ route('admin.password.change') }}" class="alert-link">تغيير كلمة المرور الآن</a>
    </div>
@endif

@if (! $user->is_active && ! $user->hasVerifiedEmail())
    <div class="alert alert-warning" role="alert">
        <h5 class="alert-heading d-flex align-items-center mb-2">
            <i class="bi bi-envelope-exclamation me-2"></i> حسابك غير مفعّل بعد
        </h5>
        <p class="mb-2">لتفعيل حسابك، يرجى الضغط على رابط التفعيل المرسل إلى بريدك الإلكتروني ({{ $user->email }}).</p>
        <p class="mb-2 small">إذا لم تجد الرسالة، يرجى التحقق من مجلد الرسائل غير المرغوب بها (Spam).</p>
        @php
            $maxResends = config('activation.max_resends');
            $remainingResends = max(0, $maxResends - (int) $user->activation_email_count);
        @endphp
        @if ($remainingResends > 0)
            <form method="POST" action="{{ route('activation.resend') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> إعادة إرسال بريد التفعيل
                </button>
            </form>
            <small class="text-muted ms-2">عدد المحاولات المتبقية: {{ $remainingResends }}</small>
        @else
            <p class="mb-0 text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                لقد وصلت إلى الحد الأقصى لإعادة إرسال بريد التفعيل. يرجى التواصل مع الإدارة.
            </p>
        @endif
    </div>
@elseif (! $user->is_active)
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-person-x me-1"></i>
        تم إيقاف حسابك من قبل الإدارة. يرجى التواصل مع الإدارة لإعادة تفعيله.
    </div>
@endif

<div class="row g-4">
    {{-- معلومات المستخدم --}}
    <div class="col-md-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-person-circle me-1"></i> معلومات الحساب
            </h5>

            @if (($user->type === 'employee' || $user->type === 'super-admin') && $employee)
            <div class="mb-3 p-3 rounded" style="background:var(--status-info-bg);color:var(--color-text-main);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-person-workspace text-primary"></i>
                    <span class="fw-bold">موظف</span>
                    <span class="badge bg-primary">{{ $employee->employee_code }}</span>
                </div>
                <small class="text-muted d-block">{{ $employee->first_name_ar }} {{ $employee->last_name_ar }}</small>
                @if ($employee->center || $employee->department)
                <small class="text-muted d-block">
                    {{ $employee->center?->name ?? '' }}
                    {{ $employee->center && $employee->department ? '|' : '' }}
                    {{ $employee->department?->name_ar ?? '' }}
                </small>
                @endif
                <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="bi bi-eye me-1"></i> عرض الملف الوظيفي
                </a>
            </div>
            @endif

            @if ($user->type === 'student' && $student)
            <div class="mb-3 p-3 rounded" style="background:var(--status-success-bg);color:var(--color-text-main);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-mortarboard text-success"></i>
                    <span class="fw-bold">طالب</span>
                    <span class="badge bg-success">{{ $student->student_code }}</span>
                </div>
                <small class="text-muted d-block">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</small>
                @if ($student->center || $student->project)
                <small class="text-muted d-block">
                    {{ $student->center?->name ?? '' }}
                    {{ $student->center && $student->project ? '|' : '' }}
                    {{ $student->project?->name ?? '' }}
                </small>
                @endif
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-success mt-2">
                    <i class="bi bi-eye me-1"></i> عرض الملف الدراسي
                </a>
            </div>
            @endif

            @if ($user->type === 'beneficiary')
            <div class="mb-3 p-3 rounded" style="background:var(--status-warning-bg);color:var(--color-text-main);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-person-heart text-warning"></i>
                    <span class="fw-bold">مستفيد</span>
                </div>
                <a href="{{ route('admin.beneficiary.dashboard') }}" class="btn btn-sm btn-outline-warning mt-2">
                    <i class="bi bi-speedometer2 me-1"></i> لوحة المستفيد
                </a>
            </div>
            @elseif (($user->type === 'employee' || $user->type === 'super-admin') && !$employee)
            <div class="mb-3 p-3 rounded" style="background:var(--status-info-bg);color:var(--color-text-main);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-lock text-primary"></i>
                    <span class="fw-bold">مدير النظام</span>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="bi bi-speedometer2 me-1"></i> لوحة التحكم
                </a>
            </div>
            @endif
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
