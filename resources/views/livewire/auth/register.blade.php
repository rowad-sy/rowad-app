<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'إنشاء حساب جديد', 'description' => 'أدخل بياناتك لإنشاء حساب في النظام'])] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <div class="mb-3">
            <label for="name" class="form-label">الاسم الكامل</label>
            <input type="text" id="name" wire:model="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="الاسم الأول واللقب" required autofocus autocomplete="name">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                   placeholder="example@example.com" required autocomplete="email">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">كلمة المرور</label>
            <input type="password" id="password" wire:model="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="new-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
            <input type="password" id="password_confirmation" wire:model="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="new-password">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-auth" wire:loading.attr="disabled">
            <span wire:loading.remove>إنشاء الحساب</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                جاري الإنشاء...
            </span>
        </button>
    </form>

    <div class="auth-divider"><span>أو</span></div>

    <div class="auth-footer">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}">تسجيل الدخول</a>
    </div>
</div>
