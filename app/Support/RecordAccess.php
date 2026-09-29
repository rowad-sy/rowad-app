<?php

namespace App\Support;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/*
 * تحقق الإذن ونطاق السجل معًا لسجل بعينه (مركزه/مشروعه/معرّفه)، دون تعديل PermissionHelper أو CheckPermission.
 *
 * السياسة (مطابقة لمعنى الصلاحيات القائمة، مع تشدّد صريح على القيم الفارغة):
 *  - super-admin: مسموح.
 *  - يُفحص كل سجل صلاحية (مباشر أو من مجموعة) على حدة: يجب أن يحمل علم العملية المطلوبة (can_view/can_edit/…) وأن يطابق نطاقه السجل:
 *      center_id / project_id غير null يتطلب تطابقًا، وسجل بلا مركز/مشروع لا يطابق نطاقًا محددًا (لا «null = مسموح»)؛
 *      model_id غير null يتطلب أن يكون معرّف السجل هو نفسه (ولا يصلح للإنشاء)؛
 *      cohort_id غير null: لا معنى له لهذه الموديلات، فلا يمنح وصولًا.
 *  - لذلك عرض في مركز A وتعديل في مركز B لا يولّدان تعديلًا في A.
 *  - assigned_to / reported_by لا يمنحان تجاوز النطاق (لا سياسة قائمة تنص على ذلك).
 */
class RecordAccess
{
    public static function allows(?User $user, string $model, string $action, ?int $centerId, ?int $projectId, ?int $recordId = null): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->type === 'super-admin') {
            return true;
        }

        $column = 'can_'.$action;
        foreach (self::permissions($user, $model) as $p) {
            if (! $p->$column) {
                continue;
            }
            if ($p->cohort_id !== null) {
                continue;
            }
            if ($p->model_id !== null && ($recordId === null || (int) $p->model_id !== $recordId)) {
                continue;
            }
            if ($p->center_id !== null && (int) $p->center_id !== $centerId) {
                continue;
            }
            if ($p->project_id !== null && (int) $p->project_id !== $projectId) {
                continue;
            }

            return true;
        }

        return false;
    }

    /** يرفض بـ403 إن لم يسمح الإذن+النطاق بالعملية على هذا السجل. */
    public static function authorize(string $model, string $action, ?int $centerId, ?int $projectId, ?int $recordId = null): void
    {
        abort_unless(self::allows(auth()->user(), $model, $action, self::i($centerId), self::i($projectId), $recordId), 403, 'ليس لديك صلاحية على هذا السجل');
    }

    /**
     * وجهة الكتابة (إنشاء أو نقل): يجب أن تسمح العملية بالمركز/المشروع المُرسَلين، ولا يُتجاوز النطاق بإرسال null.
     * لغير المقيَّدين (نطاق مفتوح) لا يتغير السلوك القائم.
     */
    public static function authorizeTarget(string $model, string $action, mixed $centerId, mixed $projectId, ?int $recordId = null): void
    {
        if (! self::allows(auth()->user(), $model, $action, self::i($centerId), self::i($projectId), $recordId)) {
            throw ValidationException::withMessages(['center_id' => 'المركز أو المشروع المحدد خارج نطاق صلاحياتك.']);
        }
    }

    /** يقصر استعلام قائمة على السجلات التي تسمح بها العملية (نفس منطق allows على مستوى الاستعلام). */
    public static function scopeQuery($query, string $model, string $action = 'view')
    {
        $user = auth()->user();
        if ($user && $user->type === 'super-admin') {
            return $query;
        }
        $column = 'can_'.$action;
        $rows = $user ? self::permissions($user, $model)->filter(fn ($p) => $p->$column && $p->cohort_id === null) : collect();

        return $query->where(function ($q) use ($rows) {
            $q->whereRaw('1 = 0');
            foreach ($rows as $p) {
                $q->orWhere(function ($g) use ($p) {
                    $g->whereRaw('1 = 1');
                    if ($p->model_id !== null) {
                        $g->where($g->getModel()->getQualifiedKeyName(), $p->model_id);
                    }
                    if ($p->center_id !== null) {
                        $g->where('center_id', $p->center_id);
                    }
                    if ($p->project_id !== null) {
                        $g->where('project_id', $p->project_id);
                    }
                });
            }
        });
    }

    private static function i(mixed $v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    private static function permissions(User $user, string $model)
    {
        $bag = request()->attributes;
        $cache = $bag->get('record_access', []);
        $key = $user->id.'|'.$model;
        if (! isset($cache[$key])) {
            $cache[$key] = PermissionHelper::getUserPermissions($user, $model);
            $bag->set('record_access', $cache);
        }

        return $cache[$key];
    }
}
