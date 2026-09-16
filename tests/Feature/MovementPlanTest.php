<?php

use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Permission;
use App\Models\User;

const MOVEMENT_MODEL = 'App\Models\Admin\MovementPlan';

function movementUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function movementPermission(User $user, array $flags = []): Permission
{
    return Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => [MOVEMENT_MODEL],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ], $flags));
}

function projectsManagerPermission(User $user): Permission
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

function movementPlanFields(array $overrides = []): array
{
    return array_merge([
        'movement_date' => now()->addDays(2)->toDateString(),
        'departure_time' => '10:00',
        'return_time' => '14:00',
        'from_location' => 'مكتب الرواد - عفرين',
        'to_location' => 'المخيمات الشرقية',
        'purpose' => 'جولة ميدانية لتوزيع المستلزمات',
        'notes' => 'ملاحظة اختبار',
    ], $overrides);
}

test('guests are redirected to login on movement plans index', function () {
    $this->get('/admin/movement-plans')->assertRedirect('/login');
});

test('users without view permission get 403', function () {
    $user = movementUser();

    $this->actingAs($user)->get('/admin/movement-plans')->assertStatus(403);
});

test('authorised user can view the movement plans index', function () {
    $user = movementUser();
    movementPermission($user);

    $this->actingAs($user)->get('/admin/movement-plans')->assertStatus(200);
});

test('PM with create permission can create a plan in review status', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)
        ->post('/admin/movement-plans', movementPlanFields())
        ->assertRedirect();

    $plan = MovementPlan::first();

    expect($plan)->not->toBeNull()
        ->and($plan->status)->toBe('review')
        ->and($plan->request_number)->toStartWith('MOV-' . now()->year . '-')
        ->and($plan->created_by)->toBe($pm->id)
        ->and($plan->workflowActions()->where('action', 'create')->exists())->toBeTrue();
});

test('users without create permission cannot store a plan (403)', function () {
    $user = movementUser();
    movementPermission($user, ['can_create' => false]);

    $this->actingAs($user)
        ->post('/admin/movement-plans', movementPlanFields())
        ->assertStatus(403);

    expect(MovementPlan::count())->toBe(0);
});

test('return_time earlier than departure_time is rejected and redirects back', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)
        ->post('/admin/movement-plans', movementPlanFields([
            'departure_time' => '16:00',
            'return_time' => '08:00',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('return_time');

    expect(MovementPlan::count())->toBe(0);
});

test('PM2 with edit permission can approve and forward to movement officer', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $pm2 = movementUser();
    $officer = movementUser();
    movementPermission($pm2, ['can_edit' => true]);

    $this->actingAs($pm2)
        ->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id])
        ->assertRedirect();

    expect($plan->fresh()->status)->toBe('approved')
        ->and($plan->fresh()->refer_to_movement_officer_id)->toBe($officer->id)
        ->and($plan->fresh()->workflowActions()->where('action', 'approve')->exists())->toBeTrue();
});

test('PM without edit permission cannot approve (403)', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $officer = movementUser();

    $this->actingAs($pm)
        ->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id])
        ->assertStatus(403);

    expect($plan->fresh()->status)->toBe('review');
});

test('actions are rejected when the plan is not in the expected stage', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);

    $this->actingAs($officer)
        ->post("/admin/movement-plans/{$plan->id}/assign", [
            'user_ids' => [$officer->id],
            'role_labels' => ['متابعة'],
        ])
        ->assertStatus(403);

    expect($plan->fresh()->status)->toBe('review');
});

test('movement officer can reject with a reason', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);

    $this->actingAs($pm2)
        ->post("/admin/movement-plans/{$plan->id}/reject", ['reason' => 'غير ممكن حالياً'])
        ->assertRedirect();

    expect($plan->fresh()->status)->toBe('rejected')
        ->and($plan->fresh()->reason)->toBe('غير ممكن حالياً')
        ->and($plan->fresh()->workflowActions()->where('action', 'reject')->exists())->toBeTrue();
});

