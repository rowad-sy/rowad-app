<?php

use App\Exports\ProjectPathsExport;
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

test('project lists sort by path rank and then restart project order inside each path', function () {
    $health = ProjectPath::create(['name' => 'الصحة', 'code' => 'HLT', 'sort_order' => 2]);
    $education = ProjectPath::create(['name' => 'التعليم', 'code' => 'EDU', 'sort_order' => 1]);

    Project::create(['name' => 'تعليم 2', 'code' => 'E2', 'status' => 'active', 'path_id' => $education->id, 'sort_order' => 2]);
    Project::create(['name' => 'صحة 1', 'code' => 'H1', 'status' => 'active', 'path_id' => $health->id, 'sort_order' => 1]);
    Project::create(['name' => 'تعليم 1', 'code' => 'E1', 'status' => 'active', 'path_id' => $education->id, 'sort_order' => 1]);
    Project::create(['name' => 'صحة 2', 'code' => 'H2', 'status' => 'active', 'path_id' => $health->id, 'sort_order' => 2]);

    expect(Project::ordered()->pluck('name')->all())->toBe(['تعليم 1', 'تعليم 2', 'صحة 1', 'صحة 2']);

    $this->actingAs(pathsAdmin())->get('/admin/projects')
        ->assertOk()
        ->assertSeeInOrder(['تعليم 1', 'تعليم 2', 'صحة 1', 'صحة 2']);

    $this->actingAs(pathsAdmin())->get('/admin/paths')
        ->assertOk()
        ->assertSeeInOrder(['التعليم', 'الصحة']);

    $this->actingAs(pathsAdmin())->get('/admin/paths/tree')
        ->assertOk()
        ->assertSeeInOrder(['مسار التعليم', 'تعليم 1', 'تعليم 2', 'مسار الصحة', 'صحة 1', 'صحة 2']);
});

test('Excel export places each track in a three-column group like the print layout', function () {
    $export = new ProjectPathsExport(collect([
        [
            'title' => 'التعليم', 'code' => 'EDU', 'sort_order' => 1, 'orphan' => false,
            'items' => [
                ['name' => 'رواد العلم', 'sort_order' => 1, 'status' => 'فعال', 'status_key' => 'active'],
                ['name' => 'أثر', 'sort_order' => 2, 'status' => 'مغلق', 'status_key' => 'closed'],
            ],
        ],
        [
            'title' => 'الصحة', 'code' => 'HLT', 'sort_order' => 4, 'orphan' => false,
            'items' => [
                ['name' => 'الرعاية الصحية', 'sort_order' => 1, 'status' => 'فعال', 'status_key' => 'active'],
            ],
        ],
    ]));
    $rows = $export->array();

    expect($rows[2][0])->toBe('1 — التعليم')
        ->and($rows[2][3])->toBe('4 — الصحة')
        ->and($rows[3][0])->toBe('EDU')
        ->and($rows[4])->toBe(['#', 'المشروع', 'الحالة', '#', 'المشروع', 'الحالة'])
        ->and($rows[5])->toBe([1, 'رواد العلم', 'فعال', 1, 'الرعاية الصحية', 'فعال'])
        ->and($rows[6])->toBe([2, 'أثر', 'مغلق', '', '', '']);
});

test('pathless projects render in a dedicated column', function () {
    Project::create(['name' => 'يتيم المسار', 'code' => '9999', 'status' => 'pending']);

    $this->actingAs(pathsAdmin())->get('/admin/paths/tree')
        ->assertOk()
        ->assertSee('مشاريع بدون مسار')
        ->assertSee('يتيم المسار');

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/pdf')
        ->assertOk()
        ->assertDontSee('يتيم المسار');

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/pdf?include_orphans=1')
        ->assertOk()
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
        ->post('/admin/projects', ['name' => 'من داخل المسار', 'path_id' => $path->id, 'sort_order' => 1])
        ->assertRedirect();
    expect(Project::firstWhere('name', 'من داخل المسار')->path_id)->toBe($path->id);
});

