<?php

use App\Models\Admin\MovementPlan;
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

function reproEntry(array $overrides = []): array
{
    return array_merge([
        'movement_date' => now()->addDays(2)->toDateString(),
        'departure_time' => '10:00',
        'return_time' => '14:00',
        'from_location' => 'موقع الانطلاق',
        'to_location' => 'موقع الوصول',
        'purpose' => 'غرض رسمي',
        'notes' => 'ملاحظة',
    ], $overrides);
}

it('reproduces the full create journey: index, create, store, show, refer, index again', function () {
    $user = reproUser();
    reproFullPermission($user);
    $pm2 = reproUser();
    reproPm2Permission($pm2);

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
        ->and($plan->status)->toBe('review');

    $show = $this->get('/admin/movement-plans/' . $plan->id);
    $show->assertOk();
    // الواجهة تعرض لوحة «الإحالة» للمنشئ
    $show->assertSee('/admin/movement-plans/' . $plan->id . '/refer', false);

    $third = reproUser();

    $this->post("/admin/movement-plans/{$plan->id}/refer", [
        'step' => 'pm2',
        'to_user_id' => $third->id,
    ])->assertStatus(302);

    $plan->refresh();
    expect($plan->refer_to_pm2_id)->toBe($third->id)
        ->and($plan->activeReferrals()->where('step', 'pm2')->pluck('to_user_id')->all())->toBe([$third->id]);

    // الصفحة الرئيسية تعمل بعد الإحالة
    $this->get('/admin/movement-plans')->assertOk();
});
