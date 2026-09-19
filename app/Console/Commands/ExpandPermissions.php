<?php

namespace App\Console\Commands;

use App\Models\Admin\Permission;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/*
 * تفكيك سجلات الصلاحيات متعددة الموديلات إلى سجل مستقل لكل موديل.
 *
 * بعد الترحيل تُصبح كل صلاحية (تعيين × موديل) سجلاً واحداً يحتوي
 * model_names = [موديل واحد] مع نطاقه وأعلامه الخاصة، فيصبح فحص
 * whereJsonContains('model_names', $modelName) في PermissionHelper دقيقاً.
 *
 * الأمر Idempotent:
 *  - السجلات أحادية الموديل لا تُمس.
 *  - عند التفكيك لا يُنشأ سجل مكرر إذا وُجد سجل أحادي مطابق مسبقاً لنفس
 *    (التعيين × الموديل × النطاق) — تشغيله مرتين لا يُفسد البيانات.
 */
class ExpandPermissions extends Command
{
    protected $signature = 'permissions:expand';

    protected $description = 'تفكيك سجلات الصلاحيات متعددة الموديلات إلى سجل لكل موديل';

    public function handle(): int
    {
        $splitCount = 0;
        $skippedDuplicates = 0;

        Permission::query()
            ->orderBy('id')
            ->chunk(200, function ($permissions) use (&$splitCount, &$skippedDuplicates) {
                foreach ($permissions as $permission) {
                    $names = array_values($permission->model_names ?? []);

                    if (count($names) <= 1) {
                        continue;
                    }

                    foreach ($names as $model) {
                        if ($this->singleRecordExists($permission, $model)) {
                            $skippedDuplicates++;
                            continue;
                        }

                        Permission::create($this->recordData($permission, $model));
                    }

                    $permission->delete();
                    $splitCount++;
                }
            });

        $this->info("تم تفكيك {$splitCount} سجل، وتم تخطي {$skippedDuplicates} سجل مكرر موجود مسبقاً.");

        return self::SUCCESS;
    }

    private function singleRecordExists(Permission $source, string $model): bool
    {
        $query = Permission::query()
            ->where('id', '!=', $source->id)
            ->where('model_id', $source->model_id)
            ->where('center_id', $source->center_id)
            ->where('project_id', $source->project_id)
            ->where('cohort_id', $source->cohort_id)
            ->whereJsonContains('model_names', $model);

        $this->scopeByAssignee($query, $source);

        return $query->get()
            ->contains(fn (Permission $p) => count($p->model_names ?? []) === 1);
    }

    private function scopeByAssignee(Builder $query, Permission $source): void
    {
        if ($source->user_id !== null) {
            $query->where('user_id', $source->user_id);

            return;
        }

        $query->where('group_id', $source->group_id);
    }

    private function recordData(Permission $source, string $model): array
    {
        return [
            'user_id' => $source->user_id,
            'group_id' => $source->group_id,
            'model_names' => [$model],
            'model_id' => $source->model_id,
            'center_id' => $source->center_id,
            'project_id' => $source->project_id,
            'cohort_id' => $source->cohort_id,
            'can_view' => $source->can_view,
            'can_create' => $source->can_create,
            'can_edit' => $source->can_edit,
            'can_delete' => $source->can_delete,
        ];
    }
}