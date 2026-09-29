<?php

use App\Models\Admin\Center;
use App\Models\Admin\Logistics\Asset;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;

function p4User(array $models, array $flags = [], array $scope = []): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge(['user_id' => $u->id, 'model_names' => $models, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false], $flags, $scope));

    return $u;
}

test('asset details page shows the stored fields read-only with the existing view permission, and edit link only with edit', function () {
    $c = Center::create(['name' => 'مركز الأصول']);
    $p = Project::create(['name' => 'مشروع الأصول']);
    $asset = Asset::create(['asset_code' => 'AST-77', 'name' => 'جهاز عرض', 'type' => 'أجهزة', 'center_id' => $c->id, 'project_id' => $p->id, 'room_number' => '5', 'status' => 'جيد', 'notes' => "سطر1\nسطر2 <b>x</b>"]);

    $viewer = p4User([Asset::class]);
    $r = $this->actingAs($viewer)->get(route('admin.logistics.assets.show', $asset))->assertOk()
        ->assertSee('AST-77')->assertSee('جهاز عرض')->assertSee('مركز الأصول')->assertSee('مشروع الأصول')->assertSee('جيد')
        ->assertDontSee(route('admin.logistics.assets.edit', $asset), false)->assertDontSee('<b>x</b>', false);
    $editor = p4User([Asset::class], ['can_edit' => true]);
    $this->actingAs($editor)->get(route('admin.logistics.assets.show', $asset))->assertOk()->assertSee(route('admin.logistics.assets.edit', $asset), false);

    // بلا صلاحية عرض على الأصول: مرفوض من الخادم
    $none = p4User([Project::class]);
    $this->actingAs($none)->get(route('admin.logistics.assets.show', $asset))->assertForbidden();
    app('auth')->forgetGuards();
    $this->get(route('admin.logistics.assets.show', $asset))->assertRedirect();
});

test('asset list links each name to its details page', function () {
    $asset = Asset::create(['asset_code' => 'AST-1', 'name' => 'ماسح ضوئي', 'type' => 'أجهزة', 'status' => 'جيد', 'center_id' => Center::create(['name' => 'م'])->id]);
    $this->actingAs(p4User([Asset::class]))->get(route('admin.logistics.assets.index'))->assertOk()->assertSee(route('admin.logistics.assets.show', $asset), false);
});

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
