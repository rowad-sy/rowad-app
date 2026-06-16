<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'استعادة كلمة المرور', 'description' => 'أدخل بريدك الإلكتروني لاستلام رابط إعادة تعيين كلمة المرور'])] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', 'تم إرسال رابط إعادة التعيين إلى بريدك الإلكتروني إن كان الحساب موجوداً.');
    }
}; ?>

<div>
    <form wire:submit="sendPasswordResetLink">
        <div class="mb-4">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                   placeholder="example@example.com" required autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-auth" wire:loading.attr="disabled">
            <span wire:loading.remove>إرسال رابط إعادة التعيين</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                جاري الإرسال...
            </span>
        </button>
    </form>

    <div class="auth-divider"><span>أو</span></div>

    <div class="auth-footer">
        تذكرت كلمة المرور؟
        <a href="{{ route('login') }}">تسجيل الدخول</a>
    </div>
</div>
