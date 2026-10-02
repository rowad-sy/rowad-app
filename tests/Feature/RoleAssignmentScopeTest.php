<?php

use App\Exports\Students\StudentExport;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Attendance;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Student;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

/*
 * اختبارات نظام الأدوار (Roles §17) + إصلاحات الثغرات W1-W4
 * ==========================================================
 * الدور (المجموعة) = قالب بلا نطاق، والعضوية (group_user) تحمل المركز/المشروع/الفوج.
 * تغطي: حصر الوصول بالسجل نفسه (IDOR/W2)، أولوية نطاق العضوية، التوافق الخلفي،
 * انعكاس تعريف الدور فوراً، حراسة super-admin (W3)، ضوابط المنح (W4)، حماية التصدير (W1).
 */

function roleStudent(string $code, ?int $projectId = null, ?int $centerId = null, array $pivots = []): Student
{
    $student = Student::create([
        'student_code' => $code,
        'first_name_ar' => 'طالب_' . $code,
        'last_name_ar' => 'اختبار_دور',
        'gender' => 'male',
        'status' => 'active',
        'project_id' => $projectId,
        'center_id' => $centerId,
    ]);

    foreach ($pivots as $p) {
        $student->projects()->attach($p);
    }

    return $student;
}

function roleUpdatePayload(Student $s): array
{
    return [
        'student_code' => $s->student_code,
        'first_name_ar' => $s->first_name_ar,
        'last_name_ar' => 'مُحدَّث',
        'gender' => 'male',
        'status' => 'active',
    ];
}

beforeEach(function () {
    $this->c1 = Center::create(['name' => 'مركز ١', 'address' => 'ع', 'phone' => '01']);
    $this->c2 = Center::create(['name' => 'مركز ٢', 'address' => 'ع', 'phone' => '02']);
    $this->p1 = Project::create(['name' => 'مشروع ١', 'description' => 'و']);
    $this->p2 = Project::create(['name' => 'مشروع ٢', 'description' => 'و']);

    // الدور: قالب نظيف — صلاحيات بلا أي نطاق
    $this->role = Group::create(['name' => 'مدير مشروع', 'description' => 'قالب دور']);
    Permission::create([
        'group_id' => $this->role->id,
        'model_names' => [Student::class],
        'can_view' => true,
        'can_edit' => true,
    ]);
    Permission::create([
        'group_id' => $this->role->id,
        'model_names' => [Attendance::class],
        'can_view' => true,
        'can_create' => true,
    ]);

    // عضويتان بنفس الدور لكن بنطاقين مختلفين
    $this->member1 = User::factory()->create(['type' => 'user']);
    $this->member1->groups()->attach($this->role->id, ['project_id' => $this->p1->id]);
    $this->member2 = User::factory()->create(['type' => 'user']);
    $this->member2->groups()->attach($this->role->id, ['project_id' => $this->p2->id]);

    $this->s1 = roleStudent('ROLE-S1', $this->p1->id, $this->c1->id, [$this->p1->id]);
    $this->s2 = roleStudent('ROLE-S2', $this->p2->id, $this->c2->id, [$this->p2->id]);

    $this->super = User::factory()->create(['type' => 'super-admin']);
});

/* ─────────── §17: النطاق في العضوية ─────────── */

test('دور واحد بنطاقين: كل عضو يرى طلاب مشروعه فقط', function () {
    $this->actingAs($this->member1)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S1')
        ->assertDontSee('ROLE-S2');

    $this->actingAs($this->member2)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S2')
        ->assertDontSee('ROLE-S1');
});

test('تعديل سجل خارج نطاق الدور مرفوض 403 — إغلاق IDOR (W2)', function () {
    $this->actingAs($this->member1)
        ->put(route('admin.students.update', $this->s2), roleUpdatePayload($this->s2))
        ->assertForbidden();

    expect(Student::find($this->s2->id)->last_name_ar)->toBe('اختبار_دور');
});

test('تعديل سجل داخل نطاق الدور يعمل', function () {
    $this->actingAs($this->member1)
        ->put(route('admin.students.update', $this->s1), roleUpdatePayload($this->s1))
        ->assertRedirect(route('admin.students.index'));

    expect(Student::find($this->s1->id)->last_name_ar)->toBe('مُحدَّث');
});

