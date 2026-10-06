<?php

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\Permission;
use App\Models\User;

function workspaceUser(array $flags = []): User
{
    $user = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => ['App\Models\Admin\MovementPlan'],
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ], $flags));

    return $user;
}

test('guest is redirected from workspace dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});

test('movement officer sees only the movement plans card without create action', function () {
    $officer = workspaceUser(['can_edit' => true]);

    $this->actingAs($officer)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSeeText('مساحة العمل')
        ->assertSeeText('خطط الحركة')
        ->assertSeeText('جدول خطط الحركة')
        ->assertSeeText('تصدير إلى Excel')
        ->assertSeeText('طباعة / حفظ PDF')
        ->assertDontSeeText('خطة حركة جديدة')
        ->assertDontSeeText('الخطة الإعلامية')
        ->assertDontSeeText('الطلاب');
});

test('project manager sees create action and other project modules', function () {
    $pm = workspaceUser(['can_create' => true]);

    $this->actingAs($pm)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSeeText('خطة حركة جديدة');
});

test('user with no permissions sees the empty workspace state', function () {
    $user = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSeeText('لا توجد وحدات متاحة بعد');
});

test('super admin workspace includes system administration and apps link', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSeeText('إدارة النظام')
        ->assertSeeText('سجل التدقيق')
        ->assertSeeText('كل التطبيقات');
});

test('movement plans excel export returns a spreadsheet for viewer', function () {
    $officer = workspaceUser(['can_edit' => true]);

    $response = $this->actingAs($officer)->get('/admin/movement-plans/export');

    // Maatwebsite يحوّل اسم الملف العربي إلى transiliteration — نتحقق بالامتداد والمحتوى
    $response->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))->toContain('.xlsx');
});

test('movement plans print page renders entries of visible plans only', function () {
    $pm = workspaceUser(['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', [
        'plan_month' => now()->format('Y-m'),
        'entries' => [
            ['movement_date' => now()->addDays(2)->toDateString(), 'purpose' => 'جولة توزيع'],
            ['movement_date' => now()->addDays(4)->toDateString(), 'purpose' => 'نقل مستلزمات'],
        ],
    ]);

    $viewer = workspaceUser();
    $this->actingAs($viewer)
        ->get('/admin/movement-plans/print')
        ->assertOk();

    $this->actingAs($pm)
        ->get('/admin/movement-plans/print')
        ->assertOk()
        ->assertSeeText('جولة توزيع')
        ->assertSeeText('نقل مستلزمات')
        ->assertSeeText('خطط الحركة');

    $outsider = workspaceUser();
    $this->actingAs($outsider)
        ->get('/admin/movement-plans/print')
        ->assertOk()
        ->assertDontSeeText('جولة توزيع');
});

test('export and print respect status and month filters', function () {
    $pm = workspaceUser(['can_create' => true]);
    $this->actingAs($pm)->post('/admin/movement-plans', [
        'plan_month' => now()->format('Y-m'),
        'entries' => [['movement_date' => now()->addDays(1)->toDateString(), 'purpose' => 'غاية مفلترة']],
    ]);

    $this->actingAs($pm)
        ->get('/admin/movement-plans/print?status=review&month='.now()->format('Y-m'))
        ->assertOk()
        ->assertSeeText('غاية مفلترة');

    $this->actingAs($pm)
        ->get('/admin/movement-plans/print?status=completed')
        ->assertOk()
        ->assertDontSeeText('غاية مفلترة');

    $this->actingAs($pm)
        ->get('/admin/movement-plans/export?month=2000-01')
        ->assertOk();
});
