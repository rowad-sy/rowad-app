<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    /**
     * تفعيل حساب المستخدم من رابط التفعيل المرسل عبر البريد الإلكتروني
     * عند الضغط على الرابط يتم تفعيل الحساب وتأكيد البريد الإلكتروني
     */
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if ($user->id !== auth()->id()) {
            abort(403, 'لا يمكنك تفعيل حساب مستخدم آخر');
        }

        $user->update(['is_active' => true]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        session()->flash('success', 'تم تفعيل حسابك بنجاح. يمكنك الآن استخدام النظام.');

        if ($user->must_change_password) {
            return redirect()->route('admin.password.change');
        }

        return redirect()->route('admin.profile');
    }
}
