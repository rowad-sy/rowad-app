<?php

use App\Models\Admin\Project;
use App\Models\Admin\ProjectPath;
use App\Models\User;

function pathsAdmin(): User
{
    return User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
}

test('paths board renders one column per path with numbered projects and add-project buttons', function () {
    $path = ProjectPath::create(['name' => 'الرعاية الصحية', 'code' => 'CHR', 'description' => null]);
    Project::create(['name' => 'مشروع العيون', 'code' => '2501', 'status' => 'active', 'path_id' => $path->id]);
    Project::create(['name' => 'مشروع الأسنان', 'code' => '2502', 'status' => 'closed', 'path_id' => $path->id]);

    $html = $this->actingAs(pathsAdmin())->get('/admin/paths/tree')
        ->assertOk()
        ->assertSee('مسار الرعاية الصحية')
        ->assertSee('CHR')
        ->assertSee('مشروع العيون')
        ->assertSee('مشروع الأسنان')
        ->getContent();

    // ترقيم المشاريع داخل العمود + زر إضافة مشروع يربط بالمسار
    expect($html)->toContain('pc-list')
        ->and($html)->toContain(route('admin.projects.create', ['path' => $path->id]));
});

test('pathless projects render in a dedicated column', function () {
    Project::create(['name' => 'يتيم المسار', 'code' => '9999', 'status' => 'pending']);

    $this->actingAs(pathsAdmin())->get('/admin/paths/tree')
        ->assertOk()
        ->assertSee('مشاريع بدون مسار')
        ->assertSee('يتيم المسار');
});

test('creating a project from inside a path preselects that path', function () {
    $path = ProjectPath::create(['name' => 'التنمية التعليمية', 'code' => 'EDU', 'description' => null]);

    $html = $this->actingAs(pathsAdmin())
        ->get('/admin/projects/create?path=' . $path->id)
        ->assertOk()
        ->assertSee('إضافة مشروع')
        ->getContent();

    expect($html)->toMatch('/value="' . $path->id . '"[^>]*selected/');

    // والحفظ بعد التوجه من المسار ينشئ المشروع داخله فعلاً
    $this->actingAs(pathsAdmin())
        ->post('/admin/projects', ['name' => 'من داخل المسار', 'path_id' => $path->id])
        ->assertRedirect();
    expect(Project::firstWhere('name', 'من داخل المسار')->path_id)->toBe($path->id);
});

test('paths exports follow the columns layout', function () {
    $path = ProjectPath::create(['name' => 'التطوير المهني', 'code' => 'DEV', 'description' => null]);
    Project::create(['name' => 'دورة كوافير', 'code' => '3001', 'status' => 'active', 'path_id' => $path->id]);

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/excel')->assertOk();

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/pdf')
        ->assertOk()
        ->assertSee('التطوير المهني')
        ->assertSee('دورة كوافير')
        ->assertSee('قائمة المسارات والمشاريع')
        ->assertSee('مؤسسة الرواد للتعاون والتنمية');
});

test('status filter narrows the columns board', function () {
    $path = ProjectPath::create(['name' => 'الإغاثة', 'code' => 'HUM', 'description' => null]);
    Project::create(['name' => 'نشط هنا', 'code' => '1', 'status' => 'active', 'path_id' => $path->id]);
    Project::create(['name' => 'مغلق هنا', 'code' => '2', 'status' => 'closed', 'path_id' => $path->id]);

    $this->actingAs(pathsAdmin())->get('/admin/paths/tree?status=active')
        ->assertOk()
        ->assertSee('نشط هنا')
        ->assertDontSee('مغلق هنا');

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/pdf?status=closed')
        ->assertOk()
        ->assertSee('مغلق هنا')
        ->assertDontSee('نشط هنا');
});