test('عرض وحذف الطالب خارج النطاق مرفوضان', function () {
    $this->actingAs($this->member1)
        ->get(route('admin.students.show', $this->s2))
        ->assertForbidden();

    $this->actingAs($this->member1)
        ->delete(route('admin.students.destroy', $this->s2))
        ->assertForbidden();

    expect(Student::find($this->s2->id))->not->toBeNull();
});

test('نطاق العضوية يتغلب على نطاق سجل المجموعة عند التعارض', function () {
    $legacyRole = Group::create(['name' => 'دور قديم']);
    Permission::create([
        'group_id' => $legacyRole->id,
        'model_names' => [Student::class],
        'project_id' => $this->p1->id, // السجل القديم مقيّد بمشروع ١
        'can_view' => true,
    ]);

    $member3 = User::factory()->create(['type' => 'user']);
    $member3->groups()->attach($legacyRole->id, ['project_id' => $this->p2->id]); // العضوية تقول مشروع ٢

    $this->actingAs($member3)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S2')
        ->assertDontSee('ROLE-S1');
});

test('سجل مجموعة قديم بنطاق — العضوية بلا نطاق تحترم السجل (توافق خلفي)', function () {
    $legacyRole = Group::create(['name' => 'دور خلفي']);
    Permission::create([
        'group_id' => $legacyRole->id,
        'model_names' => [Student::class],
        'project_id' => $this->p1->id,
        'can_view' => true,
    ]);

    $member4 = User::factory()->create(['type' => 'user']);
    $member4->groups()->attach($legacyRole->id); // بلا نطاق

    $this->actingAs($member4)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S1')
        ->assertDontSee('ROLE-S2');
});

test('تعريف الدور ينعكس على كل الأعضاء فوراً', function () {
    $this->actingAs($this->member1)
        ->get(route('admin.students.courses.index'))
        ->assertForbidden();

    Permission::create([
        'group_id' => $this->role->id,
        'model_names' => [Course::class],
        'can_view' => true,
    ]);

    $this->actingAs($this->member1)
        ->get(route('admin.students.courses.index'))
        ->assertOk();

    $this->actingAs($this->member2)
        ->get(route('admin.students.courses.index'))
        ->assertOk();
});

test('صلاحية الحضور من الدور محصورة بنطاق العضوية', function () {
    $date = now()->format('Y-m-d');

    $this->actingAs($this->member1)
        ->post(route('admin.students.attendance.store'), [
            'date' => $date,
            'attendance' => [['student_id' => $this->s2->id, 'status' => 'present']],
        ])
        ->assertForbidden();

    $this->actingAs($this->member1)
        ->post(route('admin.students.attendance.store'), [
            'date' => $date,
            'attendance' => [['student_id' => $this->s1->id, 'status' => 'present']],
        ])
        ->assertRedirect(route('admin.students.attendance', ['date' => $date]));
});

test('super-admin يرى كل شيء رغم أدوار النطاقات', function () {
    $this->actingAs($this->super)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S1')
        ->assertSee('ROLE-S2');
});

/* ─────────── واجهة الأدوار والنطاقات ─────────── */

test('شاشة الأدوار محمية بصلاحية المجموعة', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.roles.index'))->assertForbidden();

    $viewer = User::factory()->create();
    Permission::create(['user_id' => $viewer->id, 'model_names' => [Group::class], 'can_view' => true]);
    $this->actingAs($viewer)->get(route('admin.roles.index'))->assertOk();
});

