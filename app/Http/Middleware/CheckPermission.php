<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Middleware للتحقق من الصلاحيات
 * يستخدم هذا الميدل وير لحماية Routes ومكونات Livewire
 *
 * مثال على الاستخدام في routes:
 * Route::get('/centers', ...)->middleware('permission:App\Models\Center,view');
 *
 * معاملات الميدل وير:
 * - model_name: اسم الموديل (مطلوب)
 * - action: نوع الصلاحية view/create/edit/delete (مطلوب)
 * - model_id: معرّف العنصر (اختياري)
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modelName, string $action): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $employee = \App\Models\Admin\Hr\Employee::where('user_id', $user->id)->first();
        $centerId = $employee?->center_id;
        $projectId = $employee?->project_id;
        $cohortId = $employee?->cohort_id;

        if (!PermissionHelper::can($user, $modelName, $action, null, $centerId, $projectId, $cohortId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'ليس لديك صلاحية للوصول إلى هذا المورد'], 403);
            }

            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
