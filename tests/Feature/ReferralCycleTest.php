<?php

use App\Models\Admin\Center;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\User;

const PR_MODEL = 'App\Models\Admin\Logistics\PurchaseRequest';
const MOV_MODEL = 'App\Models\Admin\MovementPlan';
const MED_MODEL = 'App\Models\Admin\MediaPlan';
const MED_PAGE = 'page:admin.project-manager.dashboard';

/*
 * الحالة: configured in per-test basis via cycleSuperAdmin or cycleEmployee.
 */

function cycleEmployee(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function cyclePermission(User $user, string $model, array $flags = []): Permission
{
    return Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => [$model],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ], $flags));
}

function cycleGrant(User $user, string $model, array $flags = []): void
{
    cyclePermission($user, $model, array_merge(['can_view' => true, 'can_edit' => true], $flags));
}

function cycleGrantPage(User $user): void
{
    cyclePermission($user, MED_PAGE, ['can_view' => true]);
}

function cycleCenter(): Center
{
    return Center::create(['name' => 'مركز عفرين', 'address' => null, 'phone' => null]);
}

function cycleProject(): Project
{
    return Project::create(['name' => 'مشروع الرواد', 'description' => null]);
}

/*
 * =====================================================================
 *  طلب شراء: دورة كاملة (إنشاء → تسعير → مدير مباشر → PM2 → مالية →
 *  مدير تنفيذي → اعتماد/قفل → تنفيذ) + الإعادة + الحصرية
 * =====================================================================
 */
test('PR: full approval cycle flows to lock and executes', function () {
    $center = cycleCenter();
    $project = cycleProject();

    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $logistics = cycleEmployee();
    cycleGrant($logistics, PR_MODEL);
    $direct = cycleEmployee();
    cycleGrant($direct, PR_MODEL);
    $pm2 = cycleEmployee();
    cycleGrant($pm2, PR_MODEL);
    $finance = cycleEmployee();
    cycleGrant($finance, PR_MODEL);
    $executive = cycleEmployee();
    cycleGrant($executive, PR_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, PR_MODEL, ['can_view' => true, 'can_edit' => true]);

    // إنشاء طلب شراء
    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'center_id' => $center->id,
        'project_id' => $project->id,
        'notes' => 'اختبار',
        'refer_to_logistics_id' => $logistics->id,
        'refer_to_direct_manager_id' => $direct->id,
        'items' => [
            ['description' => 'حاسوب محمول', 'quantity' => 2, 'unit' => 'جهاز', 'unit_price' => 500, 'notes' => null],
        ],
    ])->assertRedirect();

    $pr = PurchaseRequest::first();

    expect($pr)->not->toBeNull()
        ->and($pr->status)->toBe('pending')
        ->and($pr->currentRecipientIds())->toContain($logistics->id, $direct->id)
        ->and($pr->referrals()->where('step', 'logistics')->where('status', 'active')->exists())->toBeTrue()
        ->and($pr->referrals()->where('step', 'direct_manager')->where('status', 'active')->exists())->toBeTrue();

    // (0) من لا يملك الخطوة لا يستطيع التسعير
    $this->actingAs($outsider)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/price", [
            'budget_number' => 'B-1',
            'items' => [['id' => $pr->items()->first()->id, 'unit_price' => 600]],
        ])
        ->assertStatus(403);
    expect($pr->fresh()->status)->toBe('pending');

    // (1) اللوجستي يسعّر
    $this->actingAs($logistics)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/price", [
            'budget_number' => 'B-1',
            'items' => [['id' => $pr->items()->first()->id, 'unit_price' => 600]],
        ])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('priced')
        ->and($pr->budget_number)->toBe('B-1')
        ->and($pr->referrals()->where('step', 'logistics')->where('status', 'done')->exists())->toBeTrue()
        ->and($pr->referrals()->where('step', 'direct_manager')->where('status', 'active')->exists())->toBeTrue();

    // (2) إعادة إحالة لمدير مباشر آخر من الحامل الحالي — القديم يُحرم
    $direct2 = cycleEmployee();
    cycleGrant($direct2, PR_MODEL);

    $this->actingAs($direct)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/refer", [
            'step' => 'direct_manager',
            'to_user_id' => $direct2->id,
            'note' => 'تحويل لمدير آخر',
        ])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->refer_to_direct_manager_id)->toBe($direct2->id)
        ->and($pr->currentRecipientIds())->not->toContain($direct->id)
        ->and($pr->currentRecipientIds())->toContain($direct2->id);

    $this->actingAs($direct)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertStatus(403);

    // (3) المدير المباشر الجديد يوافق
    $this->actingAs($direct2)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('pm_approved')
        ->and($pr->refer_to_pm2_id)->toBe($pm2->id)
        ->and($pr->referrals()->where('step', 'direct_manager')->where('status', 'done')->exists())->toBeTrue()
        ->and($pr->referrals()->where('step', 'pm2')->where('status', 'active')->exists())->toBeTrue();

    // (4) مدير المشاريع
    $this->actingAs($pm2)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/pm2-decide", ['decision' => 'approve', 'refer_to_finance_id' => $finance->id])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('pm2_approved')
        ->and($pr->refer_to_finance_id)->toBe($finance->id)
        ->and($pr->referrals()->where('step', 'finance')->where('status', 'active')->exists())->toBeTrue();

    // (5) المالية
    $this->actingAs($finance)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/finance-decide", ['decision' => 'approve', 'refer_to_executive_id' => $executive->id])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('finance_approved')
        ->and($pr->finance_at)->not->toBeNull()
        ->and($pr->referrals()->where('step', 'executive')->where('status', 'active')->exists())->toBeTrue();

    // (6) المدير التنفيذي — قفل + اعتماد نهائي
    $this->actingAs($executive)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/executive-decide", ['decision' => 'approve', 'note' => 'معتمد'])
        ->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('approved')
        ->and($pr->isLocked())->toBeTrue()
        ->and($pr->locked_by)->toBe($executive->id)
        ->and($pr->approved_at)->not->toBeNull()
        ->and($pr->activeReferrals()->count())->toBe(0);

    // (7) تنفيذ اللوجستي فقط
    $this->actingAs($outsider)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/execute")
        ->assertStatus(403);

    $this->actingAs($logistics)
        ->post("/admin/logistics/purchase-requests/{$pr->id}/execute")
        ->assertRedirect();

    expect($pr->fresh()->status)->toBe('executed');
});