test('adds a project directly inside its path card and returns to the tree', function () {
    $path = ProjectPath::create(['name' => 'التعليم', 'code' => 'EDU', 'sort_order' => 1]);
    Project::create(['name' => 'المشروع الأول', 'path_id' => $path->id, 'sort_order' => 1, 'status' => 'active']);

    $this->actingAs(pathsAdmin())->get('/admin/paths/tree')
        ->assertOk()
        ->assertSee('id="add-project-'.$path->id.'"', false)
        ->assertSee('name="return_to" value="paths_tree"', false)
        ->assertSee('value="2"', false);

    $this->actingAs(pathsAdmin())->post('/admin/projects', [
        'name' => 'المشروع الثاني',
        'path_id' => $path->id,
        'sort_order' => 2,
        'return_to' => 'paths_tree',
    ])->assertRedirect(route('admin.paths.tree'));

    $project = Project::firstWhere('name', 'المشروع الثاني');
    expect($project)->not->toBeNull()
        ->and($project->path_id)->toBe($path->id)
        ->and($project->sort_order)->toBe(2);
});

test('creates a new project from the path create or edit form', function () {
    $user = pathsAdmin();

    $this->actingAs($user)->get('/admin/paths/create')
        ->assertOk()
        ->assertSee('name="new_project[name]"', false)
        ->assertSee('name="new_project[sort_order]"', false);

    $this->actingAs($user)->post('/admin/paths', [
        'name' => 'مسار جديد',
        'sort_order' => 1,
        'new_project' => [
            'name' => 'مشروع أُنشئ مع المسار',
            'sort_order' => 1,
            'code' => 'N-1',
            'status' => 'active',
        ],
    ])->assertRedirect(route('admin.paths.index'));

    $path = ProjectPath::firstWhere('name', 'مسار جديد');
    $project = Project::firstWhere('name', 'مشروع أُنشئ مع المسار');
    expect($project)->not->toBeNull()
        ->and($project->path_id)->toBe($path->id)
        ->and($project->sort_order)->toBe(1)
        ->and($project->code)->toBe('N-1');

    $this->actingAs($user)->get(route('admin.paths.edit', $path))
        ->assertOk()
        ->assertSee('value="2"', false);

    $this->actingAs($user)->put(route('admin.paths.update', $path), [
        'name' => $path->name,
        'sort_order' => $path->sort_order,
        'projects' => [$project->id],
        'new_project' => [
            'name' => 'مشروع أُضيف عند تعديل المسار',
            'sort_order' => 2,
            'status' => 'studying',
        ],
    ])->assertRedirect(route('admin.paths.index'));

    $addedProject = Project::firstWhere('name', 'مشروع أُضيف عند تعديل المسار');
    expect($addedProject)->not->toBeNull()
        ->and($addedProject->path_id)->toBe($path->id)
        ->and($addedProject->sort_order)->toBe(2)
        ->and($addedProject->status)->toBe('studying');
});

test('paths exports follow the columns layout', function () {
    $path = ProjectPath::create(['name' => 'التطوير المهني', 'code' => 'DEV', 'sort_order' => 2, 'description' => null]);
    Project::create(['name' => 'دورة كوافير', 'code' => '3001', 'status' => 'active', 'path_id' => $path->id, 'sort_order' => 1]);
    Project::create(['name' => 'مشروع معلق', 'status' => 'pending', 'path_id' => $path->id, 'sort_order' => 2]);
    Project::create(['name' => 'مشروع مغلق', 'status' => 'closed', 'path_id' => $path->id, 'sort_order' => 3]);
    Project::create(['name' => 'مشروع قيد الدراسة', 'status' => 'studying', 'path_id' => $path->id, 'sort_order' => 4]);
    Project::create(['name' => 'مشروع داخلي', 'status' => 'internal', 'path_id' => $path->id, 'sort_order' => 5]);

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/excel')->assertOk();

    $this->actingAs(pathsAdmin())->get('/admin/paths/export/pdf')
        ->assertOk()
        ->assertSee('التطوير المهني')
        ->assertSee('دورة كوافير')
        ->assertSee('paths-print-table', false)
        ->assertSee('class="track-rank">2', false)
        ->assertSee('status-active', false)
        ->assertSee('status-pending', false)
        ->assertSee('status-closed', false)
        ->assertSee('status-studying', false)
        ->assertSee('status-internal', false)
        ->assertSee('الحالة')
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
