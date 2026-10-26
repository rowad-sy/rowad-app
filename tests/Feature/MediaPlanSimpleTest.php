<?php

use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;

const SIMPLE_MEDIA_MODEL = 'App\\Models\\Admin\\MediaPlan';

function simpleMediaUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function simpleMediaPermission(User $user, array $flags = []): Permission
{
    return Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => [SIMPLE_MEDIA_MODEL],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ], $flags));
}

function simpleProjectsManager(User $user): Group
{
    $role = Group::create([
        'name' => 'مدير المشاريع للاختبار',
        'kind' => Group::KIND_ROLE,
        'role_key' => Group::ROLE_PROJECTS_MANAGER,
    ]);
    Permission::create([
        'group_id' => $role->id,
        'model_names' => [SIMPLE_MEDIA_MODEL],
        'can_view' => true,
        'can_edit' => true,
    ]);
    $role->users()->attach($user->id);

    return $role;
}

function simpleRowadunaUser(): User
{
    $user = simpleMediaUser();
    simpleMediaPermission($user, ['can_edit' => true]);
    Permission::create([
        'user_id' => $user->id,
        'model_names' => ['page:admin.rowaduna.dashboard'],
        'can_view' => true,
    ]);

    return $user;
}

function simpleReporter(): User
{
    $user = simpleMediaUser();
    simpleMediaPermission($user, ['can_edit' => true]);

    return $user;
}

function simpleMediaEvent(string $date, string $start, string $end, string $name = 'فعالية'): array
{
    return [
        'event_date' => $date,
        'event_time' => $start,
        'event_end_time' => $end,
        'event_name' => $name,
        'location' => 'موقع الفعالية',
        'coverage_type' => 'تصوير',
        'notes' => 'ملاحظة الفعالية',
    ];
}

function simpleMediaPayload(Center $center, array $events, ?Project $project = null): array
{
    return [
        'month_date' => '2026-11',
        'center_id' => $center->id,
        'project_id' => $project?->id,
        'note' => 'ملاحظات الخطة',
        'events' => $events,
    ];
}

test('simple media plan goes through manager, optional rowaduna handoff, reporters and manual close', function () {
    $center = Center::create(['name' => 'مركز الإعلام']);
    $creator = simpleMediaUser();
    simpleMediaPermission($creator, ['can_create' => true, 'can_edit' => true]);
    $projectsManager = simpleMediaUser();
    $managerRole = simpleProjectsManager($projectsManager);
    $projectsManager2 = simpleMediaUser();
    $managerRole->users()->attach($projectsManager2->id);
    $rowaduna = simpleRowadunaUser();
    $reporter = simpleReporter();

    $this->actingAs($creator)->get('/admin/media-plans/create')
        ->assertOk()->assertSeeText('وقت البداية')->assertSeeText('وقت النهاية');

    $this->actingAs($creator)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-05', '13:00', '14:00', 'لقاء مجتمعي'),
    ]))->assertRedirect();

    $plan = MediaPlan::firstOrFail();
    $event = $plan->events()->firstOrFail();
    expect($plan->workflow_version)->toBe('simple')
        ->and($plan->status)->toBe('simple_review')
        ->and($event->event_time)->toContain('13:00')
        ->and($event->event_end_time)->toContain('14:00');
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/edit")
        ->assertOk()->assertSee('events[0][event_end_time]', false);

    $this->actingAs($projectsManager2)->get("/admin/media-plans/{$plan->id}")
        ->assertOk()->assertSeeText('قرار مدير المشاريع');
    $this->actingAs($projectsManager2)->post("/admin/media-plans/{$plan->id}/pm2-decide", [
        'decision' => 'approve',
    ])->assertRedirect();

    $plan->refresh();
    expect($plan->status)->toBe('simple_completed')
        ->and($plan->isLocked())->toBeTrue()
        ->and($plan->refer_to_rowaduna_id)->toBeNull();
    $this->actingAs($projectsManager)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve'])->assertStatus(403);

    $this->actingAs($creator)->put("/admin/media-plans/{$plan->id}", simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-05', '13:00', '14:00', 'تعديل غير مسموح'),
    ]))->assertStatus(403);

    $this->actingAs($projectsManager)->post("/admin/media-plans/{$plan->id}/reopen", [
        'refer_to_rowaduna_id' => $rowaduna->id,
        'note' => 'إحالة بعد الاعتماد',
    ])->assertRedirect();
    expect($plan->fresh()->status)->toBe('simple_rowaduna')
        ->and($plan->fresh()->locked_at)->not->toBeNull();
    $this->actingAs($rowaduna)->get(route('admin.rowaduna.dashboard'))
        ->assertOk()->assertSeeText('خطط ضمن مسؤولية روادنا');

    $this->actingAs($rowaduna)->post("/admin/media-plans/events/{$event->id}/assign", [
        'reporter_user_id' => $reporter->id,
    ])->assertRedirect();
    expect($plan->fresh()->status)->toBe('simple_in_progress')
        ->and($event->fresh()->coverage_status)->toBe('assigned');

    $this->actingAs($reporter)->post("/admin/media-plans/events/{$event->id}/reporter-decide", [
        'decision' => 'covered',
        'coverage_note' => 'تم التصوير والتوثيق',
    ])->assertRedirect();
    expect($event->fresh()->coverage_status)->toBe('covered')
        ->and($plan->fresh()->status)->toBe('simple_in_progress');

    $this->actingAs($rowaduna)->post("/admin/media-plans/{$plan->id}/finalize", [
        'note' => 'أُغلقت بعد مراجعة النتائج',
    ])->assertRedirect();
    expect($plan->fresh()->status)->toBe('simple_completed');

    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/print")
        ->assertOk()->assertSeeText('لقاء مجتمعي')->assertSeeText('13:00');
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/export")->assertOk();
});

