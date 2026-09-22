<?php

use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Attendance;
use App\Models\Admin\Student\Student;
use App\Models\User;

/*
 * اختبارات نطاق صلاحية الطلاب (Student Scope)
 * ============================================
 * تغطي المشكلة المكتشفة: الطالب المرتبط بمشروع عبر جدول project_student فقط
 * (بدون students.project_id) لم يكن يظهر في سجل الحضور رغم ظهوره في صفحة الطلاب.
 *
 * كما تضمن عدم إمكانية تجاوز نطاق الصلاحية عبر باراميترات الواجهة (project_id / center_id).
 */

function createStudent(string $code, ?int $projectId = null, ?int $centerId = null, array $pivot = []): Student
{
    $student = Student::create([
        'student_code' => $code,
        'first_name_ar' => 'طالب_' . $code,
        'last_name_ar' => 'اختبار',
        'gender' => 'male',
        'status' => 'active',
        'project_id' => $projectId,
        'center_id' => $centerId,
    ]);

    foreach ($pivot as $p) {
        $student->projects()->attach($p);
    }

    return $student;
}

beforeEach(function () {
    $this->center1 = Center::create(['name' => 'مركز ١', 'address' => 'عنوان ١', 'phone' => '01111111111']);
    $this->center2 = Center::create(['name' => 'مركز ٢', 'address' => 'عنوان ٢', 'phone' => '02222222222']);
    $this->project1 = Project::create(['name' => 'مشروع الرواد', 'description' => 'وصف']);
    $this->project2 = Project::create(['name' => 'مشروع آخر', 'description' => 'وصف']);

    // مدير مشروع بنطاق project1 فقط (بدون نطاق مركز)
    $this->manager = User::factory()->create(['type' => 'user']);
    Permission::create([
        'user_id' => $this->manager->id,
        'model_names' => [Attendance::class],
        'project_id' => $this->project1->id,
        'can_view' => true,
        'can_create' => true,
    ]);
    Permission::create([
        'user_id' => $this->manager->id,
        'model_names' => [Student::class],
        'project_id' => $this->project1->id,
        'can_view' => true,
    ]);

    // طالب مرتبط عبر الوسيط فقط (هذا كان القضية الأصلية)
    $this->pivotStudent = createStudent('STU-PIVOT-1', null, $this->center1->id, [$this->project1->id]);

    // طالب مرتبط عبر العمود + الوسيط
    $this->columnStudent = createStudent('STU-COL-1', $this->project1->id, $this->center1->id, [$this->project1->id]);

    // طالب في مشروع آخر (خارج النطاق)
    $this->otherProjectStudent = createStudent('STU-OTHER-1', null, $this->center2->id, [$this->project2->id]);

    // طالب بدون أي مشروع
    $this->noProjectStudent = createStudent('STU-NONE-1', null, $this->center2->id);
});

test('سجل الحضور يعرض الطلاب المرتبطين بالمشروع عبر جدول project_student فقط', function () {
    $this->actingAs($this->manager);

    $response = $this->get(route('admin.students.attendance'));

    $response->assertOk();
    $response->assertSee('STU-PIVOT-1');
    $response->assertSee('STU-COL-1');
    $response->assertDontSee('STU-OTHER-1');
    $response->assertDontSee('STU-NONE-1');
});

test('سجل الحضور يمنع عرض طلاب خارج النطاق حتى مع تمرير project_id في الرابط', function () {
    $this->actingAs($this->manager);

    // محاولة تجاوز النطاق عبر تمرير مشروع أجنبي
    $response = $this->get(route('admin.students.attendance', ['project_id' => $this->project2->id]));

    $response->assertOk();
    $response->assertDontSee('STU-OTHER-1');
    $response->assertDontSee('STU-PIVOT-1');
    $response->assertDontSee('STU-COL-1');
});

test('سجل الحضور يحفظ حضور الطلاب داخل النطاق فقط', function () {
    $this->actingAs($this->manager);

    $date = now()->format('Y-m-d');
    $this->post(route('admin.students.attendance.store'), [
        'date' => $date,
        'attendance' => [
            ['student_id' => $this->pivotStudent->id, 'status' => 'present'],
            ['student_id' => $this->columnStudent->id, 'status' => 'absent'],
        ],
    ])->assertRedirect(route('admin.students.attendance', ['date' => $date]));

    expect(Attendance::where('date', $date)->count())->toBe(2);
    expect(Attendance::where('student_id', $this->pivotStudent->id)->where('date', $date)->exists())->toBeTrue();
});

test('سجل الحضور يرفض طلب حفظ حضور لطالب خارج النطاق', function () {
    $this->actingAs($this->manager);

    $date = now()->format('Y-m-d');
    $this->post(route('admin.students.attendance.store'), [
        'date' => $date,
        'attendance' => [
            ['student_id' => $this->pivotStudent->id, 'status' => 'present'],
            ['student_id' => $this->otherProjectStudent->id, 'status' => 'present'],
        ],
    ])->assertForbidden();

    expect(Attendance::where('date', $date)->count())->toBe(0);
});

test('صفحة الطلاب تعرض الطلاب المرتبطين بالمشروع عبر الوسيط فقط ضمن النطاق', function () {
    $this->actingAs($this->manager);

    $response = $this->get(route('admin.students.index'));

    $response->assertOk();
    $response->assertSee('STU-PIVOT-1');
    $response->assertSee('STU-COL-1');
    $response->assertDontSee('STU-OTHER-1');
});

test('صفحة الطلاب تمنع تجاوز النطاق عبر باراميتر project_id في الرابط', function () {
    $this->actingAs($this->manager);

    $response = $this->get(route('admin.students.index', ['project_id' => $this->project2->id]));

    $response->assertOk();
    $response->assertDontSee('STU-OTHER-1');
});

test('المشرف العام (super-admin) يرى جميع الطلاب في سجل الحضور دون قيود', function () {
    $admin = User::factory()->create(['type' => 'super-admin']);
    $this->actingAs($admin);

    $response = $this->get(route('admin.students.attendance'));

    $response->assertOk();
    $response->assertSee('STU-PIVOT-1');
    $response->assertSee('STU-COL-1');
    $response->assertSee('STU-OTHER-1');
    $response->assertSee('STU-NONE-1');
});

test('scopeInProjects يطابق الطلاب عبر العمود أو جدول الوسيط معاً', function () {
    $ids = Student::query()->inProjects([$this->project1->id])->pluck('id')->sort()->values();

    expect($ids->toArray())->toEqual(collect([$this->pivotStudent->id, $this->columnStudent->id])->sort()->values()->toArray());
});

test('نطاق center_id يُطبَّق ولا يمكن تجاوزه في سجل الحضور', function () {
    $centerManager = User::factory()->create(['type' => 'user']);
    Permission::create([
        'user_id' => $centerManager->id,
        'model_names' => [Attendance::class],
        'center_id' => $this->center1->id,
        'can_view' => true,
        'can_create' => true,
    ]);
    $this->actingAs($centerManager);

    // بدون أي فلتر: يرى طلاب مركزه فقط
    $this->get(route('admin.students.attendance'))
        ->assertOk()
        ->assertSee('STU-PIVOT-1')
        ->assertSee('STU-COL-1')
        ->assertDontSee('STU-OTHER-1');

    // محاولة تجاوز عبر تمرير مركز أجنبي في الرابط → لا يُكشف أي طالب خارج النطاق
    $response = $this->get(route('admin.students.attendance', ['center_id' => $this->center2->id]));

    $response->assertOk();
    $response->assertDontSee('STU-OTHER-1');
    $response->assertDontSee('STU-NONE-1');
});