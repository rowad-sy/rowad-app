<?php

namespace App\Support;

use App\Models\Admin\Permission;
use Illuminate\Support\Facades\DB;

/*
 * استعلام حاملي صلاحية معيّنة — مباشرة (user_id) أو عبر مجموعة/دور (group_user).
 * يستخدم لاقتراح «الافتراضي» في القوائم المنسدلة للخطوات (مراسل/مصمم/ناشر/
 * مسؤول روادنا...) بدل الاعتماد على منح user_id المباشر فقط، وهو التوجه
 * الموصوف في docs/permission-v2-study.md (مرحلة 6).
 *
 * دلالات النطاق مطابقة لـ PermissionHelper::applyMembershipScope على وجه
 * التقريب الكافي للاقتراح: منح بلا نطاق = عام، وعضوية بلا نطاق = عام.
 */
class RoleHoldersLookup
{
    public static function userIds(string $modelKey, string $flag = 'view', ?int $projectId = null, ?int $centerId = null): array
    {
        $column = 'can_' . (in_array($flag, ['view', 'create', 'edit', 'delete'], true) ? $flag : 'view');

        $direct = Permission::query()
            ->whereJsonContains('model_names', $modelKey)
            ->where($column, 1)
            ->whereNotNull('user_id')
            ->when($projectId, fn ($q) => $q->where(fn ($s) => $s->whereNull('project_id')->orWhere('project_id', $projectId)))
            ->when($centerId, fn ($q) => $q->where(fn ($s) => $s->whereNull('center_id')->orWhere('center_id', $centerId)))
            ->pluck('user_id')
            ->all();

        $viaGroups = DB::table('group_user')
            ->join('permissions', 'permissions.group_id', '=', 'group_user.group_id')
            ->whereJsonContains('permissions.model_names', $modelKey)
            ->where('permissions.' . $column, 1)
            ->when($projectId, function ($q) use ($projectId) {
                $q->where(function ($s) use ($projectId) {
                    $s->whereNull('group_user.project_id')
                        ->orWhereNull('permissions.project_id')
                        ->orWhere('group_user.project_id', $projectId)
                        ->orWhere('permissions.project_id', $projectId);
                });
            })
            ->when($centerId, function ($q) use ($centerId) {
                $q->where(function ($s) use ($centerId) {
                    $s->whereNull('group_user.center_id')
                        ->orWhereNull('permissions.center_id')
                        ->orWhere('group_user.center_id', $centerId)
                        ->orWhere('permissions.center_id', $centerId);
                });
            })
            ->distinct()
            ->pluck('group_user.user_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return array_values(array_unique(array_merge(array_map('intval', $direct), $viaGroups)));
    }

    public static function first(string $modelKey, string $flag = 'view', ?int $projectId = null, ?int $centerId = null): ?int
    {
        $ids = self::userIds($modelKey, $flag, $projectId, $centerId);

        return $ids ? (int) collect($ids)->min() : null;
    }

    /*
     * قائمة المرشحين لقائمة منسدلة: حاملو الرول أولاً (بترتيب الأسماء) ثم بقية
     * الموظفين. تصلح كـ $users في الواجهات مع <option> افتراضي = first().
     */
    public static function preferredUsers(string $modelKey, string $flag = 'view', ?int $projectId = null, ?int $centerId = null)
    {
        $ids = self::userIds($modelKey, $flag, $projectId, $centerId);

        $holders = \App\Models\User::whereIn('id', $ids)->orderBy('name')->get();
        $others = \App\Models\User::where('type', 'employee')->whereNotIn('id', $ids)->orderBy('name')->get();

        return $holders->concat($others)->unique('id')->values();
    }

    /*
     * هل يحمل المستخدم الصلاحية (مباشرة أو عبر مجموعة) بنطاق يطابق المعطى؟
     */
    public static function holds(int $userId, string $modelKey, string $flag = 'view', ?int $projectId = null, ?int $centerId = null): bool
    {
        return in_array($userId, self::userIds($modelKey, $flag, $projectId, $centerId), true);
    }
}