test('PR: show visibility is scoped to creator, holders and super-admin', function () {
    $center = cycleCenter();
    $project = cycleProject();

    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $logistics = cycleEmployee();
    cycleGrant($logistics, PR_MODEL);
    $direct = cycleEmployee();
    cycleGrant($direct, PR_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, PR_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'center_id' => $center->id,
        'project_id' => $project->id,
        'refer_to_logistics_id' => $logistics->id,
        'refer_to_direct_manager_id' => $direct->id,
        'items' => [['description' => 'ورق', 'quantity' => 1, 'unit' => 'رزمة', 'unit_price' => 10, 'notes' => null]],
    ])->assertRedirect();

    $pr = PurchaseRequest::first();

    $this->actingAs($outsider)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertStatus(403);
    $this->actingAs($creator)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertOk();
    $this->actingAs($logistics)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertOk();
    $this->actingAs($direct)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertOk();
});

/*
 * =====================================================================
 *  خطة حركة: دورة كاملة (إنشاء → PM2 → مسؤول حركة → تعيين متابعين →
 *  إنجاز) + الإعادة + الحصرية
 * =====================================================================
 */
test('Movement: full cycle flows to completion', function () {
    $pm = cycleEmployee();
    cyclePermission($pm, MOV_MODEL, ['can_create' => true, 'can_view' => true]);
    $pm2 = cycleEmployee();
    cycleGrantPage($pm2);
    cycleGrant($pm2, MOV_MODEL);
    $pm2b = cycleEmployee();
    cycleGrant($pm2b, MOV_MODEL);
    $officer = cycleEmployee();
    cycleGrant($officer, MOV_MODEL);
    $rek1 = cycleEmployee();
    $rek2 = cycleEmployee();
    $outsider = cycleEmployee();
    cyclePermission($outsider, MOV_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', [
        'movement_date' => now()->addDays(2)->toDateString(),
        'departure_time' => '08:00',
        'return_time' => '16:00',
        'from_location' => 'المكتب',
        'to_location' => 'المخيم',
        'purpose' => 'جولة ميدانية',
        'notes' => null,
    ])->assertRedirect();

    $plan = MovementPlan::first();

    expect($plan->status)->toBe('review')
        ->and($plan->refer_to_pm2_id)->toBe($pm2->id)
        ->and($plan->currentRecipientIds())->toContain($pm2->id);

    // إعادة إحالة PM2 → PM2 آخر قبل الموافقة
    $this->actingAs($pm2)
        ->post("/admin/movement-plans/{$plan->id}/refer", ['step' => 'pm2', 'to_user_id' => $pm2b->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->refer_to_pm2_id)->toBe($pm2b->id)
        ->and($plan->currentRecipientIds())->not->toContain($pm2->id);

    $this->actingAs($pm2)
        ->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id])
        ->assertStatus(403);

    // PM2 الجديد يوافق
    $this->actingAs($pm2b)
        ->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('approved')
        ->and($plan->refer_to_movement_officer_id)->toBe($officer->id)
        ->and($plan->referrals()->where('step', 'pm2')->where('status', 'done')->exists())->toBeTrue()
        ->and($plan->referrals()->where('step', 'movement_officer')->where('status', 'active')->exists())->toBeTrue();

    // مسؤول الحركة يعيّن متابعين
    $this->actingAs($officer)
        ->post("/admin/movement-plans/{$plan->id}/assign", [
            'user_ids' => [$rek1->id, $rek2->id],
            'role_labels' => ['لوجستي', 'سائق'],
        ])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('assigned')
        ->and($plan->referrals()->where('step', 'movement_officer')->where('status', 'done')->exists())->toBeTrue()
        ->and($plan->referrals()->where('step', 'recipient')->where('status', 'active')->count())->toBe(2)
        ->and($plan->currentRecipientIds())->toContain($rek1->id, $rek2->id);

    // شخص خارجي لا يستطيع الإنجاز في هذه المرحلة
    $this->actingAs($outsider)
        ->post("/admin/movement-plans/{$plan->id}/complete")
        ->assertStatus(403);

    // الإنجاز
    $this->actingAs($officer)
        ->post("/admin/movement-plans/{$plan->id}/complete")
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('completed')
        ->and($plan->completed_at)->not->toBeNull()
        ->and($plan->activeReferrals()->count())->toBe(0);
});

