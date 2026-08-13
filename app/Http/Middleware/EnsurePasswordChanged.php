<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Middleware لفرض تغيير كلمة المرور
 * المستخدم الذي يجب عليه تغيير كلمة المرور يُعاد توجيهه إلى صفحة تغيير كلمة المرور
 * حتى يغيّرها، ولا يستطيع استخدام النظام قبل ذلك
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('admin.password.change');
        }

        return $next($request);
    }
}
