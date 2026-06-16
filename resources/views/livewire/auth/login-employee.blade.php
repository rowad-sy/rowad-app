<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'تسجيل دخول الموظفين', 'description' => 'أدخل بريدك الإلكتروني وكلمة المرور الخاصة بك'])] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'type' => 'employee'], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) return;
        event(new Lockout(request()));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <form wire:submit="login">
        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                   placeholder="example@example.com" required autofocus autocomplete="email">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" wire:model="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="current-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
            <div class="form-check">
                <input type="checkbox" id="remember" wire:model="remember" class="form-check-input">
                <label for="remember" class="form-check-label small">تذكرني</label>
            </div>
        </div>
        <button type="submit" class="btn btn-auth" wire:loading.attr="disabled">
            <span wire:loading.remove>تسجيل الدخول</span>
            <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> جاري التحقق...</span>
        </button>
    </form>
    <div class="auth-divider"><span>أو</span></div>
    <div class="d-flex justify-content-between auth-footer" style="margin-top:0">
        <a href="{{ route('register.employee') }}">إنشاء حساب جديد</a>
        <a href="{{ route('auth.choose') }}">العودة</a>
    </div>
</div>
