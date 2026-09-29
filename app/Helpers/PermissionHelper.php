<?php

namespace App\Helpers;

use App\Models\Admin\Permission;
use App\Models\User;
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
     * @return array{center_ids: array, project_ids: array, cohort_ids: array, sees_all: bool}
     */
    public static function getEffectiveScope(User $user, string $modelName): array
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
            if (!$permission->can_view) {
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
     * الحصول على جميع صلاحيات المستخدم (المباشرة + من المجموعات)
     */
    public static function getUserPermissions(User $user, string $modelName): Collection
    {
        // صلاحيات المستخدم المباشرة
        $directPermissions = Permission::where('user_id', $user->id)
            ->whereJsonContains('model_names', $modelName)
            ->get();

        // صلاحيات المجموعات التي ينتمي إليها المستخدم
        $groupIds = $user->groups()->pluck('groups.id');
        $groupPermissions = Permission::whereIn('group_id', $groupIds)
            ->whereJsonContains('model_names', $modelName)
            ->get();

        return $directPermissions->concat($groupPermissions);
    }

    /*
     * التحقق من صلاحية صفحة معينة
     * يتم تخزين صلاحيات الصفحات في model_names كـ "page:{route_name}"
     */
    public static function canViewPage(User $user, string $routeName): bool
    {
        return self::can($user, 'page:' . $routeName, 'view');
    }
}