test('center conflicts use overlapping event intervals across different projects, while adjacent slots are allowed', function () {
    $center = Center::create(['name' => 'مركز واحد']);
    $projectA = Project::create(['name' => 'مشروع ألف']);
    $projectB = Project::create(['name' => 'مشروع باء']);
    $manager = simpleMediaUser();
    simpleProjectsManager($manager);
    $creatorA = simpleMediaUser();
    simpleMediaPermission($creatorA, ['can_create' => true]);
    $creatorB = simpleMediaUser();
    simpleMediaPermission($creatorB, ['can_create' => true]);

    $this->actingAs($creatorA)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-12', '13:00', '14:00', 'الفعالية الأولى'),
    ], $projectA))->assertRedirect();

    $secondResponse = $this->actingAs($creatorB)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-12', '13:59', '14:30', 'فعالية متداخلة'),
    ], $projectB));
    $secondResponse->assertRedirect()->assertSessionHasErrors('events.0.event_time');
    expect(MediaPlan::count())->toBe(1);

    $this->actingAs($creatorB)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-12', '14:00', '15:00', 'فعالية متتابعة'),
    ], $projectB))->assertRedirect();
    expect(MediaPlan::count())->toBe(2);

    $otherCenter = Center::create(['name' => 'مركز آخر']);
    $this->actingAs($creatorB)->post('/admin/media-plans', simpleMediaPayload($otherCenter, [
        simpleMediaEvent('2026-11-12', '13:30', '14:30', 'فعالية بمركز آخر'),
    ], $projectB))->assertRedirect();
    expect(MediaPlan::count())->toBe(3);
});

