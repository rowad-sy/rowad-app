<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\UserActivationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ActivationResendController extends Controller
{
    /**
     * إعادة إرسال بريد التفعيل للمستخدم الذي لم يفعّل حسابه بعد
     * مع تحديد عدد الرسائل المسموح إرسالها والفاصل الزمني بينها
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        // الحساب مفعّل بالفعل أو موقوف من قبل الإدارة - لا حاجة لإعادة الإرسال
        if ($user->is_active || $user->hasVerifiedEmail()) {
            if ($user->is_active) {
                session()->flash('error', 'حسابك مفعّل بالفعل، لا حاجة لإعادة إرسال رسالة التفعيل.');
            } else {
                session()->flash('error', 'تم إيقاف حسابك من قبل الإدارة. يرجى التواصل مع الإدارة لإعادة تفعيله.');
            }

            return redirect()->route('admin.profile');
        }

        $maxResends = config('activation.max_resends');
        $intervalMinutes = config('activation.resend_interval_minutes');

        // الحد الأقصى لعدد الرسائل
        if ($user->activation_email_count >= $maxResends) {
            session()->flash('error', 'لقد وصلت إلى الحد الأقصى لإعادة إرسال رسالة التفعيل. يرجى التواصل مع الإدارة.');

            return redirect()->route('admin.profile');
        }

        // الفاصل الزمني بين كل إرسال وآخر
        $lastSentAt = $user->activation_email_sent_at;
        if ($lastSentAt && $lastSentAt->diffInMinutes(now()) < $intervalMinutes) {
            $remainingMinutes = max(1, $intervalMinutes - (int) $lastSentAt->diffInMinutes(now()));
            session()->flash('info', "يرجى الانتظار قبل إعادة الإرسال. يمكنك إعادة الإرسال بعد {$remainingMinutes} دقيقة.");

            return redirect()->route('admin.profile');
        }

        try {
            $activationUrl = URL::temporarySignedRoute(
                'activation.verify',
                now()->addDays(7),
                ['user' => $user->id]
            );

            $user->notify(new UserActivationMail($activationUrl));

            $user->update([
                'activation_email_sent_at' => now(),
                'activation_email_count' => $user->activation_email_count + 1,
            ]);

            session()->flash('success', 'تم إعادة إرسال بريد التفعيل بنجاح. يرجى التحقق من صندوق الوارد أو مجلد الرسائل غير المرغوب بها (Spam).');
        } catch (\Throwable $e) {
            Log::error('Failed to resend activation email: ' . $e->getMessage());
            session()->flash('error', 'حدث خطأ أثناء إرسال البريد. يرجى المحاولة لاحقاً.');
        }

        return redirect()->route('admin.profile');
    }
}
