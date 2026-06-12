<?php

namespace Database\Seeders;

use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Group::create(['name' => 'مدير النظام', 'description' => 'مدير النظام - لديه جميع الصلاحيات']);
        $centersAdmin = Group::create(['name' => 'مسؤول المراكز', 'description' => 'يدير المراكز بشكل كامل']);
        $projectsAdmin = Group::create(['name' => 'مسؤول المشاريع', 'description' => 'يدير المشاريع بشكل كامل']);
        $supervisor = Group::create(['name' => 'مشرف', 'description' => 'مشرف - يمكنه عرض كل شيء']);
        $hrOfficer = Group::create(['name' => 'مسؤول الموارد البشرية', 'description' => 'يدير المستخدمين والصلاحيات']);

        $allModels = [
            'App\Models\Admin\Center',
            'App\Models\Admin\Project',
            'App\Models\User',
            'App\Models\Admin\Group',
            'App\Models\Admin\Permission',
        ];

        // مدير النظام - صلاحية واحدة تشمل جميع الموديلات
        Permission::create([
            'group_id' => $superAdmin->id,
            'model_names' => $allModels,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        // مسؤول المراكز
        Permission::create([
            'group_id' => $centersAdmin->id,
            'model_names' => ['App\Models\Admin\Center'],
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        // مسؤول المشاريع
        Permission::create([
            'group_id' => $projectsAdmin->id,
            'model_names' => ['App\Models\Admin\Project'],
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        // مشرف - صلاحية واحدة تشمل جميع الموديلات (عرض فقط)
        Permission::create([
            'group_id' => $supervisor->id,
            'model_names' => $allModels,
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);

        // مسؤول الموارد البشرية - صلاحية واحدة للمستخدمين والمجموعات والصلاحيات
        Permission::create([
            'group_id' => $hrOfficer->id,
            'model_names' => [
                'App\Models\User',
                'App\Models\Admin\Group',
                'App\Models\Admin\Permission',
            ],
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        $adminUser = User::where('email', 'admin@rowad.app')->first();
        if ($adminUser) {
            $adminUser->groups()->attach($superAdmin->id);
        }

        $user1 = User::factory()->create([
            'name' => 'أحمد محمد',
            'email' => 'ahmed@rowad.app',
            'password' => bcrypt('password'),
        ]);
        $user1->groups()->attach($centersAdmin->id);

        $user2 = User::factory()->create([
            'name' => 'سارة خالد',
            'email' => 'sara@rowad.app',
            'password' => bcrypt('password'),
        ]);
        $user2->groups()->attach($projectsAdmin->id);

        $user3 = User::factory()->create([
            'name' => 'محمود علي',
            'email' => 'mahmoud@rowad.app',
            'password' => bcrypt('password'),
        ]);
        $user3->groups()->attach($supervisor->id);

        $user4 = User::factory()->create([
            'name' => 'نور حسن',
            'email' => 'noor@rowad.app',
            'password' => bcrypt('password'),
        ]);
        $user4->groups()->attach($hrOfficer->id);
    }
}
