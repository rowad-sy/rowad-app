<?php

use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use App\Exports\Students\StudentExport;
use App\Imports\Students\StudentImport;
use App\Models\User;

beforeEach(function () {
    // Create a super-admin user (bypasses all permission checks)
    $this->admin = User::factory()->create(['type' => 'super-admin']);
    $this->actingAs($this->admin);

    // Create centers and projects
    $this->center1 = Center::create(['name' => 'مركز اختبار 1', 'address' => 'عنوان 1', 'phone' => '0111111111']);
    $this->center2 = Center::create(['name' => 'مركز اختبار 2', 'address' => 'عنوان 2', 'phone' => '0222222222']);
    $this->project1 = Project::create(['name' => 'مشروع اختبار 1', 'description' => 'وصف 1']);
    $this->project2 = Project::create(['name' => 'مشروع اختبار 2', 'description' => 'وصف 2']);

    // Link centers to projects (for center_project pivot)
    $this->center1->projects()->attach([$this->project1->id, $this->project2->id]);
    $this->center2->projects()->attach([$this->project1->id]);

    // Create courses
    $this->course1 = Course::create(['project_id' => $this->project1->id, 'name_ar' => 'مقرر اختبار 1', 'name_en' => 'Course 1']);
    $this->course2 = Course::create(['project_id' => $this->project2->id, 'name_ar' => 'مقرر اختبار 2', 'name_en' => 'Course 2']);

    // Create periods
    $this->period1 = Period::create([
        'project_id' => $this->project1->id,
        'name_ar' => 'فترة اختبار 1',
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'is_active' => true,
    ]);
    $this->period2 = Period::create([
        'project_id' => $this->project2->id,
        'name_ar' => 'فترة اختبار 2',
        'year' => 2026,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);
});

// ─── checkIdentity ───────────────────────────────────────────────────

test('checkIdentity returns found=false when identity_number does not exist', function () {
    $response = $this->postJson(route('admin.students.check-identity'), [
        'identity_number' => '999999999',
    ]);

    $response->assertJson([
        'found' => false,
        'redirect' => route('admin.students.create', ['identity_number' => '999999999']),
    ]);
});

test('checkIdentity returns can_edit=true when student exists in same center and project', function () {
    // Create an employee for the admin user with same center/project
    $employee = Employee::create([
        'user_id' => $this->admin->id,
        'employee_code' => 'EMP-TEST-1',
        'first_name_ar' => 'موظف',
        'last_name_ar' => 'اختبار',
        'gender' => 'male',
        'center_id' => $this->center1->id,
        'project_id' => $this->project1->id,
        'status' => 'active',
    ]);

    $student = Student::create([
        'student_code' => 'STU-CHECK-1',
        'first_name_ar' => 'طالب',
        'last_name_ar' => 'اختبار',
        'identity_number' => 'ID-12345',
        'identity_type' => 'national_id',
        'center_id' => $this->center1->id,
        'project_id' => $this->project1->id,
        'gender' => 'male',
        'status' => 'active',
    ]);
    $student->projects()->attach($this->project1->id);

    $response = $this->postJson(route('admin.students.check-identity'), [
        'identity_number' => 'ID-12345',
    ]);

    $response->assertJson([
        'found' => true,
        'can_edit' => true,
        'redirect' => route('admin.students.edit', $student),
    ]);
});

test('checkIdentity returns can_edit=false with available projects when different center/project', function () {
    // Employee at center1/project1
    Employee::create([
        'user_id' => $this->admin->id,
        'employee_code' => 'EMP-TEST-2',
        'first_name_ar' => 'موظف',
        'last_name_ar' => 'اختبار',
        'gender' => 'male',
        'center_id' => $this->center1->id,
        'project_id' => $this->project1->id,
        'status' => 'active',
    ]);

    // Student at center2/project1 (different center)
    $student = Student::create([
        'student_code' => 'STU-CHECK-2',
        'first_name_ar' => 'طالب',
        'last_name_ar' => 'آخر',
        'identity_number' => 'ID-67890',
        'identity_type' => 'national_id',
        'center_id' => $this->center2->id,
        'project_id' => $this->project1->id,
        'gender' => 'female',
        'status' => 'active',
    ]);
    $student->projects()->attach($this->project1->id);

    $response = $this->postJson(route('admin.students.check-identity'), [
        'identity_number' => 'ID-67890',
    ]);

    $response->assertJson([
        'found' => true,
        'can_edit' => false,
    ]);
    $responseData = $response->json();
    expect($responseData['student']['id'])->toBe($student->id);
    expect($responseData['student']['name'])->toContain('طالب');
    expect($responseData['student']['code'])->toBe('STU-CHECK-2');
    expect($responseData['available_projects'])->toBeArray();
});

// ─── addToProjects ───────────────────────────────────────────────────

test('addToProjects attaches student to additional projects', function () {
    $student = Student::create([
        'student_code' => 'STU-ADDPRJ-1',
        'first_name_ar' => 'طالب',
        'last_name_ar' => 'مشاريع',
        'gender' => 'male',
        'status' => 'active',
    ]);
    $student->projects()->attach($this->project1->id);

    expect($student->projects)->toHaveCount(1);

    $response = $this->post(route('admin.students.add-to-projects', $student), [
        'project_ids' => [$this->project2->id],
    ]);

    $response->assertRedirect(route('admin.students.show', $student));
    $response->assertSessionHas('success');

    $student->refresh();
    expect($student->projects)->toHaveCount(2);
});

// ─── Student CRUD with multi-project ─────────────────────────────────

test('store creates student with multiple projects', function () {
    $response = $this->post(route('admin.students.store'), [
        'student_code' => 'STU-NEW-1',
        'first_name_ar' => 'أحمد',
        'last_name_ar' => 'محمد',
        'gender' => 'male',
        'status' => 'active',
        'center_id' => $this->center1->id,
        'project_id' => $this->project1->id,
        'sync_project_ids' => '1',
        'project_ids' => [$this->project1->id, $this->project2->id],
    ]);

    $response->assertRedirect(route('admin.students.index'));
    $response->assertSessionHas('success');

    $student = Student::where('student_code', 'STU-NEW-1')->first();
    expect($student)->not->toBeNull();
    expect($student->projects)->toHaveCount(2);
});

test('store creates student with enrollments', function () {
    $response = $this->post(route('admin.students.store'), [
        'student_code' => 'STU-ENR-1',
        'first_name_ar' => 'خالد',
        'last_name_ar' => 'سعيد',
        'gender' => 'male',
        'status' => 'active',
        'sync_project_ids' => '1',
        'project_ids' => [$this->project1->id],
        'enrollments' => [
            [
                'course_id' => $this->course1->id,
                'period_id' => $this->period1->id,
                'enrollment_date' => '2026-01-15',
                'status' => 'enrolled',
                'grade' => null,
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.students.index'));

    $student = Student::where('student_code', 'STU-ENR-1')->first();
    expect($student)->not->toBeNull();
    expect($student->enrollments)->toHaveCount(1);
    expect($student->enrollments->first()->course_id)->toBe($this->course1->id);
    expect($student->enrollments->first()->period_id)->toBe($this->period1->id);
});

test('update can sync projects and add enrollments', function () {
    $student = Student::create([
        'student_code' => 'STU-UPD-1',
        'first_name_ar' => 'محمود',
        'last_name_ar' => 'علي',
        'gender' => 'male',
        'status' => 'active',
    ]);

    $response = $this->put(route('admin.students.update', $student), [
        'student_code' => 'STU-UPD-1',
        'first_name_ar' => 'محمود',
        'last_name_ar' => 'علي',
        'gender' => 'male',
        'status' => 'active',
        'sync_project_ids' => '1',
        'project_ids' => [$this->project1->id, $this->project2->id],
        'enrollments' => [
            [
                'course_id' => $this->course1->id,
                'period_id' => $this->period1->id,
                'enrollment_date' => '2026-03-01',
                'status' => 'enrolled',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.students.index'));

    $student->refresh();
    expect($student->projects)->toHaveCount(2);
    expect($student->enrollments)->toHaveCount(1);
});

test('update skips duplicate enrollments', function () {
    $student = Student::create([
        'student_code' => 'STU-DUP-1',
        'first_name_ar' => 'سامر',
        'last_name_ar' => 'حسن',
        'gender' => 'male',
        'status' => 'active',
    ]);

    // Add first enrollment
    StudentEnrollment::create([
        'student_id' => $student->id,
        'course_id' => $this->course1->id,
        'period_id' => $this->period1->id,
        'enrollment_date' => '2026-04-01',
    ]);

    expect($student->enrollments)->toHaveCount(1);

    // Try to add the same enrollment again
    $response = $this->put(route('admin.students.update', $student), [
        'student_code' => 'STU-DUP-1',
        'first_name_ar' => 'سامر',
        'last_name_ar' => 'حسن',
        'gender' => 'male',
        'status' => 'active',
        'enrollments' => [
            [
                'course_id' => $this->course1->id,
                'period_id' => $this->period1->id,
                'enrollment_date' => '2026-04-01',
                'status' => 'enrolled',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.students.index'));

    $student->refresh();
    expect($student->enrollments)->toHaveCount(1);
});

// ─── createUser ──────────────────────────────────────────────────────

test('createUser creates a student user account', function () {
    $student = Student::create([
        'student_code' => 'STU-USER-1',
        'first_name_ar' => 'مستخدم',
        'last_name_ar' => 'طالب',
        'gender' => 'male',
        'status' => 'active',
    ]);

    expect($student->user_id)->toBeNull();

    $response = $this->post(route('admin.students.create-user', $student));

    $response->assertRedirect(route('admin.students.show', $student));
    $response->assertSessionHas('success');

    $student->refresh();
    expect($student->user_id)->not->toBeNull();

    $user = User::find($student->user_id);
    expect($user)->not->toBeNull();
    expect($user->type)->toBe('student');
});

test('createUser fails if student already has a user', function () {
    $user = User::factory()->create(['type' => 'student']);
    $student = Student::create([
        'student_code' => 'STU-USER-2',
        'first_name_ar' => 'مستخدم',
        'last_name_ar' => 'موجود',
        'gender' => 'male',
        'status' => 'active',
        'user_id' => $user->id,
    ]);

    $response = $this->post(route('admin.students.create-user', $student));

    $response->assertRedirect(route('admin.students.show', $student));
    $response->assertSessionHas('error');
});

// ─── destroy ─────────────────────────────────────────────────────────

test('destroy soft-deletes a student', function () {
    $student = Student::create([
        'student_code' => 'STU-DEL-1',
        'first_name_ar' => 'محذوف',
        'last_name_ar' => 'طالب',
        'gender' => 'male',
        'status' => 'active',
    ]);

    $response = $this->delete(route('admin.students.destroy', $student));

    $response->assertRedirect(route('admin.students.index'));
    $response->assertSessionHas('success');

    expect(Student::find($student->id))->toBeNull();
    expect(Student::withTrashed()->find($student->id))->not->toBeNull();
});

// ─── show ────────────────────────────────────────────────────────────

test('show displays student profile', function () {
    $student = Student::create([
        'student_code' => 'STU-SHOW-1',
        'first_name_ar' => 'معروض',
        'last_name_ar' => 'طالب',
        'gender' => 'female',
        'status' => 'active',
        'center_id' => $this->center1->id,
        'project_id' => $this->project1->id,
    ]);
    $student->projects()->attach($this->project1->id);

    $response = $this->get(route('admin.students.show', $student));

    $response->assertOk();
    $response->assertSee($student->first_name_ar);
    $response->assertSee($student->last_name_ar);
});

// ─── index ───────────────────────────────────────────────────────────

test('index page shows students list', function () {
    Student::create([
        'student_code' => 'STU-INDEX-1',
        'first_name_ar' => 'مفهرس',
        'last_name_ar' => 'أول',
        'gender' => 'male',
        'status' => 'active',
    ]);
    Student::create([
        'student_code' => 'STU-INDEX-2',
        'first_name_ar' => 'مفهرس',
        'last_name_ar' => 'ثاني',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $response = $this->get(route('admin.students.index'));

    $response->assertOk();
    $response->assertSee('STU-INDEX-1');
    $response->assertSee('STU-INDEX-2');
});

// ─── Export ──────────────────────────────────────────────────────────

test('export downloads excel file with selected students', function () {
    Student::create([
        'student_code' => 'STU-EXP-1',
        'first_name_ar' => 'مصدر',
        'last_name_ar' => 'أول',
        'gender' => 'male',
        'status' => 'active',
    ]);
    Student::create([
        'student_code' => 'STU-EXP-2',
        'first_name_ar' => 'مصدر',
        'last_name_ar' => 'ثاني',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $exported = StudentExport::fromIds([1, 2]);
    $rows = $exported->collection();
    expect($rows)->toHaveCount(2);
    expect($exported->headings())->toContain('كود الطالب');
    expect($exported->headings())->toContain('المشاريع');
});

test('export all creates export with all students', function () {
    Student::create([
        'student_code' => 'STU-ALL-1',
        'first_name_ar' => 'الكل',
        'last_name_ar' => 'واحد',
        'gender' => 'male',
        'status' => 'active',
    ]);
    Student::create([
        'student_code' => 'STU-ALL-2',
        'first_name_ar' => 'الكل',
        'last_name_ar' => 'اثنان',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $exported = StudentExport::all();
    expect($exported->collection())->toHaveCount(2);
});

// ─── Import ──────────────────────────────────────────────────────────

test('import creates new student from row data', function () {
    $import = new StudentImport();

    $row = new \Illuminate\Support\Collection([
        'كود الطالب' => 'STU-IMP-1',
        'نوع الهوية' => 'بطاقة هوية',
        'رقم الهوية' => 'IMP-12345',
        'الاسم الأول AR' => 'مستورد',
        'الاسم الأخير AR' => 'جديد',
        'الجنس' => 'ذكر',
        'الحالة' => 'نشط',
        'المركز' => $this->center1->name,
        'المشاريع' => $this->project1->name,
    ]);

    $import->collection(collect([$row]));

    $student = Student::where('student_code', 'STU-IMP-1')->first();
    expect($student)->not->toBeNull();
    expect($student->first_name_ar)->toBe('مستورد');
    expect($student->identity_type)->toBe('national_id');
    expect($student->projects)->toHaveCount(1);
    expect($student->projects->first()->id)->toBe($this->project1->id);
});

test('import updates existing student with empty fields only', function () {
    $student = Student::create([
        'student_code' => 'STU-IMP-2',
        'first_name_ar' => 'قديم',
        'last_name_ar' => 'طالب',
        'gender' => 'male',
        'status' => 'active',
        'phone' => '0912345678',
    ]);

    $import = new StudentImport();
    $row = new \Illuminate\Support\Collection([
        'كود الطالب' => 'STU-IMP-2',
        'الاسم الأول AR' => 'مستورد',
        'الاسم الأخير AR' => 'محدث',
        'الجنس' => 'ذكر',
        'الحالة' => 'نشط',
        'الهاتف' => '', // empty → should NOT overwrite
    ]);

    $import->collection(collect([$row]));

    $student->refresh();
    expect($student->first_name_ar)->toBe('مستورد'); // was null? no, was 'قديم', but import overwrites non-empty values... wait, let me think
    // Actually the import sets: if (!is_null($value) && $value !== '') { $student->$key = $value; }
    // So 'مستورد' is non-null and non-empty, so it should overwrite
    expect($student->last_name_ar)->toBe('محدث');
    expect($student->phone)->toBe('0912345678'); // unchanged because '' is empty
});

test('index page filters by search', function () {
    Student::create([
        'student_code' => 'STU-SRCH-1',
        'first_name_ar' => 'مبحوث',
        'last_name_ar' => 'عنه',
        'gender' => 'male',
        'status' => 'active',
    ]);
    Student::create([
        'student_code' => 'STU-SRCH-2',
        'first_name_ar' => 'آخر',
        'last_name_ar' => 'واحد',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $response = $this->get(route('admin.students.index', ['search' => 'مبحوث']));

    $response->assertOk();
    $response->assertSee('STU-SRCH-1');
    $response->assertDontSee('STU-SRCH-2');
});
