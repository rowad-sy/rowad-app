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
 *  طلب شراء: دورة جديدة (إنشاء بإحالة ← موافقة1+توقيع ← موافقة2+توقيع ←
 *  موافقة تنفيذي+توقيع/قفل ← تعليم البنود منفذة). كل خطوة حصرية بالمحال إليه.
 * =====================================================================
 */
test('PR: full approval cycle with signatures flows to lock and executes', function () {
    $center = cycleCenter();
    $project = cycleProject();

    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $a1 = cycleEmployee();   // المدير المباشر (مدير المشاريع عادةً)
    cycleGrant($a1, PR_MODEL);
    $a2 = cycleEmployee();   // المالية
    cycleGrant($a2, PR_MODEL);
    $a3 = cycleEmployee();   // التنفيذي
    cycleGrant($a3, PR_MODEL);
    $logistics = cycleEmployee();
    cycleGrant($logistics, PR_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, PR_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'request_number' => 'PR-TEST-0001',
        'pr_date' => now()->toDateString(),
        'required_date' => now()->addDays(10)->toDateString(),
        'management_unit' => 'إدارة المشاريع',
        'center_id' => $center->id,
        'project_id' => $project->id,
        'refer_to_approver1_id' => $a1->id,
        'notes' => 'اختبار الدورة',
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('req.png'),
        'items' => [
            ['description' => 'حاسوب محمول', 'quantity' => 2, 'unit' => 'قطعة', 'currency' => 'USD', 'unit_price' => 500, 'budget_line' => '3.1.29'],
            ['description' => 'حبر', 'quantity' => 5, 'unit' => 'علبة', 'currency' => 'SYP', 'unit_price' => 120000],
        ],
    ])->assertRedirect();

    $pr = PurchaseRequest::first();
    expect($pr)->not->toBeNull()
        ->and($pr->status)->toBe('review')
        ->and($pr->request_number)->toBe('PR-TEST-0001')
        ->and($pr->items()->count())->toBe(2)
        ->and($pr->items()->orderBy('id')->value('budget_line'))->toBe('3.1.29')
        ->and($pr->currentRecipientIds())->toContain($a1->id);

    // الحصرية: غير المحال إليه لا يوافق (حتى صاحب صلاحية تعديل)
    $this->actingAs($outsider)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s.png'),
        'next_approver_id' => $a2->id,
    ])->assertStatus(403);

    // موافقة 1 (المدير المباشر)
    $this->actingAs($a1)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s1.png'),
        'next_approver_id' => $a2->id,
        'note' => 'موافق',
    ])->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('approved1')
        ->and($pr->refer_to_approver2_id)->toBe($a2->id)
        ->and($pr->signatures()->where('role', 'approver1')->exists())->toBeTrue();

    // موافقة 2 (المالية)
    $this->actingAs($a2)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s2.png'),
        'next_approver_id' => $a3->id,
    ])->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('approved2')
        ->and($pr->refer_to_approver3_id)->toBe($a3->id)
        ->and($pr->signatures()->where('role', 'approver2')->exists())->toBeTrue();

    // موافقة 3 (التنفيذي) — اختيار اللوجستي + قفل + اعتماد
    $this->actingAs($a3)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s3.png'),
        'logistics_user_id' => $logistics->id,
    ])->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('approved')
        ->and($pr->refer_to_logistics_id)->toBe($logistics->id)
        ->and($pr->isLocked())->toBeTrue()
        ->and($pr->signatures()->where('role', 'approver3')->exists())->toBeTrue()
        ->and($pr->activeReferrals()->count())->toBe(1); // logistics نشط

    // التعليم: اللوجستي فقط، وكل البنود ⇒ executed
    $this->actingAs($outsider)->post("/admin/logistics/purchase-requests/{$pr->id}/execute-items", [])->assertStatus(403);

    $ids = $pr->items()->pluck('id')->all();
    $this->actingAs($logistics)->post("/admin/logistics/purchase-requests/{$pr->id}/execute-items", [
        'executed_ids' => [$ids[0]],
    ])->assertRedirect();
    expect($pr->fresh()->status)->toBe('approved'); // بقي بند

    $this->actingAs($logistics)->post("/admin/logistics/purchase-requests/{$pr->id}/execute-items", [
        'executed_ids' => $ids,
    ])->assertRedirect();

    $pr = $pr->fresh();
    expect($pr->status)->toBe('executed')
        ->and($pr->items()->whereNotNull('executed_at')->count())->toBe(2)
        ->and($pr->activeReferrals()->count())->toBe(0);

    // الطباعة والتصدير متاحان لكل من يرى الطلب، ويعرضان الهوية والبنود
    $this->actingAs($creator)->get("/admin/logistics/purchase-requests/{$pr->id}/print")
        ->assertOk()
        ->assertSee('PR-TEST-0001')
        ->assertSee('مؤسسة الرواد للتعاون والتنمية')
        ->assertSee('حاسوب محمول')
        ->assertSee('3.1.29')
        ->assertSee('الإجمالي — ليرة سورية');

    $this->actingAs($creator)->get("/admin/logistics/purchase-requests/{$pr->id}/export")->assertOk();
    $this->actingAs($outsider)->get("/admin/logistics/purchase-requests/{$pr->id}/print")->assertStatus(403);
});

