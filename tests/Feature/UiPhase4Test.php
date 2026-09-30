<?php

use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;

function p4User(array $models, array $flags = [], array $scope = []): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge(['user_id' => $u->id, 'model_names' => $models, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false], $flags, $scope));

    return $u;
}

test('error pages render with the shared layout, local Tajawal via app css, and a logout form only for signed-in users', function () {
    $guest = $this->get('/no-such-page-here');
    $guest->assertNotFound()->assertSee('الصفحة غير موجودة')->assertSee('الصفحة الرئيسية')->assertDontSee('تسجيل الخروج');
    expect($guest->getContent())->not->toContain('fonts.bunny.net');

    $user = p4User([Project::class]);
    $res = $this->actingAs($user)->get(route('admin.logistics.assets.index'));
    $res->assertForbidden()->assertSee('ليس لديك صلاحية')->assertSee('تسجيل الخروج')->assertSee(route('logout'), false)->assertSee(route('admin.home'), false);
    expect($res->getContent())->not->toContain('fonts.bunny.net');

    // الخروج من صفحة 403 يعمل ولا يمنح وصولًا
    $this->actingAs($user)->post(route('logout'))->assertRedirect();
    $this->assertGuest();
});

test('audit log filters are labelled, apply explicitly and survive pagination', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($admin)->get(route('admin.audit-logs.index', ['search' => 'zzz-none-zzz']))->assertOk()
        ->assertSee('for="f-event"', false)->assertSee('تطبيق')->assertSee('لا توجد نتائج تطابق الفلاتر');
});

test('profile, beneficiary and forced-password pages use the shared header', function () {
    $emp = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $this->actingAs($emp)->get(route('admin.profile'))->assertOk()->assertSee('class="page-title"', false)->assertDontSee('class="page-header"', false);
});
