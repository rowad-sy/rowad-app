<?php

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Student\Student;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'تسجيل الدخول', 'description' => 'أدخل بريدك الإلكتروني وكلمة المرور للدخول إلى النظام'])] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // Redirect based on user type
        $user = auth()->user();

        if ($user->type === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $redirectRoute = route('admin.students.show', $student, absolute: false);
            } else {
                session()->flash('error', 'حسابك غير مرتبط بأي طالب، يرجى التواصل مع الإدارة');
                $redirectRoute = route('admin.profile', absolute: false);
            }
        } elseif ($user->type === 'beneficiary') {
            $redirectRoute = route('admin.beneficiary.dashboard', absolute: false);
        } elseif ($user->type === 'employee' || $user->type === 'super-admin') {
            $employee = Employee::where('user_id', $user->id)->first();
            $redirectRoute = $employee
                ? route('admin.hr.employees.show', $employee, absolute: false)
                : route('admin.dashboard', absolute: false);
        } else {
            // Unknown type (null, empty, etc.)
            $redirectRoute = route('admin.home', absolute: false);
        }

        $this->redirectIntended(default: $redirectRoute, navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <form wire:submit="login">
        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                   placeholder="example@example.com" required autofocus autocomplete="email">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label">كلمة المرور</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color:#1e293b;">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>
            <input type="password" id="password" wire:model="password" class="form-control @error('password') is-invalid @enderror"
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
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                جاري التحقق...
            </span>
        </button>
    </form>

</div>