test('PR: super-admin cannot approve because step is strictly exclusive', function () {
    $center = cycleCenter();
    $project = cycleProject();
    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $a1 = cycleEmployee();
    cycleGrant($a1, PR_MODEL);
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);

    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'request_number' => 'PR-TEST-0002',
        'pr_date' => now()->toDateString(),
        'center_id' => $center->id,
        'project_id' => $project->id,
        'refer_to_approver1_id' => $a1->id,
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('req.png'),
        'items' => [['description' => 'ورق', 'quantity' => 1, 'unit' => 'علبة', 'currency' => 'USD', 'unit_price' => 10]],
    ]);

    $pr = PurchaseRequest::first();

    $this->actingAs($super)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s.png'),
        'next_approver_id' => $a1->id,
    ])->assertStatus(403);

    expect($pr->fresh()->status)->toBe('review');
});

test('PR: show visibility is scoped to creator, approvers and super-admin', function () {
    $center = cycleCenter();
    $project = cycleProject();

    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $a1 = cycleEmployee();
    cycleGrant($a1, PR_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, PR_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'request_number' => 'PR-TEST-0003',
        'pr_date' => now()->toDateString(),
        'center_id' => $center->id,
        'project_id' => $project->id,
        'refer_to_approver1_id' => $a1->id,
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('req.png'),
        'items' => [['description' => 'ورق', 'quantity' => 1, 'unit' => 'علبة', 'currency' => 'SYP', 'unit_price' => 10000]],
    ])->assertRedirect();

    $pr = PurchaseRequest::first();

    $this->actingAs($outsider)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertStatus(403);
    $this->actingAs($creator)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertOk();
    $this->actingAs($a1)->get("/admin/logistics/purchase-requests/{$pr->id}")->assertOk();
});

/*
 * =====================================================================
 *  خطة حركة: دورة كاملة (إنشاء → PM2 → مسؤول حركة → تعيين متابعين →
 *  إنجاز) + الإعادة + الحصرية
 * =====================================================================
 */
