<?php

use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\User;

function uiEmployee(array $models = [], string $type = 'employee'): User
{
    $user = User::factory()->create(['type' => $type, 'must_change_password' => false]);

    if ($models) {
        Permission::create([
            'user_id' => $user->id,
            'model_names' => $models,
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
    }

    return $user;
}

test('apps page only links to apps the user may open and shows healthcare as unavailable', function () {
    $limited = uiEmployee();

    $this->actingAs($limited)->get('/admin')
        ->assertOk()
        ->assertSee(route('admin.dashboard'), false)
        ->assertDontSee(route('admin.hr.employees.index'), false)
        ->assertDontSee(route('admin.students.index'), false)
        ->assertDontSee(route('admin.tech.issues.index'), false)
        ->assertSeeText('الرعاية الصحية')
        ->assertSeeText('غير متاح حاليًا')
        ->assertDontSee('href="#"', false);

    $hr = uiEmployee(['App\Models\Admin\Hr\Employee']);
    $this->actingAs($hr)->get('/admin')
        ->assertOk()
        ->assertSee(route('admin.hr.employees.index'), false)
        ->assertDontSee(route('admin.students.index'), false);
});

test('site admin dashboard shows counts only for permitted models and an empty state otherwise', function () {
    Center::create(['name' => 'مركز أ']);
    Center::create(['name' => 'مركز ب']);

    $this->actingAs(uiEmployee())->get('/admin/dashboard')
        ->assertOk()
        ->assertSeeText('لا توجد مؤشرات متاحة لحسابك')
        ->assertDontSee(route('admin.centers.index'), false);

    $this->actingAs(uiEmployee(['App\Models\Admin\Center']))->get('/admin/dashboard')
        ->assertOk()
        ->assertSee(route('admin.centers.index'), false)
        ->assertDontSeeText('لا توجد مؤشرات متاحة لحسابك')
        ->assertDontSee(route('admin.users.index'), false);
});

test('users list keeps active filters when switching tabs and after pagination', function () {
    $admin = uiEmployee([], 'super-admin');
    $center = Center::create(['name' => 'مركز اختبار']);

    $html = $this->actingAs($admin)
        ->get('/admin/users?center_id='.$center->id.'&search=abc')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('center_id='.$center->id)
        ->and($html)->toContain('type=employee&amp;search=abc')
        ->and($html)->toContain('مسح (2)');
});

test('sidebar sections are semantic toggle buttons and the sidebar is a labelled navigation', function () {
    $this->actingAs(uiEmployee([], 'super-admin'))->get('/admin')
        ->assertOk()
        ->assertSee('<nav class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">', false)
        ->assertSee('class="nav-section" aria-expanded="true"', false)
        ->assertSee('aria-controls="sidebar"', false);
});