test('a reporter must give a reason for missed coverage and rowaduna closes only after all events have results', function () {
    $center = Center::create(['name' => 'مركز التغطية']);
    $creator = simpleMediaUser();
    simpleMediaPermission($creator, ['can_create' => true]);
    $manager = simpleMediaUser();
    simpleProjectsManager($manager);
    $rowaduna = simpleRowadunaUser();
    $reporter = simpleReporter();

    $this->actingAs($creator)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-15', '09:00', '10:00', 'زيارة'),
        simpleMediaEvent('2026-11-15', '10:00', '11:00', 'لقاء'),
    ]))->assertRedirect();
    $plan = MediaPlan::firstOrFail();

    $this->actingAs($manager)->post("/admin/media-plans/{$plan->id}/pm2-decide", [
        'decision' => 'approve', 'refer_to_rowaduna_id' => $rowaduna->id,
    ])->assertRedirect();
    $events = $plan->events()->orderBy('event_time')->get();

    foreach ($events as $event) {
        $this->actingAs($rowaduna)->post("/admin/media-plans/events/{$event->id}/assign", [
            'reporter_user_id' => $reporter->id,
        ])->assertRedirect();
    }

    $this->actingAs($reporter)->post("/admin/media-plans/events/{$events[0]->id}/reporter-decide", [
        'decision' => 'not_covered',
    ])->assertSessionHasErrors('not_covered_reason');

    $this->actingAs($reporter)->post("/admin/media-plans/events/{$events[0]->id}/reporter-decide", [
        'decision' => 'not_covered', 'not_covered_reason' => 'تعذر الوصول للموقع',
    ])->assertRedirect();

    $this->actingAs($rowaduna)->post("/admin/media-plans/{$plan->id}/finalize")->assertSessionHas('error');
    expect($plan->fresh()->status)->toBe('simple_in_progress');

    $this->actingAs($reporter)->post("/admin/media-plans/events/{$events[1]->id}/reporter-decide", [
        'decision' => 'covered', 'coverage_note' => 'تمت التغطية',
    ])->assertRedirect();
    $this->actingAs($rowaduna)->post("/admin/media-plans/{$plan->id}/finalize")->assertRedirect();
    expect($plan->fresh()->status)->toBe('simple_completed')
        ->and($plan->fresh()->events()->where('coverage_status', 'not_covered')->value('not_covered_reason'))->toBe('تعذر الوصول للموقع');
});

test('scoped viewers can read a plan but cannot act, and out-of-scope users cannot open it', function () {
    $center = Center::create(['name' => 'مركز محدود']);
    $otherCenter = Center::create(['name' => 'مركز خارج النطاق']);
    $creator = simpleMediaUser();
    simpleMediaPermission($creator, ['can_create' => true, 'can_view' => true]);
    $manager = simpleMediaUser();
    simpleProjectsManager($manager);

    $this->actingAs($creator)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-19', '11:00', '12:00', 'خطة ضمن النطاق'),
    ]))->assertRedirect();
    $plan = MediaPlan::firstOrFail();

    $reader = simpleMediaUser();
    simpleMediaPermission($reader, ['center_id' => $center->id]);
    $outside = simpleMediaUser();
    simpleMediaPermission($outside, ['center_id' => $otherCenter->id]);

    $this->actingAs($reader)->get('/admin/media-plans')->assertOk()->assertSeeText('مركز محدود');
    $this->actingAs($reader)->get("/admin/media-plans/{$plan->id}")
        ->assertOk()->assertDontSee('name="decision"', false);
    $this->actingAs($reader)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve'])->assertStatus(403);
    $this->actingAs($outside)->get("/admin/media-plans/{$plan->id}")->assertStatus(403);
});

test('simple plans require a manager role holder and a valid same-day interval', function () {
    $center = Center::create(['name' => 'مركز التحقق']);
    $creator = simpleMediaUser();
    simpleMediaPermission($creator, ['can_create' => true]);

    $this->actingAs($creator)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-22', '13:00', '14:00'),
    ]))->assertRedirect()->assertSessionHasErrors('role_assignment');
    expect(MediaPlan::count())->toBe(0);

    $manager = simpleMediaUser();
    simpleProjectsManager($manager);
    $this->actingAs($creator)->post('/admin/media-plans', simpleMediaPayload($center, [
        simpleMediaEvent('2026-11-22', '14:00', '13:00', 'مدة غير صحيحة'),
    ]))->assertRedirect()->assertSessionHasErrors('events.0.event_end_time');
    expect(MediaPlan::count())->toBe(0);
});