test('movement officer can assign multiple recipients then complete the plan', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();

    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);
    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id]);

    $recipientA = movementUser();
    $recipientB = movementUser();

    $this->actingAs($officer)
        ->post("/admin/movement-plans/{$plan->id}/assign", [
            'user_ids' => [$recipientA->id, $recipientB->id],
            'role_labels' => ['لوجستي', 'سائق'],
        ])
        ->assertRedirect();

    expect($plan->fresh()->status)->toBe('assigned')
        ->and($plan->fresh()->assigned_by)->toBe($officer->id)
        ->and($plan->fresh()->recipients()->count())->toBe(2)
        ->and($plan->fresh()->recipients()->pluck('role_label')->sort()->values()->all())->toBe(['سائق', 'لوجستي']);

    $this->actingAs($officer)
        ->post("/admin/movement-plans/{$plan->id}/complete")
        ->assertRedirect();

    expect($plan->fresh()->status)->toBe('completed')
        ->and($plan->fresh()->completed_at)->not->toBeNull()
        ->and($plan->fresh()->workflowActions()->count())->toBe(4);
});

test('assigning a plan replaces the previous recipients', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();

    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);
    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id]);

    $recipientA = movementUser();
    $recipientB = movementUser();

    $this->actingAs($officer)->post("/admin/movement-plans/{$plan->id}/assign", [
        'user_ids' => [$recipientA->id],
        'role_labels' => ['أولى'],
    ]);
    $this->actingAs($officer)->post("/admin/movement-plans/{$plan->id}/assign", [
        'user_ids' => [$recipientB->id],
        'role_labels' => ['ثانية'],
    ]);

    expect($plan->fresh()->recipients()->count())->toBe(1)
        ->and($plan->fresh()->recipients()->first()->user_id)->toBe($recipientB->id);
});

test('index scope: unrelated users with view permission do not see other plans', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $outsider = movementUser();
    movementPermission($outsider);

    $this->actingAs($outsider)
        ->get('/admin/movement-plans')
        ->assertOk()
        ->assertDontSeeText(MovementPlan::first()->purpose);
});

test('index scope: recipients see plans they were assigned to', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();

$pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);
    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id]);

    $recipient = movementUser();
    movementPermission($recipient);

    $this->actingAs($officer)->post("/admin/movement-plans/{$plan->id}/assign", [
        'user_ids' => [$recipient->id],
        'role_labels' => ['متابعة'],
    ]);

    $this->actingAs($recipient)
        ->get('/admin/movement-plans')
        ->assertOk()
        ->assertSeeText($plan->purpose);
});

test('delete requires delete permission', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $admin = movementUser();
    movementPermission($admin, ['can_delete' => true]);

    $this->actingAs($admin)
        ->delete("/admin/movement-plans/{$plan->id}")
        ->assertRedirect();

    expect(MovementPlan::count())->toBe(0);
});

test('create form renders for user with create permission', function () {
    $user = movementUser();
    movementPermission($user, ['can_create' => true]);

    $this->actingAs($user)
        ->get('/admin/movement-plans/create')
        ->assertOk()
        ->assertSeeText('خطة حركة جديدة')
        ->assertSeeText('الغاية من الحركة');
});

test('create form is forbidden without create permission', function () {
    $user = movementUser();
    movementPermission($user);

    $this->actingAs($user)->get('/admin/movement-plans/create')->assertStatus(403);
});

test('show page renders the plan through every workflow stage', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    projectsManagerPermission($pm2);

    $recipient = movementUser();
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();

    $this->actingAs($pm2)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('بانتظار مراجعة إدارة المشاريع');

    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id]);
    $this->actingAs($pm2)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('أُحيلت لمسؤول الحركة');

    $this->actingAs($officer)->post("/admin/movement-plans/{$plan->id}/assign", [
        'user_ids' => [$recipient->id],
        'role_labels' => ['متابعة'],
    ]);
    $this->actingAs($officer)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('قيد المتابعة')
        ->assertSeeText('جهات المتابعة');

    $this->actingAs($officer)->post("/admin/movement-plans/{$plan->id}/complete");
    $this->actingAs($officer)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('منجزة');
});

test('show page renders a rejected plan with its reason', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    projectsManagerPermission($pm2);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();

    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/reject", ['reason' => 'نقص الحافلة']);
    $this->actingAs($pm2)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('مرفوضة')
        ->assertSeeText('نقص الحافلة');
});