test('منح دور بنطاق من الواجهة يسجل في العضوية ويحصر الرؤية', function () {
    $granter = User::factory()->create();
    Permission::create(['user_id' => $granter->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $newbie = User::factory()->create(['type' => 'user']);

    $this->actingAs($granter)
        ->post(route('admin.roles.store'), [
            'group_id' => $this->role->id,
            'user_id' => $newbie->id,
            'center_id' => $this->c1->id,
            'project_id' => '',
            'cohort_id' => '',
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseHas('group_user', [
        'group_id' => $this->role->id,
        'user_id' => $newbie->id,
        'center_id' => $this->c1->id,
    ]);

    $this->actingAs($newbie)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S1')
        ->assertDontSee('ROLE-S2');
});

test('تعديل نطاق إسناد قائم عبر الشاشة', function () {
    $granter = User::factory()->create();
    Permission::create(['user_id' => $granter->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $this->actingAs($granter)
        ->put(route('admin.roles.update', [$this->role, $this->member1]), [
            'center_id' => '',
            'project_id' => $this->p2->id,
            'cohort_id' => '',
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseHas('group_user', [
        'group_id' => $this->role->id,
        'user_id' => $this->member1->id,
        'project_id' => $this->p2->id,
    ]);

    $this->actingAs($this->member1)
        ->get(route('admin.students.index'))
        ->assertSee('ROLE-S2');
});

test('سحب الدور يزيل العضوية ويحجب الرؤية', function () {
    $granter = User::factory()->create();
    Permission::create(['user_id' => $granter->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $this->actingAs($granter)
        ->delete(route('admin.roles.destroy', [$this->role, $this->member1]))
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $this->role->id,
        'user_id' => $this->member1->id,
    ]);

    $this->actingAs($this->member1)
        ->get(route('admin.students.index'))
        ->assertForbidden();
});

test('لا يستطيع غير السوبر-أدن منح دور لنفسه', function () {
    $selfActor = User::factory()->create();
    Permission::create(['user_id' => $selfActor->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $this->actingAs($selfActor)
        ->post(route('admin.roles.store'), [
            'group_id' => $this->role->id,
            'user_id' => $selfActor->id,
        ])
        ->assertForbidden();
});

test('لا يمكن منح دور لحساب super-admin', function () {
    $granter = User::factory()->create();
    Permission::create(['user_id' => $granter->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $this->actingAs($granter)
        ->post(route('admin.roles.store'), [
            'group_id' => $this->role->id,
            'user_id' => $this->super->id,
        ])
        ->assertForbidden();
});

/* ─────────── W3: قفل super-admin ─────────── */

test('من يملك تعديل المستخدمين لا يستطيع إنشاء أو ترقية سوبر-أدن', function () {
    $actor = User::factory()->create();
    Permission::create(['user_id' => $actor->id, 'model_names' => [User::class], 'can_create' => true, 'can_edit' => true]);

    $this->actingAs($actor)
        ->post(route('admin.users.store'), [
            'name' => 'مزيف', 'email' => 'fake@x.com', 'password' => 'password123',
            'password_confirmation' => 'password123', 'type' => 'super-admin',
        ])
        ->assertForbidden();

    $this->actingAs($actor)
        ->put(route('admin.users.update', $this->member1), [
            'name' => $this->member1->name, 'email' => $this->member1->email, 'type' => 'super-admin',
        ])
        ->assertForbidden();
});

test('لا يستطيع غير السوبر-أدن لمس حساب super-admin', function () {
    $actor = User::factory()->create();
    Permission::create(['user_id' => $actor->id, 'model_names' => [User::class], 'can_edit' => true]);

    $this->actingAs($actor)
        ->put(route('admin.users.update', $this->super), [
            'name' => 'اختراق', 'email' => $this->super->email,
        ])
        ->assertForbidden();

    $this->actingAs($actor)
        ->post(route('admin.users.toggle-status', $this->super))
        ->assertForbidden();
});

/* ─────────── W4: ضوابط المنح ─────────── */

test('لا يستطيع غير السوبر-أدن منح صلاحيات لنفسه', function () {
    $actor = User::factory()->create();
    Permission::create(['user_id' => $actor->id, 'model_names' => [Permission::class], 'can_create' => true]);

    $this->actingAs($actor)
        ->post(route('admin.permissions.store'), [
            'rows' => [0 => ['assign_to' => 'user', 'user_id' => $actor->id, 'group_id' => '']],
            'perms' => [0 => [0 => ['can_view' => '1', 'can_create' => '', 'can_edit' => '1', 'can_delete' => '']]],
        ])
        ->assertForbidden();
});

test('لا يستطيع غير السوبر-أدن منح موديلات الإدارة العليا أو منح حساب سوبر-أدن', function () {
    $actor = User::factory()->create();
    Permission::create(['user_id' => $actor->id, 'model_names' => [Permission::class], 'can_create' => true]);

    $other = User::factory()->create(['type' => 'user']);

    // مفتاح User في الكتالوج = 4
    $this->actingAs($actor)
        ->post(route('admin.permissions.store'), [
            'rows' => [0 => ['assign_to' => 'user', 'user_id' => $other->id, 'group_id' => '']],
            'perms' => [0 => [4 => ['can_view' => '1', 'can_create' => '', 'can_edit' => '', 'can_delete' => '']]],
        ])
        ->assertForbidden();

    $this->actingAs($actor)
        ->post(route('admin.permissions.store'), [
            'rows' => [0 => ['assign_to' => 'user', 'user_id' => $this->super->id, 'group_id' => '']],
            'perms' => [0 => [0 => ['can_view' => '1', 'can_create' => '', 'can_edit' => '', 'can_delete' => '']]],
        ])
        ->assertForbidden();

    // الممنوح العادي يمر
    $this->actingAs($actor)
        ->post(route('admin.permissions.store'), [
            'rows' => [0 => ['assign_to' => 'user', 'user_id' => $other->id, 'group_id' => '']],
            'perms' => [0 => [0 => ['can_view' => '1', 'can_create' => '', 'can_edit' => '', 'can_delete' => '']]],
        ])
        ->assertRedirect(route('admin.permissions.index'));
});

test('لا يمكن لغير السوبر-أدن إضافة نفسه لمجموعة', function () {
    $actor = User::factory()->create();
    Permission::create(['user_id' => $actor->id, 'model_names' => [Group::class], 'can_edit' => true]);

    $this->actingAs($actor)
        ->put(route('admin.groups.update', $this->role), [
            'name' => $this->role->name,
            'users' => [$actor->id],
        ])
        ->assertForbidden();

    // العضوية الحالية (member1) لم تفقد نطاقها رغم عدم ذكرها — منطق diff لا sync
    $fresh = $this->role->users()->where('users.id', $this->member1->id)->first();
    expect($fresh)->not->toBeNull();
    expect($fresh->pivot->project_id)->toBe($this->p1->id);
});

/* ─────────── W1: حماية التصدير ─────────── */

test('التصدير محجوب عمن لا يملك صلاحية العرض', function () {
    $nobody = User::factory()->create();

    $this->actingAs($nobody)
        ->post(route('admin.hr.employees.export'))
        ->assertForbidden();

    $this->actingAs($nobody)
        ->post(route('admin.students.export'))
        ->assertForbidden();

    $this->actingAs($nobody)
        ->get(route('admin.logistics.export.assets'))
        ->assertForbidden();
});

test('صاحب الصلاحية والسوبر-أدن يستطيعان التصدير', function () {
    Excel::fake();

    $this->actingAs($this->super)
        ->post(route('admin.students.export'))
        ->assertOk();

    Excel::assertDownloaded('students.xlsx', function (StudentExport $export) {
        return $export->collection()->pluck('student_code')->contains('ROLE-S1')
            && $export->collection()->pluck('student_code')->contains('ROLE-S2');
    });

    $hr = User::factory()->create();
    Permission::create(['user_id' => $hr->id, 'model_names' => [Student::class], 'can_view' => true]);

    $this->actingAs($hr)
        ->post(route('admin.students.export'))
        ->assertOk();
});

test('تصدير الطالب المحصور النطاق لا يتجاوز حدوده عبر ids', function () {
    Excel::fake();

    $this->actingAs($this->member1)
        ->post(route('admin.students.export'), ['ids' => [$this->s2->id]])
        ->assertOk();

    // طلب تصدير طالب خارج النطاق ⇒ يتقاطع الطلب مع المسموح فيصير فارغاً: لا تسريب
    Excel::assertDownloaded('students.xlsx', function (StudentExport $export) {
        return $export->collection()->isEmpty();
    });
});
