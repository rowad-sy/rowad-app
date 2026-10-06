<?php

namespace App\Console\Commands;

use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use Illuminate\Console\Command;

/*
 * فحص سجلات الصلاحيات التي ما زالت تحمل نطاقاً مدمجاً (center_id/project_id/cohort_id).
 *
 * وفق الترتيبة v2 النطاق يُقرَّر وقت إسناد الدور (group_user) لا داخل التعيين.
 * هذه السجلات القديمة محترمة في المحرك (توافق خلفي) لكنها مرشحة للنقل.
 * الأمر **قراءة فقط** — يعرض تقريراً ويصفر عند وجود سجلات (للاستخدام في CI لاحقاً بـ --fail).
 */
class AuditPermissionScopes extends Command
{
    protected $signature = 'permissions:audit-scopes {--fail : رجوع كود خطأ إذا وُجدت سجلات مقيّدة}';

    protected $description = 'تقرير قراءة-فقط عن صلاحيات قديمة تحمل نطاقاً مدمجاً، مترشحة للنقل إلى إسناد الدور';

    public function handle(): int
    {
        $records = Permission::query()
            ->where(fn($q) => $q->whereNotNull('center_id')
                ->orWhereNotNull('project_id')
                ->orWhereNotNull('cohort_id'))
            ->orderBy('group_id')
            ->orderBy('user_id')
            ->get();

        if ($records->isEmpty()) {
            $this->info('✅ لا توجد صلاحيات تحمل نطاقاً مدمجاً — الترتيبة v2 نظيفة بالكامل.');
            return self::SUCCESS;
        }

        $roleIds = Group::roles()->pluck('id');

        $rows = $records->map(function (Permission $p) use ($roleIds) {
            $entity = $p->user_id
                ? 'مستخدم #' . $p->user_id . ' (' . ($p->user?->name ?? '؟') . ')'
                : ($roleIds->contains($p->group_id) ? 'دور #' : 'مجموعة #') . $p->group_id . ' (' . ($p->group?->name ?? '؟') . ')';

            $scope = implode(' · ', array_filter([
                $p->center_id ? 'مركز ' . $p->center_id : null,
                $p->project_id ? 'مشروع ' . $p->project_id : null,
                $p->cohort_id ? 'فوج ' . $p->cohort_id : null,
            ]));

            $flags = collect(['can_view' => 'عرض', 'can_create' => 'إضافة', 'can_edit' => 'تعديل', 'can_delete' => 'حذف'])
                ->filter(fn($label, $col) => (bool) $p->$col)->values()->implode('+');

            $suggestion = $p->user_id
                ? 'انقل التقييد إلى إسناد دور بالنطاق المناسب بدل المنح المباشر المقيّد'
                : 'اضبط النطاق في عضوية group_user (شاشة الأدوار) ثم فرّغ نطاق السجل';

            return [
                $p->id,
                $entity,
                collect($p->model_names ?? [])->map(fn($m) => class_basename($m))->implode(','),
                $flags ?: '—',
                $scope,
                $suggestion,
            ];
        })->all();

        $this->table(['id', 'الكيان', 'الموديلات', 'الأعلام', 'النطاق المدمج', 'التوصية'], $rows)->render();

        $this->warn(sprintf('مجموع السجلات المقيّدة بنطاق مدمج: %d — المحرك يحترمها حالياً كسقف، والخطط في docs/permission-v2-study.md §6 (المرحلة 3/8).', $records->count()));

        if ($this->option('fail')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
