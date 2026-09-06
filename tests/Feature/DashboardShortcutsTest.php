<?php

use App\Models\Admin\Permission;
use App\Models\User;

const PM_DASH_MODEL = 'page:admin.project-manager.dashboard';
const PO_DASH_MODEL = 'page:admin.project-officer.dashboard';
const SHORTCUT_MODELS = [
    'App\Models\Admin\MovementPlan',
    'App\Models\Admin\MediaPlan',
    'App\Models\Admin\Tech\TechIssue',
    'App\Models\Admin\Logistics\PurchaseRequest',
];

function shortcutUser(string $pageModel): User
{
    $user = User::factory()->create([
        'type' => 'employee',
        'must_change_password' => false,
    ]);

    Permission::create([
        'user_id' => $user->id,
        'model_names' => array_merge([$pageModel], SHORTCUT_MODELS),
        'can_view' => true,
        'can_create' => true,
        'can_edit' => false,
        'can_delete' => false,
    ]);

    return $user;
}

test('project manager dashboard renders with shortcuts for an authorised user', function () {
    $user = shortcutUser(PM_DASH_MODEL);

    $this->actingAs($user)->get('/admin/project-manager')
        ->assertOk()
        ->assertSeeText('وصول سريع')
        ->assertSeeText('خطة حركة جديدة')
        ->assertSeeText('خطط الحركة')
        ->assertSeeText('خطة إعلامية جديدة')
        ->assertSeeText('تذكرة تقنية جديدة')
        ->assertSeeText('طلب شراء جديد');
});

test('project officer dashboard renders with shortcuts for an authorised user', function () {
    $user = shortcutUser(PO_DASH_MODEL);

    $this->actingAs($user)->get('/admin/project-officer')
        ->assertOk()
        ->assertSeeText('وصول سريع')
        ->assertSeeText('خطة حركة جديدة')
        ->assertSeeText('طلب شراء جديد');
});

test('dashboards are forbidden without the page permission', function () {
    $user = User::factory()->create([
        'type' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)->get('/admin/project-manager')->assertStatus(403);
    $this->actingAs($user)->get('/admin/project-officer')->assertStatus(403);
});