test('Movement: show visibility scoped to creator, holders and officers', function () {
    $pm = cycleEmployee();
    cyclePermission($pm, MOV_MODEL, ['can_create' => true, 'can_view' => true]);
    $pm2 = cycleEmployee();
    cycleGrantPage($pm2);
    cycleGrant($pm2, MOV_MODEL);
    $officer = cycleEmployee();
    cycleGrant($officer, MOV_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, MOV_MODEL, ['can_view' => true]);

    $this->actingAs($pm)->post('/admin/movement-plans', [
        'movement_date' => now()->addDays(2)->toDateString(),
        'purpose' => 'جولة ميدانية',
    ])->assertRedirect();

    $plan = MovementPlan::first();

    $this->actingAs($outsider)->get("/admin/movement-plans/{$plan->id}")->assertStatus(403);
    $this->actingAs($pm)->get("/admin/movement-plans/{$plan->id}")->assertOk();
    $this->actingAs($pm2)->get("/admin/movement-plans/{$plan->id}")->assertOk();

    $this->actingAs($pm2)->post("/admin/movement-plans/{$plan->id}/approve", ['movement_officer_id' => $officer->id]);
    $this->actingAs($officer)->get("/admin/movement-plans/{$plan->id}")->assertOk();
});

/*
 * =====================================================================
 *  خطة إعلامية: دورة كاملة (إنشاء → مدير مباشر (قفل) → PM2 → مدير
 *  إعلام → مسؤول إعلامي (علامات + إغلاق) + الإعادة + حصرية القفل
 * =====================================================================
 */
