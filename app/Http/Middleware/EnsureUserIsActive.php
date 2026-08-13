<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Middleware للتحقق من أن المستخدم نشط
 * المستخدم غير النشط لا يستطيع الوصول إلى أي صلاحية
 * باستثناء ملفه الشخصي وتغيير كلمة المرور والصفحات المشابهة
 */
class EnsureUserIsActive
{
    protected array $except = [
        'admin.profile',
        'admin.profile.update',
        'admin.profile.password',
        'admin.password.change',
        'admin.password.update',
        'logout',
        'verification.notice',
        'verification.verify',
        'activation.resend',
        'password.confirm',
        'livewire.update',
        'livewire.message',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        $route = $request->route();

        if ($route && in_array($route->getName(), $this->except, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'حسابك غير نشط، لا يمكنك الوصول إلى النظام حتى يتم تفعيل حسابك'], 403);
        }

        session()->flash('error', 'حسابك غير نشط. يمكنك فقط الوصول إلى ملفك الشخصي وتغيير كلمة المرور.');

        return redirect()->route('admin.profile');
    }
}
