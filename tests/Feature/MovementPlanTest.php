<?php

use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanEntry;
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

function movementEntry(array $overrides = []): array
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

function movementPlanFields(array $overrides = []): array
{
    return array_merge([
        'plan_month' => now()->format('Y-m'),
        'notes' => 'ملاحظة عامة على الخطة',
        'entries' => [movementEntry()],
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
        ->and($plan->plan_month->format('Y-m'))->toBe(now()->format('Y-m'))
        ->and($plan->request_number)->toStartWith('MOV-' . now()->year . '-')
        ->and($plan->created_by)->toBe($pm->id)
        ->and($plan->entries()->count())->toBe(1)
        ->and($plan->workflowActions()->where('action', 'create')->exists())->toBeTrue();
});

test('a monthly plan can hold multiple movement entries', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields([
        'entries' => [
            movementEntry(['movement_date' => now()->addDays(1)->toDateString(), 'purpose' => 'الحركة الأولى']),
            movementEntry(['movement_date' => now()->addDays(5)->toDateString(), 'purpose' => 'الحركة الثانية']),
            movementEntry(['movement_date' => now()->addDays(12)->toDateString(), 'departure_time' => null, 'return_time' => null, 'purpose' => 'الحركة الثالثة']),
        ],
    ]))->assertRedirect();

    $plan = MovementPlan::first();

    expect($plan->entries()->count())->toBe(3)
        ->and($plan->entries()->orderBy('movement_date')->pluck('purpose')->all())
        ->toBe(['الحركة الأولى', 'الحركة الثانية', 'الحركة الثالثة']);
});

test('a plan requires at least one movement entry', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)
        ->post('/admin/movement-plans', movementPlanFields(['entries' => []]))
        ->assertSessionHasErrors('entries');

    expect(MovementPlan::count())->toBe(0);
});

test('users without create permission cannot store a plan (403)', function () {
    $user = movementUser();
    movementPermission($user, ['can_create' => false]);

    $this->actingAs($user)
        ->post('/admin/movement-plans', movementPlanFields())
        ->assertStatus(403);

    expect(MovementPlan::count())->toBe(0);
});

test('return_time earlier than departure_time is rejected per entry', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)
        ->post('/admin/movement-plans', movementPlanFields([
            'entries' => [
                movementEntry(),
                movementEntry(['departure_time' => '16:00', 'return_time' => '08:00']),
            ],
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('entries.1.return_time');

    expect(MovementPlan::count())->toBe(0);
});

test('store accepts an explicit referral target chosen by the creator', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $referee = movementUser();
    movementPermission($referee, ['can_edit' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields([
        'refer_to_pm2_id' => $referee->id,
    ]))->assertRedirect();

    $plan = MovementPlan::first();

    expect($plan->refer_to_pm2_id)->toBe($referee->id)
        ->and($plan->currentRecipientIds())->toContain($referee->id);
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

test('creator can edit entries while the plan is under review', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());

    $plan = MovementPlan::first();
    $plan->load('entries');
    $keepId = $plan->entries->first()->id;

    $this->actingAs($pm)
        ->put("/admin/movement-plans/{$plan->id}", [
            'plan_month' => now()->addMonth()->format('Y-m'),
            'entries' => [
                array_merge(movementEntry(), ['id' => $keepId, 'purpose' => 'الحركة المعدّلة']),
                movementEntry(['movement_date' => now()->addDays(9)->toDateString(), 'purpose' => 'حركة جديدة']),
            ],
        ])
        ->assertRedirect();

    $plan = $plan->fresh();

    expect($plan->plan_month->format('Y-m'))->toBe(now()->addMonth()->format('Y-m'))
        ->and($plan->entries()->count())->toBe(2)
        ->and(MovementPlanEntry::find($keepId)->purpose)->toBe('الحركة المعدّلة');
});

test('editing removes entries the creator dropped from the form', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields([
        'entries' => [movementEntry(), movementEntry(['purpose' => 'حركة ثانية'])],
    ]));

    $plan = MovementPlan::first();
    $plan->load('entries');
    [$first, $second] = [$plan->entries->first(), $plan->entries->last()];

    $this->actingAs($pm)->put("/admin/movement-plans/{$plan->id}", [
        'plan_month' => now()->format('Y-m'),
        'entries' => [array_merge(movementEntry(), ['id' => $second->id])],
    ])->assertRedirect();

    expect($plan->fresh()->entries()->count())->toBe(1)
        ->and($plan->fresh()->entries()->first()->id)->toBe($second->id);
});

test('edit is forbidden for non-creators and after approval', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);
    $other = movementUser();
    movementPermission($other, ['can_edit' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields());
    $plan = MovementPlan::first();

    $this->actingAs($other)->get("/admin/movement-plans/{$plan->id}/edit")->assertStatus(403);
    $this->actingAs($pm)->get("/admin/movement-plans/{$plan->id}/edit")->assertOk();

    movementPermission($pm, ['can_edit' => true]);
    $this->actingAs($pm)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $other->id]);

    $this->actingAs($pm)->get("/admin/movement-plans/{$plan->id}/edit")->assertStatus(403);
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
        ->assertDontSeeText(MovementPlan::first()->request_number);
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
        ->assertSeeText($plan->request_number);
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

test('create form renders with month header, entries repeater and searchable referral', function () {
    $user = movementUser();
    movementPermission($user, ['can_create' => true]);

    $this->actingAs($user)
        ->get('/admin/movement-plans/create')
        ->assertOk()
        ->assertSeeText('خطة حركة جديدة')
        ->assertSeeText('شهر الخطة')
        ->assertSeeText('إحالة المراجعة إلى')
        ->assertSee('user-picker', false)
        ->assertSee('addMovementRow', false);
});

test('create form is forbidden without create permission', function () {
    $user = movementUser();
    movementPermission($user);

    $this->actingAs($user)->get('/admin/movement-plans/create')->assertStatus(403);
});

test('show page renders all movement entries through every workflow stage', function () {
    $pm = movementUser();
    movementPermission($pm, ['can_create' => true]);

    $pm2 = movementUser();
    movementPermission($pm2, ['can_edit' => true]);
    projectsManagerPermission($pm2);

    $recipient = movementUser();
    $officer = movementUser();
    movementPermission($officer, ['can_edit' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', movementPlanFields([
        'entries' => [movementEntry(), movementEntry(['purpose' => 'حركة يومية ثانية', 'movement_date' => now()->addDays(3)->toDateString()])],
    ]));

    $plan = MovementPlan::first();

    $this->actingAs($pm2)->get("/admin/movement-plans/{$plan->id}")
        ->assertOk()
        ->assertSeeText('بانتظار مراجعة إدارة المشاريع')
        ->assertSeeText('حركة يومية ثانية');

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
