<?php

namespace App\Helpers;

use App\Models\Admin\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/*
 * نظام الصلاحيات المتقدم - PermissionHelper
 * =========================================
 *
 * هذا الكلاس هو المسؤول عن التحقق من صلاحيات المستخدمين في النظام.
 * يعمل النظام على مبدأ جمع الصلاحيات:
 *   1. صلاحيات المستخدم المباشرة (user permissions)
 *   2. صلاحيات المجموعات التي ينتمي إليها المستخدم (group permissions)
 *
 * يتم جمع كل هذه الصلاحيات وإرجاع الصلاحية الفعالة (الأعلى).
 *
 * كيفية التوسيع:
 * ------------
 * إذا أردت إضافة نطاق جديد (مثل department_id):
 *   1. أضف العمود إلى جدول permissions في ميقريشن جديد
 *   2. أضف checked للمتغير الجديد في الدوال المعنية هنا
 *
 * إذا أردت إضافة نوع صلاحية جديد (مثل can_export):
 *   1. أضف العمود إلى جدول permissions
 *   2. أضف checked للصلاحية الجديدة هنا
 */
class PermissionHelper
{
    /*
     * التحقق مما إذا كان المستخدم لديه صلاحية معينة
     *
     * @param User $user المستخدم
     * @param string $modelName اسم الموديل (مثال: App\Models\Center)
     * @param string $action نوع الصلاحية (view, create, edit, delete)
     * @param int|null $modelId معرّف العنصر (اختياري)
     * @param int|null $centerId معرّف المركز (اختياري - null يعني جميع المراكز)
     * @param int|null $projectId معرّف المشروع (اختياري - null يعني جميع المشاريع)
     * @param int|null $cohortId معرّف الفوج (اختياري - null يعني جميع الأفواج)
     * @return bool
     */
    public static function can(User $user, string $modelName, string $action, ?int $modelId = null, ?int $centerId = null, ?int $projectId = null, ?int $cohortId = null): bool
    {
        // Super-admin has all permissions
        if ($user->type === 'super-admin') {
            return true;
        }

        $column = 'can_' . $action;

        $permissions = self::getUserPermissions($user, $modelName);

        foreach ($permissions as $permission) {
            // التحقق من صلاحية العنصر المحدد (إذا كان modelId موجوداً)
            if ($modelId !== null && $permission->model_id !== null && $permission->model_id !== $modelId) {
                continue;
            }

            // التحقق من نطاق المركز
            if ($centerId !== null && $permission->center_id !== null && $permission->center_id !== $centerId) {
                continue;
            }

            // التحقق من نطاق المشروع
            if ($projectId !== null && $permission->project_id !== null && $permission->project_id !== $projectId) {
                continue;
            }

            // التحقق من نطاق الفوج
            if ($cohortId !== null && $permission->cohort_id !== null && $permission->cohort_id !== $cohortId) {
                continue;
            }

            if ($permission->$column) {
                return true;
            }
        }

        return false;
    }

    /*
     * الحصول على نطاق المستخدم الفعّال من صلاحياته لموديل معين
     * مثال:
     *   إذا كان للمستخدم صلاحية على Student مع center_id=5
     *   → يرجع ['center_ids' => [5], 'project_ids' => [], 'cohort_ids' => [], 'sees_all' => false]
     *
     * @param string $flag أي علم يُبنى عليه النطاق (افتراضياً can_view — للعرض)
     * @return array{center_ids: array, project_ids: array, cohort_ids: array, sees_all: bool}
     */
    public static function getEffectiveScope(User $user, string $modelName, string $flag = 'can_view'): array
    {
        if ($user->type === 'super-admin') {
            return [
                'center_ids' => [],
                'project_ids' => [],
                'cohort_ids' => [],
                'sees_all' => true,
            ];
        }

        $permissions = self::getUserPermissions($user, $modelName);

        $centerIds = [];
        $projectIds = [];
        $cohortIds = [];
        $seesAll = false;

        foreach ($permissions as $permission) {
            if (!$permission->$flag) {
                continue;
            }

            // يرى كل شيء فقط إذا كانت جميع النطاقات الثلاثة مفتوحة على الكل
            if ($permission->center_id === null && $permission->project_id === null && $permission->cohort_id === null) {
                $seesAll = true;
            }

            if ($permission->center_id !== null) {
                $centerIds[] = $permission->center_id;
            }
            if ($permission->project_id !== null) {
                $projectIds[] = $permission->project_id;
            }
            if ($permission->cohort_id !== null) {
                $cohortIds[] = $permission->cohort_id;
            }
        }

        $centerIds = array_unique($centerIds);
        $projectIds = array_unique($projectIds);
        $cohortIds = array_unique($cohortIds);

        return [
            'center_ids' => $centerIds,
            'project_ids' => $projectIds,
            'cohort_ids' => $cohortIds,
            'sees_all' => $seesAll,
        ];
    }

