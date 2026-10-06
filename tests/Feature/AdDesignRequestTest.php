<?php

use App\Models\Admin\AdDesignRequest;
use App\Models\Admin\Permission;
use App\Models\User;

const AD_MODEL = 'App\Models\Admin\AdDesignRequest';
const RW_PAGE = 'page:admin.rowaduna.dashboard';

function adEmployee(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function adGrant(User $user, array $flags = []): void
{
    Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => [AD_MODEL],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => true,
        'can_delete' => false,
    ], $flags));
}

test('AdDesign: full cycle from request to permanent publication', function () {
    $creator = adEmployee();
    adGrant($creator, ['can_create' => true]);
    $pm2 = adEmployee();
    adGrant($pm2);
    $rw = adEmployee();
    adGrant($rw);
    Permission::create(['user_id' => $rw->id, 'model_names' => [RW_PAGE], 'can_view' => true]);
    $designer = adEmployee();
    adGrant($designer);
    $publisher = adEmployee();
    adGrant($publisher);
    $outsider = adEmployee();
    adGrant($outsider);

    $this->actingAs($creator)->post('/admin/ad-design-requests', [
        'title' => 'إعلان دورة إنجليزي',
        'description' => 'بوستر A3 + منشور',
        'refer_to_pm2_id' => $pm2->id,
    ])->assertRedirect();

    $ad = AdDesignRequest::first();
    expect($ad->status)->toBe('pm2_review')
        ->and($ad->refer_to_rowaduna_id)->toBe($rw->id) // افتراضي من حاملي صفحة روادنا
        ->and($ad->currentRecipientIds())->toContain($pm2->id);

    // غير المحال إليه لا يعتمد — حتى السوبر ادمن
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($super)->post("/admin/ad-design-requests/{$ad->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id])->assertStatus(403);
    $this->actingAs($outsider)->post("/admin/ad-design-requests/{$ad->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id])->assertStatus(403);

    // مدير المشاريع يعتمد
    $this->actingAs($pm2)->post("/admin/ad-design-requests/{$ad->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id])
        ->assertRedirect();
    expect($ad->fresh()->status)->toBe('rowaduna_review');

    // روادنا يُسند لمصمم
    $this->actingAs($rw)->post("/admin/ad-design-requests/{$ad->id}/rowaduna-decide", ['designer_user_id' => $designer->id])
        ->assertRedirect();
    expect($ad->fresh()->status)->toBe('designing');

    // المصمم يرفع الرابط — وحده
    $this->actingAs($outsider)->post("/admin/ad-design-requests/{$ad->id}/designer-submit", ['design_url' => 'https://drive.google.com/x'])->assertStatus(403);
    $this->actingAs($designer)->post("/admin/ad-design-requests/{$ad->id}/designer-submit", [
        'design_url' => 'https://drive.google.com/design-v1',
        'design_note' => 'النسخة الأولى',
    ])->assertRedirect();

    $ad = $ad->fresh();
    expect($ad->status)->toBe('ready_for_review')
        ->and($ad->design_url)->toContain('drive.google.com');

    // صاحب الطلب يعيد مع ملاحظات — الإعادة بلا ملاحظات مرفوضة
    $this->actingAs($creator)->post("/admin/ad-design-requests/{$ad->id}/requester-decide", ['decision' => 'return'])
        ->assertRedirect()->assertSessionHas('error');
    $this->actingAs($creator)->post("/admin/ad-design-requests/{$ad->id}/requester-decide", [
        'decision' => 'return', 'note' => 'التاريخ خاطئ',
    ])->assertRedirect();
    $ad = $ad->fresh();
    expect($ad->status)->toBe('designing')->and($ad->revision_note)->toBe('التاريخ خاطئ');

    // المصمم يعيد الرفع وصاحب الطلب يعتمد
    $this->actingAs($designer)->post("/admin/ad-design-requests/{$ad->id}/designer-submit", ['design_url' => 'https://drive.google.com/design-v2'])
        ->assertRedirect();
    $ad = $ad->fresh();
    $this->actingAs($outsider)->post("/admin/ad-design-requests/{$ad->id}/requester-decide", ['decision' => 'approve', 'publisher_user_id' => $publisher->id])->assertStatus(403);
    $this->actingAs($creator)->post("/admin/ad-design-requests/{$ad->id}/requester-decide", ['decision' => 'approve', 'publisher_user_id' => $publisher->id])
        ->assertRedirect();

    $ad = $ad->fresh();
    expect($ad->status)->toBe('to_publish')->and($ad->approved_by)->toBe($creator->id);

    // الناشر يدخل روابط النشر فيُغلق
    $this->actingAs($publisher)->post("/admin/ad-design-requests/{$ad->id}/publish-final", [
        'platforms' => [
            ['platform' => 'facebook', 'url' => 'https://facebook.com/a/1'],
            ['platform' => 'instagram', 'url' => 'https://instagram.com/p/1'],
        ],
    ])->assertRedirect();

    $ad = $ad->fresh();
    expect($ad->status)->toBe('published')
        ->and(count($ad->publish_links))->toBe(2)
        ->and($ad->published_by)->toBe($publisher->id);

    // الرؤية: الغريب لا يرى، صاحب الطلب يرى الرابط في صفحته
    $this->actingAs($outsider)->get("/admin/ad-design-requests/{$ad->id}")->assertStatus(403);
    $this->actingAs($creator)->get("/admin/ad-design-requests/{$ad->id}")->assertOk()->assertSee('design-v2');
});

test('AdDesign: super-admin cannot be the requester decision either', function () {
    $creator = adEmployee();
    adGrant($creator, ['can_create' => true]);
    $pm2 = adEmployee();
    adGrant($pm2);
    $rw = adEmployee();
    adGrant($rw);
    $designer = adEmployee();
    adGrant($designer);

    $this->actingAs($creator)->post('/admin/ad-design-requests', [
        'title' => 'إعلان',
        'refer_to_pm2_id' => $pm2->id,
    ]);

    $ad = AdDesignRequest::latest('id')->first();
    $this->actingAs($pm2)->post("/admin/ad-design-requests/{$ad->id}/pm2-decide", ['decision' => 'approve', 'refer_to_rowaduna_id' => $rw->id]);
    $this->actingAs($rw)->post("/admin/ad-design-requests/{$ad->id}/rowaduna-decide", ['designer_user_id' => $designer->id]);
    $this->actingAs($designer)->post("/admin/ad-design-requests/{$ad->id}/designer-submit", ['design_url' => 'https://drive.google.com/1']);

    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($super)->post("/admin/ad-design-requests/{$ad->fresh()->id}/requester-decide", ['decision' => 'approve', 'publisher_user_id' => $super->id])
        ->assertStatus(403);
});

test('Rowaduna: dashboard shows only permitted cards for holders', function () {
    $rw = adEmployee();
    adGrant($rw);
    Permission::create(['user_id' => $rw->id, 'model_names' => [RW_PAGE], 'can_view' => true]);
    Permission::create(['user_id' => $rw->id, 'model_names' => ['App\Models\Admin\MediaPlan'], 'can_view' => true, 'can_edit' => true]);

    $this->actingAs($rw)->get('/admin/rowaduna')
        ->assertOk()
        ->assertSee('روادنا')
        ->assertSee('خطط بانتظار الإسناد');

    // من بلا صلاحية الصفحة لا يدخل اللوحة
    $plain = adEmployee();
    adGrant($plain);
    $this->actingAs($plain)->get('/admin/rowaduna')->assertStatus(403);
});