test('Maintenance: same cycle, separated by type and tabs, exports filtered by type', function () {
    $center = cycleCenter();
    $project = cycleProject();

    $creator = cycleEmployee();
    cyclePermission($creator, PR_MODEL, ['can_create' => true, 'can_view' => true]);
    $a1 = cycleEmployee();
    cycleGrant($a1, PR_MODEL);

    // طلب صيانة
    $this->actingAs($creator)->post('/admin/logistics/purchase-requests', [
        'request_number' => 'PM-TEST-0001',
        'request_type' => 'maintenance',
        'pr_date' => now()->toDateString(),
        'center_id' => $center->id,
        'project_id' => $project->id,
        'refer_to_approver1_id' => $a1->id,
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('req.png'),
        'items' => [['description' => 'صيانة مضخة', 'quantity' => 1, 'unit' => 'قطعة', 'currency' => 'SYP', 'unit_price' => 40000]],
    ])->assertRedirect();

    $pr = PurchaseRequest::where('request_number', 'PM-TEST-0001')->first();
    expect($pr)->not->toBeNull()
        ->and($pr->request_type)->toBe('maintenance')
        ->and($pr->status)->toBe('review')
        ->and($pr->typeLabel())->toBe('طلب صيانة');

    // موافقة المدير المباشر على طلب الصيانة تمر بنفس الدورة
    $this->actingAs($a1)->post("/admin/logistics/purchase-requests/{$pr->id}/approve", [
        'signature_image' => \Illuminate\Http\UploadedFile::fake()->image('s.png'),
        'next_approver_id' => $creator->id,
    ])->assertRedirect();
    expect($pr->fresh()->status)->toBe('approved1');

    // التبويبات تفصل النوعين، وزر القائمة المنسدلة يتيح إنشاء الاثنين من أي تبويب
    $this->actingAs($creator)->get('/admin/logistics/purchase-requests?type=maintenance')
        ->assertOk()->assertSee('PM-TEST-0001')
        ->assertSee('طلب صيانة جديد');
    $this->actingAs($creator)->get('/admin/logistics/purchase-requests?type=purchase')
        ->assertOk()->assertDontSee('PM-TEST-0001');

    // طباعة طلب الصيانة تعنون بنفسه
    $this->actingAs($creator)->get("/admin/logistics/purchase-requests/{$pr->id}/print")
        ->assertOk()->assertSee('طلب صيانة');
});

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
        'plan_month' => now()->format('Y-m'),
        'entries' => [
            [
                'movement_date' => now()->addDays(2)->toDateString(),
                'departure_time' => '08:00',
                'return_time' => '16:00',
                'from_location' => 'المكتب',
                'to_location' => 'المخيم',
                'purpose' => 'جولة ميدانية',
                'notes' => null,
            ],
        ],
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
        'plan_month' => now()->format('Y-m'),
        'entries' => [
            ['movement_date' => now()->addDays(2)->toDateString(), 'purpose' => 'جولة ميدانية'],
        ],
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
 *  خطة إعلامية — دورة روادنا: إنشاء → مدير مباشر (قفل) → مدير مشاريع →
 *  مسؤول روادنا (إسناد مراسل) → تغطية + رابط مواد → نشر مؤقت → مراجعة
 *  مدير المشروع → نشر دائم بروابط → إغلاق تلقائي. الحصرية صارمة حتى للسوبر.
 * =====================================================================
 */