    /*
     * التحقق من صلاحية على **سجل محدد** (إغلاق ثغرة IDOR — W2).
     *
     * لا تكفي can() وحدها: هي تسأل "هل توجد أي صلاحية للفعل على الموديل؟"
     * بينما هنا نتأكد أن السجل نفسه يقع داخل نطاق صلاحيات الفعل المطلوبة.
     *
     * مثال: دور "مدير مشروع" بنطاق project_id=3 ⇒ يستطيع تعديل طلاب مشروع 3 فقط.
     */
    public static function canAccessRecord(User $user, string $modelName, string $action, Model $record): bool
    {
        if ($user->type === 'super-admin') {
            return true;
        }

        if (!self::can($user, $modelName, $action)) {
            return false;
        }

        $scope = self::getEffectiveScope($user, $modelName, 'can_' . $action);

        if ($scope['sees_all']) {
            return true;
        }

        foreach (['center_id' => 'center_ids', 'project_id' => 'project_ids', 'cohort_id' => 'cohort_ids'] as $column => $key) {
            $value = $record->getAttribute($column);

            // السجل غير محصور بهذا المحور، أو الصلاحية غير مقيّدة به ⇒ لا رفض من هذا المحور
            if ($value === null || empty($scope[$key])) {
                continue;
            }

            $allowed = array_map('intval', $scope[$key]);

            if (in_array((int) $value, $allowed, true)) {
                continue;
            }

            // حالة خاصة: الطلاب المرتبطون بمشاريع عبر جدول project_student فقط
            if ($column === 'project_id'
                && method_exists($record, 'projects')
                && $record->projects()->whereIn('projects.id', $scope['project_ids'])->exists()) {
                continue;
            }

            return false;
        }

        return true;
    }

    /*
     * التحقق من صلاحية صفحة معينة
     * يتم تخزين صلاحيات الصفحات في model_names كـ "page:{route_name}"
     */
    public static function canViewPage(User $user, string $routeName): bool
    {
        return self::can($user, 'page:' . $routeName, 'view');
    }

    /*
     * الحصول على جميع صلاحيات المستخدم (المباشرة + من المجموعات)
     *
     * صلاحيات المجموعات تمر بمرحلة "حلّ النطاق" (نظام الأدوار §17):
     * المجموعة تعرّف "ماذا" (الموديلات والأعلام) والعضوية (group_user) تعرّف "أين"
     * (مركز/مشروع/فوج). نطاق العضوية يسود عند التعارض، ويسقط لنطاق سجل المجموعة
     * عند غيابها — وهو سلوك متوافق خلفياً مع السجلات القديمة.
     */
    private static function getUserPermissions(User $user, string $modelName): Collection
    {
        // صلاحيات المستخدم المباشرة
        $directPermissions = Permission::where('user_id', $user->id)
            ->whereJsonContains('model_names', $modelName)
            ->get();

        // صلاحيات المجموعات التي ينتمي إليها المستخدم (مع نطاقات العضوية من الـ pivot)
        $memberships = $user->groups()->get();
        $groupIds = $memberships->pluck('id');

        $groupPermissions = $groupIds->isEmpty()
            ? new Collection()
            : Permission::whereIn('group_id', $groupIds)
                ->whereJsonContains('model_names', $modelName)
                ->get()
                ->map(function (Permission $permission) use ($memberships) {
                    $membership = $memberships->firstWhere('id', $permission->group_id);
                    return $membership ? self::applyMembershipScope($permission, $membership) : $permission;
                });

        return $directPermissions->concat($groupPermissions);
    }

    /*
     * دمج نطاق العضوية مع نطاق سجل صلاحية المجموعة (نطاق العضوية أعلى أسبقية).
     * يعيد نسخة محلول بها النطاق — النسخة غير قابلة للحفظ ولا تُستخدم إلا للقراءة.
     */
    private static function applyMembershipScope(Permission $permission, Model $membership): Permission
    {
        $resolved = clone $permission;

        foreach (['center_id', 'project_id', 'cohort_id'] as $column) {
            $pivotValue = $membership->pivot->{$column} ?? null;
            if ($pivotValue !== null) {
                $resolved->setAttribute($column, $pivotValue);
            }
        }

        return $resolved;
    }
}
