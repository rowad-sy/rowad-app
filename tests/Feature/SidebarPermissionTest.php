<?php

use App\Models\Admin\Permission;
use App\Models\User;

function sidebarUser(array $permissions): User
{
    $user = User::factory()->create([
        'type' => 'employee',
        'must_change_password' => false,
    ]);

    foreach ($permissions as $permission) {
        Permission::create([
            'user_id' => $user->id,
            'model_names' => [$permission],
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
    }

    return $user;
}

test('sidebar shows only the section the employee is allowed to see', function () {
    $user = sidebarUser(['App\Models\Admin\Hr\Employee']);

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('الرئيسية')
        ->assertSee('الموارد البشرية')
        ->assertSee('الموظفين')
        ->assertSee('التايم شيت')
        ->assertDontSee('اللوجستي')
        ->assertDontSee('قواعد الموافقات')
        ->assertDontSee('الطلاب')
        ->assertDontSee('المقررات')
        ->assertDontSee('إدارة المشاريع')
        ->assertDontSee('العلاج الفيزيائي')
        ->assertDontSee('متابعة المرضى')
        ->assertDontSee('التقنية')
        ->assertDontSee('المعدات التقنية')
        ->assertDontSee('النظام والتدقيق')
        ->assertDontSee('سجل التدقيق')
        ->assertDontSee('المراكز')
        ->assertDontSee('المستخدمين')
        ->assertDontSee('الصلاحيات');
});

test('super-admin sees every sidebar section', function () {
    $admin = User::factory()->create(['type' => 'super-admin']);

    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertSee('المراكز')
        ->assertSee('المستخدمين')
        ->assertSee('الصلاحيات')
        ->assertSee('الموظفين')
        ->assertSee('المناصب الوظيفية')
        ->assertSee('طلبات الشراء')
        ->assertSee('المخازن')
        ->assertSee('المقررات')
        ->assertSee('سجل التدقيق')
        ->assertSee('النظام والتدقيق')
        ->assertSee('العلاج الفيزيائي')
        ->assertSee('المعدات التقنية');
});

test('employee without any permission sees only the home section', function () {
    $user = User::factory()->create([
        'type' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('التطبيقات')
        ->assertSee('لوحة التحكم')
        ->assertDontSee('المراكز')
        ->assertDontSee('المستخدمين')
        ->assertDontSee('الموارد البشرية')
        ->assertDontSee('اللوجستي')
        ->assertDontSee('الطلاب')
        ->assertDontSee('إدارة المشاريع')
        ->assertDontSee('العلاج الفيزيائي')
        ->assertDontSee('التقنية')
        ->assertDontSee('النظام والتدقيق');
});