<?php

namespace Database\Seeders;

/*
 * يمنح مستخدم "مدير المشاريع" التجريبي (demo.pm2@rowad.app) صلاحية
 * لوحة مدير المشاريع page:admin.projects-manager.dashboard + صلاحيات
 * عرض للموديلات التي تعرضها اللوحة (Project/ProjectTask/Student/Employee).
 * Idempotent: آمن إعادة تشغيله في أي وقت.
 *
 *   php artisan db:seed --class=ProjectsManagerDashboardPermissionSeeder
 */

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectsManagerDashboardPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $pm2 = User::where('email', 'demo.pm2@rowad.app')->first();

        if (! $pm2) {
            fwrite(STDOUT, "demo.pm2@rowad.app not found — nothing to grant.\n");
            return;
        }

        $scope = ['center_id' => null, 'project_id' => null, 'cohort_id' => null, 'can_view' => true];

        $this->ensurePage($pm2, 'page:admin.projects-manager.dashboard');

        foreach ([
            'App\\Models\\Admin\\Project',
            'App\\Models\\Admin\\ProjectTask',
            'App\\Models\\Admin\\Student\\Student',
            'App\\Models\\Admin\\Hr\\Employee',
        ] as $model) {
            $this->ensureModel($pm2, $model, $scope);
        }

        // مدير المشاريع يراجع كافة المشاريع/المراكز: فتح نطاق الصفوف الموجودة
        // (خطط الحركة/الإعلامية/الوثائق/طلبات الشراء/المقررات) ليشمل كل المراكز.
        $this->widenScope($pm2, [
            'App\\Models\\Admin\\MovementPlan',
            'App\\Models\\Admin\\MediaPlan',
            'App\\Models\\Admin\\ProjectDocs\\AnnexDocument',
            'App\\Models\\Admin\\ProjectDocs\\AnnexTemplate',
            'App\\Models\\Admin\\Logistics\\PurchaseRequest',
        ]);

        fwrite(STDOUT, "ProjectsManager dashboard permission granted to demo.pm2 (id={$pm2->id}).\n");
    }

    private function ensurePage(User $user, string $page): void
    {
        $row = Permission::where('user_id', $user->id)
            ->whereJsonContains('model_names', $page)
            ->first();

        if ($row) {
            if (! $row->can_view) {
                $row->update(['can_view' => true]);
                fwrite(STDOUT, "Enabled existing page permission {$page}.\n");
            }
            return;
        }

        Permission::create([
            'user_id' => $user->id,
            'model_names' => [$page],
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
    }

    private function ensureModel(User $user, string $model, array $scope): void
    {
        $row = Permission::where('user_id', $user->id)
            ->whereJsonContains('model_names', $model)
            ->first();

        if ($row) {
            if (! $row->can_view) {
                $row->update(['can_view' => true]);
                fwrite(STDOUT, "Enabled existing view for {$model}.\n");
            }
            return;
        }

        Permission::create([
            'user_id' => $user->id,
            'model_names' => [$model],
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
            'center_id' => $scope['center_id'],
            'project_id' => $scope['project_id'],
            'cohort_id' => $scope['cohort_id'],
        ]);
    }

    private function widenScope(User $user, array $models): void
    {
        $updated = 0;

        Permission::where('user_id', $user->id)->get()->each(function (Permission $row) use ($models, &$updated) {
            if (is_array($row->model_names)) {
                foreach ($models as $model) {
                    if (in_array($model, $row->model_names, true)) {
                        if ($row->center_id !== null || $row->project_id !== null || $row->cohort_id !== null) {
                            $row->update(['center_id' => null, 'project_id' => null, 'cohort_id' => null]);
                            $updated++;
                        }
                        break;
                    }
                }
            }
        });

        if ($updated > 0) {
            fwrite(STDOUT, "Widened {$updated} permission row(s) to global scope.\n");
        }
    }
}