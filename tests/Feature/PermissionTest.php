<?php

use App\Helpers\PermissionHelper;
use App\Models\Admin\Center;
use App\Models\Admin\Cohort;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use App\Support\PermissionModelCatalog;

/*
 * سلسلة اختبارات إصلاح الصلاحيات:
 * كل صلاحية محفوظة = سجل مستقل لكل (تعيين × موديل) بنطاقه وأعلامه.
 */

function permissionMatrixKey(string $model): int
{
    return PermissionModelCatalog::keyFor($model);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['type' => 'super-admin']);
    $this->actingAs($this->admin);

    $this->center = Center::create(['name' => 'مركز اختبار', 'address' => 'عنوان', 'phone' => '01111111111']);
    $this->project = Project::create(['name' => 'مشروع اختبار', 'description' => 'وصف']);
    $this->cohort = Cohort::create(['name' => 'فوج اختبار', 'project_id' => $this->project->id]);
});

test('matrix store creates one record per model with independent flags', function () {
    $user = User::factory()->create();
    $student = 'App\Models\Admin\Student\Student';
    $course = 'App\Models\Admin\Student\Course';

    $this->post(route('admin.permissions.store'), [
        'rows' => [0 => ['assign_to' => 'user', 'user_id' => $user->id, 'group_id' => '']],
        'perms' => [
            0 => [
                permissionMatrixKey($student) => ['can_view' => '1'],
                permissionMatrixKey($course) => ['can_edit' => '1'],
            ],
        ],
    ])->assertRedirect(route('admin.permissions.index'));

    $records = Permission::where('user_id', $user->id)->get();

    expect($records)->toHaveCount(2);

    $studentRec = $records->firstWhere('model_names', [$student]);
    $courseRec = $records->firstWhere('model_names', [$course]);

    expect($studentRec->can_view)->toBeTrue();
    expect($studentRec->can_edit)->toBeFalse();
    expect($courseRec->can_edit)->toBeTrue();
    expect($courseRec->can_view)->toBeFalse();

    expect(PermissionHelper::can($user, $student, 'view'))->toBeTrue();
    expect(PermissionHelper::can($user, $student, 'edit'))->toBeFalse();
    expect(PermissionHelper::can($user, $course, 'edit'))->toBeTrue();
    expect(PermissionHelper::can($user, $course, 'view'))->toBeFalse();
});

test('shared scope is applied to every generated record', function () {
    $user = User::factory()->create();
    $student = 'App\Models\Admin\Student\Student';
    $course = 'App\Models\Admin\Student\Course';

    $this->post(route('admin.permissions.store'), [
        'rows' => [0 => ['assign_to' => 'user', 'user_id' => $user->id, 'group_id' => '']],
        'perms' => [
            0 => [
                permissionMatrixKey($student) => ['can_view' => '1'],
                permissionMatrixKey($course) => ['can_view' => '1'],
            ],
        ],
        'center_id' => $this->center->id,
        'project_id' => $this->project->id,
        'cohort_id' => $this->cohort->id,
    ])->assertRedirect(route('admin.permissions.index'));

    foreach ([$student, $course] as $model) {
        $record = Permission::where('user_id', $user->id)->whereJsonContains('model_names', $model)->first();
        expect($record)->not->toBeNull();
        expect($record->center_id)->toBe($this->center->id);
        expect($record->project_id)->toBe($this->project->id);
        expect($record->cohort_id)->toBe($this->cohort->id);
    }
});

test('permissions are merged between direct assignments and group memberships', function () {
    $user = User::factory()->create();
    $group = Group::create(['name' => 'مجموعة اختبار']);
    $user->groups()->attach($group);
    $student = 'App\Models\Admin\Student\Student';
    $course = 'App\Models\Admin\Student\Course';

    Permission::create([
        'group_id' => $group->id,
        'model_names' => [$student],
        'can_view' => true,
    ]);
    Permission::create([
        'user_id' => $user->id,
        'model_names' => [$course],
        'can_edit' => true,
    ]);

    expect(PermissionHelper::can($user, $student, 'view'))->toBeTrue();
    expect(PermissionHelper::can($user, $course, 'edit'))->toBeTrue();
    expect(PermissionHelper::can($user, $course, 'view'))->toBeFalse();
    expect(PermissionHelper::can($user, $student, 'edit'))->toBeFalse();
});

