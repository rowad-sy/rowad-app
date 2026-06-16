<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'تأكيد البريد الإلكتروني', 'description' => 'يرجى تأكيد بريدك الإلكتروني قبل المتابعة'])] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'تم إرسال رابط التحقق إلى بريدك الإلكتروني.');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="text-center">
    <div class="mb-4">
        <i class="bi bi-envelope-check" style="font-size:3rem;color:#1e293b;"></i>
    </div>
    <p class="text-muted mb-4">يرجى التحقق من بريدك الإلكتروني عبر النقر على الرابط الذي أرسلناه لك.</p>

    @if (session('status') == 'verification-link-sent' || session('status') === 'تم إرسال رابط التحقق إلى بريدك الإلكتروني.')
        <div class="alert alert-success alert-session mb-4" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.
        </div>
    @endif

    <button wire:click="sendVerification" class="btn btn-auth mb-3" wire:loading.attr="disabled">
        <span wire:loading.remove>إعادة إرسال رابط التحقق</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
            جاري الإرسال...
        </span>
    </button>

    <div class="auth-footer">
        <button wire:click="logout" class="btn btn-link p-0 text-decoration-none small">
            تسجيل الخروج
        </button>
    </div>
</div>