test('Media: full rowaduna cycle flows to published and auto-closes plan', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $rw = cycleEmployee();
    cycleGrant($rw, MED_MODEL);
    $rep = cycleEmployee();
    cycleGrant($rep, MED_MODEL);
    $pub = cycleEmployee();
    cycleGrant($pub, MED_MODEL);
    $outsider = cycleEmployee();
    cyclePermission($outsider, MED_MODEL, ['can_view' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-11-01',
        'note' => 'خطة توعية',
        'events' => [
            0 => ['event_date' => '2026-11-05', 'event_time' => '10:00', 'event_name' => 'لقاء أهالي'],
        ],
    ])->assertRedirect();

    $plan = MediaPlan::first();
    expect($plan->status)->toBe('review')
        ->and($plan->refer_to_direct_manager_id)->toBe($direct->id)
        ->and($plan->currentRecipientIds())->toContain($direct->id);

    // الحصرية: حتى السوبر ادمن لا يوافق خطوة المدير المباشر
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($super)
        ->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertStatus(403);
    $this->actingAs($outsider)
        ->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertStatus(403);

    // المدير المباشر يوافق → قفل → pm2
    $this->actingAs($direct)
        ->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id])
        ->assertRedirect();
    $plan = $plan->fresh();
    expect($plan->status)->toBe('manager_approved')
        ->and($plan->isLocked())->toBeTrue();

    // مدير المشاريع يوافق → روادنا
    $this->actingAs($pm2)
        ->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id])
        ->assertRedirect();
    $plan = $plan->fresh();
    expect($plan->status)->toBe('rowaduna_review')
        ->and($plan->refer_to_rowaduna_id)->toBe($rw->id);

    // مسؤول روادنا يُسند الفعالية لمراسل
    $event = $plan->events()->first();
    $this->actingAs($outsider)->post("/admin/media-plans/events/{$event->id}/assign", ['reporter_user_id' => $rep->id])->assertStatus(403);
    $this->actingAs($rw)->post("/admin/media-plans/events/{$event->id}/assign", ['reporter_user_id' => $rep->id])->assertRedirect();

    $plan = $plan->fresh();
    $event = $event->fresh();
    expect($plan->status)->toBe('in_progress')
        ->and($event->coverage_status)->toBe('assigned')
        ->and($event->refer_to_reporter_id)->toBe($rep->id)
        ->and($event->isCurrentRecipient($rep->id))->toBeTrue();

    // المراسل: تمت التغطية + رابط المواد + إحالة للمونتير
    $this->actingAs($rep)->post("/admin/media-plans/events/{$event->id}/reporter-decide", [
        'decision' => 'covered',
        'media_items_url' => 'https://drive.google.com/drive/folders/demo-materials',
        'publisher_user_id' => $pub->id,
    ])->assertRedirect();

    $event = $event->fresh();
    expect($event->coverage_status)->toBe('covered')
        ->and($event->publish_status)->toBe('to_publish')
        ->and($event->media_items_url)->toContain('drive.google.com');

    // المونتير: نشر مؤقت + إحالة لمدير المشروع للمراجعة
    $this->actingAs($pub)->post("/admin/media-plans/events/{$event->id}/publisher-preview", [
        'preview_url' => 'https://youtube.com/watch?v=draft',
        'refer_to_reviewer_id' => $direct->id,
    ])->assertRedirect();
    expect($event->fresh()->publish_status)->toBe('to_review');

    // مراجع غير مخوّل لا يقرر
    $this->actingAs($outsider)->post("/admin/media-plans/events/{$event->id}/reviewer-decide", ['decision' => 'approve'])->assertStatus(403);

    // مدير المشروع يعيد النشر مع ملاحظات أولاً
    $this->actingAs($direct)->post("/admin/media-plans/events/{$event->id}/reviewer-decide", [
        'decision' => 'return', 'preview_feedback' => 'الشعار مقصوص في البداية',
    ])->assertRedirect();
    expect($event->fresh()->publish_status)->toBe('rework');

    // المونتير يعيد المعاينة ثم المدير يعتمد
    $this->actingAs($pub)->post("/admin/media-plans/events/{$event->id}/publisher-preview", [
        'preview_url' => 'https://youtube.com/watch?v=draft2',
        'refer_to_reviewer_id' => $direct->id,
    ])->assertRedirect();
    $this->actingAs($direct)->post("/admin/media-plans/events/{$event->id}/reviewer-decide", ['decision' => 'approve'])->assertRedirect();
    expect($event->fresh()->publish_status)->toBe('to_final');

    // المونتير: روابط النشر الدائم
    $this->actingAs($pub)->post("/admin/media-plans/events/{$event->id}/publish-final", [
        'platforms' => [
            ['platform' => 'facebook', 'url' => 'https://facebook.com/post/1'],
            ['platform' => 'youtube', 'url' => 'https://youtube.com/watch?v=final'],
        ],
    ])->assertRedirect();

    $event = $event->fresh();
    $plan = $plan->fresh();
    expect($event->publish_status)->toBe('published')
        ->and(count($event->publish_links))->toBe(2)
        ->and($event->published_by)->toBe($pub->id)
        ->and($plan->status)->toBe('executed');

    // طباعة وتصدير الخطة برابط المعاينة والمنصات
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/print")
        ->assertOk()
        ->assertSee('مؤسسة الرواد للتعاون والتنمية')
        ->assertSee('لقاء أهالي')
        ->assertSee('فيسبوك');
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/print?theme=rowaduna")
        ->assertOk()
        ->assertSee('روادنا');
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}/export")->assertOk();
    $this->actingAs($outsider)->get("/admin/media-plans/{$plan->id}/print")->assertStatus(403);
});

