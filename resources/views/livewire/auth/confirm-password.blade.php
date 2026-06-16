<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'تأكيد كلمة المرور', 'description' => 'هذه منطقة آمنة، يرجى تأكيد كلمة المرور قبل المتابعة'])] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="confirmPassword">
        <div class="mb-4">
            <label for="password" class="form-label">كلمة المرور</label>
            <input type="password" id="password" wire:model="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="current-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-auth" wire:loading.attr="disabled">
            <span wire:loading.remove>تأكيد</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                جاري التحقق...
            </span>
        </button>
    </form>
</div>