test('Media: full cycle flows to executed and locks after direct manager', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $mediaManager = cycleEmployee();
    cycleGrant($mediaManager, MED_MODEL);
    $officer = cycleEmployee();
    cycleGrant($officer, MED_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, MED_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-10-01',
        'note' => 'خطة توعية',
        'events' => [
            0 => ['event_date' => '2026-10-01', 'event_time' => '10:00', 'event_name' => 'لقاء أهالي'],
            1 => ['event_date' => '2026-10-02', 'event_time' => '12:00', 'event_name' => 'ندوة'],
        ],
    ])->assertRedirect();

    $plan = MediaPlan::first();

    expect($plan->status)->toBe('review')
        ->and($plan->refer_to_direct_manager_id)->toBe($direct->id)
        ->and($plan->currentRecipientIds())->toContain($direct->id);

    // غير الحامل لا يوافق
    $this->actingAs($outsider)
        ->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertStatus(403);

    // المدير المباشر يوافق → قفل
    $this->actingAs($direct)
        ->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('manager_approved')
        ->and($plan->isLocked())->toBeTrue()
        ->and($plan->locked_by)->toBe($direct->id)
        ->and($plan->referrals()->where('step', 'direct_manager')->where('status', 'done')->exists())->toBeTrue()
        ->and($plan->referrals()->where('step', 'pm2')->where('status', 'active')->exists())->toBeTrue();

    // بعد القفل لا يُعدَّل
    $this->actingAs($creator)
        ->put("/admin/media-plans/{$plan->id}", ['month_date' => '2026-10-01', 'note' => 'محاولة تعديل'])
        ->assertStatus(403);

    // PM2 يوافق
    $this->actingAs($pm2)
        ->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_media_manager_id' => $mediaManager->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('pm2_approved')
        ->and($plan->referrals()->where('step', 'media_manager')->where('status', 'active')->exists())->toBeTrue();

    // مدير الإعلام يوافق
    $this->actingAs($mediaManager)
        ->post("/admin/media-plans/{$plan->id}/media-manager-decide", ['decision' => 'approve', 'refer_to_media_officer_id' => $officer->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('media_manager_approved')
        ->and($plan->referrals()->where('step', 'media_officer')->where('status', 'active')->exists())->toBeTrue();

    // المسؤول الإعلامي — علامات الفعاليات + الإغلاق اليدوي
    $event1 = $plan->events()->get()[0];
    $event2 = $plan->events()->get()[1];

    $this->actingAs($officer)
        ->post("/admin/media-plans/events/{$event1->id}/mark", ['execution_status' => 'executed', 'execution_note' => 'تم'])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('executing')
        ->and($event1->fresh()->execution_status)->toBe('executed')
        ->and($event1->fresh()->execution_by)->toBe($officer->id);

    $this->actingAs($officer)
        ->post("/admin/media-plans/events/{$event2->id}/mark", ['execution_status' => 'not_executed'])
        ->assertRedirect();

    $this->actingAs($officer)
        ->post("/admin/media-plans/{$plan->id}/finalize")
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('executed')
        ->and($plan->approved_at)->not->toBeNull()
        ->and($plan->activeReferrals()->count())->toBe(0);
});

test('Media: re-referral of the media officer keeps officer as current holder', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $mediaManager = cycleEmployee();
    cycleGrant($mediaManager, MED_MODEL);
    $officer = cycleEmployee();
    cycleGrant($officer, MED_MODEL);
    $officer2 = cycleEmployee();
    cycleGrant($officer2, MED_MODEL);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-10-01',
        'events' => [0 => ['event_date' => '2026-10-01', 'event_time' => '10:00', 'event_name' => 'فعالية']],
    ])->assertRedirect();

    $plan = MediaPlan::first();

    $this->actingAs($direct)->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id]);
    $this->actingAs($pm2)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_media_manager_id' => $mediaManager->id]);
    $this->actingAs($mediaManager)->post("/admin/media-plans/{$plan->id}/media-manager-decide", ['decision' => 'approve', 'refer_to_media_officer_id' => $officer->id]);

    $plan = $plan->fresh();
    expect($plan->currentRecipientIds())->toContain($officer->id);

    // الحامل الحالي يعيد الإحالة لا يملكها غير الحامل
    $this->actingAs($officer)
        ->post("/admin/media-plans/{$plan->id}/refer", ['step' => 'media_officer', 'to_user_id' => $officer2->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->refer_to_media_officer_id)->toBe($officer2->id)
        ->and($plan->currentRecipientIds())->not->toContain($officer->id)
        ->and($plan->currentRecipientIds())->toContain($officer2->id);

    $this->actingAs($officer)
        ->post("/admin/media-plans/{$plan->id}/finalize")
        ->assertStatus(403);

    // المسؤول الجديد ينهي
    $this->actingAs($officer2)
        ->post("/admin/media-plans/{$plan->id}/finalize")
        ->assertRedirect();

    expect($plan->fresh()->status)->toBe('executed');
});

test('Media: show visibility scoped to creator and holders', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, MED_MODEL, ['can_view' => true]);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-10-01',
    ])->assertRedirect();

    $plan = MediaPlan::first();

    $this->actingAs($outsider)->get("/admin/media-plans/{$plan->id}")->assertStatus(403);
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}")->assertOk();
    $this->actingAs($direct)->get("/admin/media-plans/{$plan->id}")->assertOk();
});