test('Media: not covered requires reason and can be rescheduled by project owner', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $rw = cycleEmployee();
    cycleGrant($rw, MED_MODEL);
    $rep = cycleEmployee();
    cycleGrant($rep, MED_MODEL);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-11-01',
        'events' => [0 => ['event_date' => '2026-11-10', 'event_time' => '12:00', 'event_name' => 'ندوة']],
    ]);

    $plan = MediaPlan::first();
    $this->actingAs($direct)->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id]);
    $this->actingAs($pm2)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id]);
    $plan = $plan->fresh();
    $event = $plan->events()->first();
    $this->actingAs($rw)->post("/admin/media-plans/events/{$event->id}/assign", ['reporter_user_id' => $rep->id]);

    // عدم التغطية بلا سبب → خطأ تحقق
    $this->actingAs($rep)->post("/admin/media-plans/events/{$event->id}/reporter-decide", ['decision' => 'not_covered'])
        ->assertSessionHasErrors('not_covered_reason');

    $this->actingAs($rep)->post("/admin/media-plans/events/{$event->id}/reporter-decide", [
        'decision' => 'not_covered', 'not_covered_reason' => 'تعذر الوصول للموقع',
    ])->assertRedirect();
    expect($event->fresh()->coverage_status)->toBe('not_covered');

    // إعادة الجدولة من مدير المشروع (صاحب الخطة) إلى موعد ومراسل آخرين
    $rep2 = cycleEmployee();
    cycleGrant($rep2, MED_MODEL);
    $this->actingAs($direct)->post("/admin/media-plans/events/{$event->id}/reschedule", [
        'event_date' => '2026-11-20', 'event_time' => '14:00', 'reporter_user_id' => $rep2->id,
    ])->assertRedirect();

    $event = $event->fresh();
    expect($event->coverage_status)->toBe('assigned')
        ->and($event->refer_to_reporter_id)->toBe($rep2->id)
        ->and($event->not_covered_reason)->toBeNull()
        ->and($event->isCurrentRecipient($rep2->id))->toBeTrue();

    // الحصرية: غريب لا يعيد الجدولة
    $outsider = cycleEmployee();
    cyclePermission($outsider, MED_MODEL, ['can_view' => true, 'can_edit' => true]);
    $this->actingAs($outsider)->post("/admin/media-plans/events/{$event->id}/reschedule", [
        'event_date' => '2026-11-21', 'event_time' => '14:00', 'reporter_user_id' => $rep2->id,
    ])->assertStatus(403);
});

test('Media: two coverages cannot be assigned to the same reporter at the same time', function () {
    $rw = cycleEmployee();
    cycleGrant($rw, MED_MODEL);
    $rep = cycleEmployee();
    cycleGrant($rep, MED_MODEL);

    $makePlan = function (string $month) use ($rw) {
        $creator = cycleEmployee();
        cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);

        $direct = cycleEmployee();
        cycleGrantPage($direct);
        cycleGrant($direct, MED_MODEL);
        $pm2 = cycleEmployee();
        cycleGrant($pm2, MED_MODEL);

        $this->actingAs($creator)->post('/admin/media-plans', [
            'month_date' => $month,
            'refer_to_direct_manager_id' => $direct->id,
            'events' => [0 => ['event_date' => '2026-11-15', 'event_time' => '09:00', 'event_name' => 'فعالية ' . $month]],
        ]);

        $plan = MediaPlan::latest('id')->first();

        $this->actingAs($direct)->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id]);
        $this->actingAs($pm2)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id]);

        return $plan->fresh();
    };

    $planA = $makePlan('2026-11-01');
    $eventA = $planA->events()->first();
    $this->actingAs($rw)->post("/admin/media-plans/events/{$eventA->id}/assign", ['reporter_user_id' => $rep->id])->assertRedirect();

    $planB = $makePlan('2026-11-01');
    $eventB = $planB->events()->first();
    $this->actingAs($rw)->post("/admin/media-plans/events/{$eventB->id}/assign", ['reporter_user_id' => $rep->id])
        ->assertSessionHasErrors('reporter_user_id');

    expect($eventB->fresh()->coverage_status)->toBe('pending');

    // ساعة مختلفة = لا تعارض
    $eventB->update(['event_time' => '11:00']);
    $this->actingAs($rw)->post("/admin/media-plans/events/{$eventB->id}/assign", ['reporter_user_id' => $rep->id])
        ->assertRedirect();
    expect($eventB->fresh()->coverage_status)->toBe('assigned');
});