test('group matrix row grants permissions to its members', function () {
    $group = Group::create(['name' => 'مجموعة']);
    $user = User::factory()->create();
    $user->groups()->attach($group);
    $student = 'App\Models\Admin\Student\Student';

    $this->post(route('admin.permissions.store'), [
        'rows' => [0 => ['assign_to' => 'group', 'user_id' => '', 'group_id' => $group->id]],
        'perms' => [
            0 => [
                permissionMatrixKey($student) => ['can_view' => '1'],
            ],
        ],
    ])->assertRedirect(route('admin.permissions.index'));

    expect(PermissionHelper::can($user, $student, 'view'))->toBeTrue();
});

test('matrix edit loads current state and update removes unchecked records', function () {
    $user = User::factory()->create();
    $student = 'App\Models\Admin\Student\Student';
    $course = 'App\Models\Admin\Student\Course';
    $studentKey = permissionMatrixKey($student);
    $courseKey = permissionMatrixKey($course);

    $studentPerm = Permission::create([
        'user_id' => $user->id,
        'model_names' => [$student],
        'can_view' => true,
    ]);
    Permission::create([
        'user_id' => $user->id,
        'model_names' => [$course],
        'can_edit' => true,
    ]);

    $html = $this->get(route('admin.permissions.edit', $studentPerm))
        ->assertOk()
        ->getContent();

    // الحالة الحالية تُحمَّل من القاعدة وتُعرض مفحوصة (مع تجاهل فواصل الأسطر في HTML)
    expect((bool) preg_match(
        '#name="perms\[0\]\[' . $studentKey . '\]\[can_view\]"\s+value="1" data-cat="2" data-row="0"\s+checked#',
        $html
    ))->toBeTrue();

    expect((bool) preg_match(
        '#name="perms\[0\]\[' . $courseKey . '\]\[can_edit\]"\s+value="1" data-cat="2" data-row="0"\s+checked#',
        $html
    ))->toBeTrue();

    $this->put(route('admin.permissions.update', $studentPerm), [
        'rows' => [0 => ['assign_to' => 'user', 'user_id' => $user->id, 'group_id' => '']],
        'perms' => [
            0 => [
                permissionMatrixKey($course) => ['can_edit' => '1', 'can_view' => '1'],
            ],
        ],
    ])->assertRedirect(route('admin.permissions.index'));

    $records = Permission::where('user_id', $user->id)->get();

    expect($records)->toHaveCount(1);
    expect($records->first()->model_names)->toEqual([$course]);
    expect(PermissionHelper::can($user, $course, 'view'))->toBeTrue();
    expect(PermissionHelper::can($user, $student, 'view'))->toBeFalse();
});

test('matrix store requires a valid assignee', function () {
    $response = $this->post(route('admin.permissions.store'), [
        'rows' => [0 => ['assign_to' => 'user', 'user_id' => '', 'group_id' => '']],
        'perms' => [],
    ]);

    $response->assertSessionHasErrors(['rows.0.user_id']);
});

test('permissions:expand splits multi-model records idempotently', function () {
    $user = User::factory()->create();
    $student = 'App\Models\Admin\Student\Student';
    $course = 'App\Models\Admin\Student\Course';

    Permission::create([
        'user_id' => $user->id,
        'model_names' => [$student, $course],
        'can_view' => true,
        'can_edit' => true,
    ]);

    Artisan::call('permissions:expand');
    Artisan::call('permissions:expand');

    $records = Permission::where('user_id', $user->id)->get();

    expect($records)->toHaveCount(2);
    expect($records->every(fn ($p) => count($p->model_names) === 1))->toBeTrue();

    $studentRec = $records->firstWhere('model_names', [$student]);
    $courseRec = $records->firstWhere('model_names', [$course]);

    expect($studentRec->can_view)->toBeTrue();
    expect($studentRec->can_edit)->toBeTrue();
    expect($courseRec->can_view)->toBeTrue();

    expect(PermissionHelper::can($user, $student, 'view'))->toBeTrue();
    expect(PermissionHelper::can($user, $course, 'edit'))->toBeTrue();

    expect(Permission::where('user_id', $user->id)->count())->toBe(2);
});