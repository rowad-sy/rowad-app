<?php

use App\Models\Admin\MovementPlan;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const REPRO_MOVEMENT_MODEL = 'App\Models\Admin\MovementPlan';

function reproUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function reproFullPermission(User $user): Permission
{
    return Permission::create([
        'user_id' => $user->id,
        'model_names' => [REPRO_MOVEMENT_MODEL],
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
        'can_delete' => true,
    ]);
}

function reproPm2Permission(User $user): Permission
{
    return Permission::create([
        'user_id' => $user->id,
        'model_names' => ['page:admin.project-manager.dashboard'],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ]);
}

function reproProjectsManagerRole(User $user): void
{
    $role = Group::create([
        'name' => 'إدارة المشاريع للاختبار',
        'kind' => Group::KIND_ROLE,
        'role_key' => Group::ROLE_PROJECTS_MANAGER,
    ]);
    Permission::create([
        'group_id' => $role->id,
        'model_names' => [REPRO_MOVEMENT_MODEL],
        'can_view' => true,
        'can_edit' => true,
    ]);
    $role->users()->attach($user->id);
}

function reproEntry(array $overrides = []): array
{
    return array_merge([
        'movement_date' => now()->addDays(2)->toDateString(),
        'return_date' => now()->addDays(2)->toDateString(),
        'departure_time' => '10:00',
        'return_time' => '14:00',
        'from_location' => 'موقع الانطلاق',
        'to_location' => 'موقع الوصول',
        'purpose' => 'غرض رسمي',
        'notes' => 'ملاحظة',
    ], $overrides);
}

it('reproduces the create journey with review routed to the projects-manager role', function () {
    $user = reproUser();
    reproFullPermission($user);
    $pm2 = reproUser();
    reproProjectsManagerRole($pm2);

    $this->actingAs($user);

    $this->get('/admin/movement-plans')->assertOk();

    $this->get('/admin/movement-plans/create')->assertOk();

    $this->post('/admin/movement-plans', [
        'plan_month' => now()->format('Y-m'),
        'notes' => 'خطة تجريبية',
        'entries' => [reproEntry()],
    ])->assertRedirect();

    $plan = MovementPlan::latest('id')->firstOrFail();

    expect($plan->created_by)->toBe($user->id)
        ->and($plan->status)->toBe('review')
        ->and($plan->review_role_key)->toBe(Group::ROLE_PROJECTS_MANAGER);

    $show = $this->get('/admin/movement-plans/' . $plan->id);
    $show->assertOk();
    $show->assertDontSee('/admin/movement-plans/' . $plan->id . '/refer', false);

    $this->post("/admin/movement-plans/{$plan->id}/refer", [
        'step' => 'pm2',
        'to_user_id' => $user->id,
    ])->assertStatus(403);

    $plan->refresh();
    expect($plan->refer_to_pm2_id)->toBeNull()
        ->and($plan->review_role_key)->toBe(Group::ROLE_PROJECTS_MANAGER);

    // الصفحة الرئيسية تعمل بعد الإحالة
    $this->get('/admin/movement-plans')->assertOk();
});