test('Media: plan created by a project manager skips the direct manager', function () {
    $pm = cycleEmployee();
    cyclePermission($pm, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    cycleGrantPage($pm); // حامل لوحة مدير المشروع = هو مدير المشروع
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $rw = cycleEmployee();
    cycleGrant($rw, MED_MODEL);

    $this->actingAs($pm)->post('/admin/media-plans', [
        'month_date' => '2026-12-01',
        'skip_direct_manager' => '1',
        'refer_to_pm2_id' => $pm2->id,
        'events' => [0 => ['event_date' => '2026-12-03', 'event_time' => '10:00', 'event_name' => 'حفل']],
    ])->assertRedirect();

    $plan = MediaPlan::latest('id')->first();
    expect($plan->status)->toBe('pm2_review')
        ->and($plan->refer_to_direct_manager_id)->toBeNull()
        ->and($plan->refer_to_pm2_id)->toBe($pm2->id)
        ->and($plan->currentRecipientIds())->toContain($pm2->id);

    // مدير المشاريع يوافق → روادنا، ويُقفل هنا (لا كان مدير مباشر)
    $this->actingAs($pm2)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id])
        ->assertRedirect();

    $plan = $plan->fresh();
    expect($plan->status)->toBe('rowaduna_review')
        ->and($plan->isLocked())->toBeTrue()
        ->and($plan->locked_by)->toBe($pm2->id);
});

test('Media: show visibility includes reporters and holders, blocks outsiders', function () {
    $creator = cycleEmployee();
    cyclePermission($creator, MED_MODEL, ['can_create' => true, 'can_view' => true]);
    $direct = cycleEmployee();
    cycleGrantPage($direct);
    cycleGrant($direct, MED_MODEL);
    $rw = cycleEmployee();
    cycleGrant($rw, MED_MODEL);
    $rep = cycleEmployee();
    cycleGrant($rep, MED_MODEL);

    $this->actingAs($creator)->post('/admin/media-plans', [
        'month_date' => '2026-11-01',
        'refer_to_direct_manager_id' => $direct->id,
        'events' => [0 => ['event_date' => '2026-11-08', 'event_time' => '10:00', 'event_name' => 'زيارة']],
    ]);

    $plan = MediaPlan::first();

    $outsider = cycleEmployee();
    cyclePermission($outsider, MED_MODEL, ['can_view' => true]);
    $this->actingAs($outsider)->get("/admin/media-plans/{$plan->id}")->assertStatus(403);
    $this->actingAs($creator)->get("/admin/media-plans/{$plan->id}")->assertOk();
    $this->actingAs($direct)->get("/admin/media-plans/{$plan->id}")->assertOk();

    // نوصلها لروادنا ثم للمراسل، والمراسل يرى الخطة بسبب عهدته على الفعالية
    $pm2 = cycleEmployee();
    cycleGrant($pm2, MED_MODEL);
    $this->actingAs($direct)->post("/admin/media-plans/{$plan->id}/direct-manager-decide", ['decision' => 'approve', 'refer_to_pm2_id' => $pm2->id]);
    $this->actingAs($pm2)->post("/admin/media-plans/{$plan->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id]);
    $event = $plan->events()->first();
    $this->actingAs($rw)->post("/admin/media-plans/events/{$event->id}/assign", ['reporter_user_id' => $rep->id]);

    $this->actingAs($rep)->get("/admin/media-plans/{$plan->id}")->assertOk()->assertSee('زيارة');
});