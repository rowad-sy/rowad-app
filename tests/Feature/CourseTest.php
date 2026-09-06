<?php

use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\CourseOffering;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Subject;
use App\Models\Admin\Student\SubjectExam;
use App\Models\User;

const COURSE_MODEL = 'App\Models\Admin\Student\Course';

function courseUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'type' => 'employee',
        'must_change_password' => false,
    ], $attributes));
}

function coursePermission(User $user, array $flags = [], ?int $projectId = null): Permission
{
    return Permission::create(array_merge([
        'user_id' => $user->id,
        'model_names' => [COURSE_MODEL],
        'project_id' => $projectId,
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ], $flags));
}

function project(string $name): Project
{
    return Project::create(['name' => $name]);
}

function period(Project $project): Period
{
    return Period::create([
        'project_id' => $project->id,
        'name_ar' => 'الفترة الأولى',
        'year' => now()->year,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
    ]);
}

function courseFields(array $overrides = []): array
{
    return array_merge([
        'project_id' => null,
        'name_ar' => 'كوافيرة',
        'name_en' => 'Hairdressing',
        'description' => 'دورة مهنية متكاملة',
        'duration' => 45,
        'period_ids' => [],
        'levels' => [
            ['name_ar' => 'مستوى أول', 'type' => 'level', 'code' => 'L1', 'sort_order' => 0],
            ['name_ar' => 'مستوى ثانٍ', 'type' => 'level', 'code' => 'L2', 'sort_order' => 1],
        ],
        'subjects' => [
            [
                'name_ar' => 'أساسيات الكوافير',
                'name_en' => 'Basics',
                'hours' => 20,
                'weight' => 30,
                'exams' => [
                    ['name_ar' => 'امتحان قبلي', 'type' => 'pre', 'max_score' => 20],
                    ['name_ar' => 'امتحان نهائي', 'type' => 'final', 'max_score' => 100],
                ],
            ],
        ],
        'offerings' => [],
    ], $overrides);
}

test('guests are redirected to login on courses index', function () {
    $this->get('/admin/students/courses')->assertRedirect('/login');
});

test('users without view permission get 403 on courses index', function () {
    $user = courseUser();

    $this->actingAs($user)->get('/admin/students/courses')->assertStatus(403);
});

test('authorised user can view the courses index', function () {
    $user = courseUser();
    coursePermission($user);

    $this->actingAs($user)->get('/admin/students/courses')->assertOk()->assertSeeText('إدارة المقررات');
});

test('authorised user can store a course with levels, subjects, exams and offerings', function () {
    $project = project('مشروع أثر');
    $period = period($project);
    $user = courseUser();
    coursePermission($user, ['can_create' => true]);

    $payload = courseFields([
        'project_id' => $project->id,
        'period_ids' => [$period->id],
        'offerings' => [
            ['period_id' => $period->id, 'name_ar' => 'عرض صباحي', 'session_time' => '10:00'],
        ],
    ]);

    $this->actingAs($user)
        ->post('/admin/students/courses', $payload)
        ->assertRedirect(route('admin.students.courses.index'));

    $course = Course::first();

    expect($course)->not->toBeNull()
        ->and($course->name_ar)->toBe('كوافيرة')
        ->and($course->name_en)->toBe('Hairdressing')
        ->and($course->project_id)->toBe($project->id)
        ->and($course->duration)->toBe(45)
        ->and($course->periods()->count())->toBe(1)
        ->and($course->levels()->count())->toBe(2)
        ->and($course->subjects()->count())->toBe(1)
        ->and($course->offerings()->count())->toBe(1);

    $firstLevel = $course->levels()->orderBy('sort_order')->first();
    $subject = $course->subjects()->first();
    $offering = $course->offerings()->first();

    expect($firstLevel->project_id)->toBe($project->id)
        ->and($firstLevel->course_id)->toBe($course->id)
        ->and($firstLevel->name_ar)->toBe('مستوى أول')
        ->and($subject->exams()->count())->toBe(2)
        ->and((float) $subject->exams()->orderBy('sort_order')->first()->max_score)->toBe(20.0)
        ->and($subject->exams()->orderBy('sort_order')->get()->last()->name_ar)->toBe('امتحان نهائي')
        ->and($offering->period_id)->toBe($period->id)
        ->and($offering->name_ar)->toBe('عرض صباحي');
});

test('users without create permission cannot store a course (403)', function () {
    $project = project('مشروع أثر');
    $user = courseUser();
    coursePermission($user);

    $payload = courseFields(['project_id' => $project->id]);

    $this->actingAs($user)
        ->post('/admin/students/courses', $payload)
        ->assertStatus(403);

    expect(Course::count())->toBe(0)
        ->and(Subject::count())->toBe(0);
});

test('create form renders for user with create permission and is forbidden without it', function () {
    $creator = courseUser();
    coursePermission($creator, ['can_create' => true]);

    $this->actingAs($creator)
        ->get('/admin/students/courses/create')
        ->assertOk()
        ->assertSeeText('إضافة مقرر جديد');

    $viewer = courseUser();
    coursePermission($viewer);

    $this->actingAs($viewer)->get('/admin/students/courses/create')->assertStatus(403);
});

test('update renames a subject, updates its exam, deletes removed subjects/exams and ignores empty exam rows', function () {
    $project = project('مشروع أثر');
    $period = period($project);
    $creator = courseUser();
    coursePermission($creator, ['can_create' => true, 'can_edit' => true]);

    $this->actingAs($creator)->post('/admin/students/courses', courseFields([
        'project_id' => $project->id,
        'period_ids' => [$period->id],
        'subjects' => [
            [
                'name_ar' => 'رياضيات',
                'exams' => [
                    ['name_ar' => 'امتحان قبلي', 'type' => 'pre', 'max_score' => 20],
                    ['name_ar' => 'امتحان نهائي', 'type' => 'final', 'max_score' => 100],
                ],
            ],
            [
                'name_ar' => 'عربية',
                'exams' => [
                    ['name_ar' => 'امتحان قبلي', 'type' => 'pre', 'max_score' => 20],
                ],
            ],
        ],
        'levels' => [],
    ]));

    $course = Course::first();
    $subjectA = $course->subjects()->where('name_ar', 'رياضيات')->first();
    $examA1 = $subjectA->exams()->orderBy('sort_order')->first();

    $updatePayload = courseFields([
        'project_id' => $project->id,
        'period_ids' => [$period->id],
        'name_ar' => 'كوافيرة متقدمة',
        'levels' => [],
        'subjects' => [
            [
                'id' => $subjectA->id,
                'name_ar' => 'رياضيات متقدمة',
                'exams' => [
                    ['id' => $examA1->id, 'name_ar' => 'امتحان قبلي', 'type' => 'pre', 'max_score' => 25],
                    ['name_ar' => '', 'type' => 'final', 'max_score' => 100],
                ],
            ],
            [
                'name_ar' => 'علوم',
                'exams' => [],
            ],
        ],
        'offerings' => [],
    ]);

    $this->actingAs($creator)
        ->put("/admin/students/courses/{$course->id}", $updatePayload)
        ->assertRedirect(route('admin.students.courses.index'));

    $course->refresh();

    expect($course->name_ar)->toBe('كوافيرة متقدمة')
        ->and($course->subjects()->count())->toBe(2)
        ->and($course->subjects()->where('name_ar', 'رياضيات متقدمة')->exists())->toBeTrue()
        ->and($course->subjects()->where('name_ar', 'علوم')->exists())->toBeTrue()
        ->and($course->subjects()->where('name_ar', 'عربية')->exists())->toBeFalse();

    $subjectA->refresh();
    $updatedExam = $subjectA->exams()->first();

    expect($subjectA->name_ar)->toBe('رياضيات متقدمة')
        ->and($subjectA->exams()->count())->toBe(1)
        ->and($updatedExam->id)->toBe($examA1->id)
        ->and((float) $updatedExam->max_score)->toBe(25.0);
});

test('update is forbidden without edit permission (403)', function () {
    $project = project('مشروع أثر');
    $creator = courseUser();
    coursePermission($creator, ['can_create' => true]);
    $this->actingAs($creator)->post('/admin/students/courses', courseFields(['project_id' => $project->id, 'levels' => []]));

    $course = Course::first();
    $viewer = courseUser();
    coursePermission($viewer);

    $this->actingAs($viewer)
        ->put("/admin/students/courses/{$course->id}", courseFields(['project_id' => $project->id, 'levels' => []]))
        ->assertStatus(403);
});

test('destroy requires delete permission and cascades related records', function () {
    $project = project('مشروع أثر');
    $period = period($project);
    $creator = courseUser();
    coursePermission($creator, ['can_create' => true]);
    $this->actingAs($creator)->post('/admin/students/courses', courseFields([
        'project_id' => $project->id,
        'period_ids' => [$period->id],
        'offerings' => [
            ['period_id' => $period->id, 'name_ar' => 'عرض مسائي'],
        ],
    ]));

    $course = Course::first();
    expect(SubjectExam::count())->toBe(2);

    $admin = courseUser();
    coursePermission($admin, ['can_delete' => true]);

    $this->actingAs($admin)
        ->delete("/admin/students/courses/{$course->id}")
        ->assertRedirect(route('admin.students.courses.index'));

    expect(Course::count())->toBe(0)
        ->and(Subject::count())->toBe(0)
        ->and(SubjectExam::count())->toBe(0)
        ->and(CourseOffering::count())->toBe(0)
        ->and(AcademicLevel::where('course_id', $course->id)->count())->toBe(0);
});

test('help page renders for viewers and is forbidden without view permission', function () {
    $viewer = courseUser();
    coursePermission($viewer);

    $this->actingAs($viewer)
        ->get('/admin/students/courses/help')
        ->assertOk()
        ->assertSeeText('معلومات ونصائح')
        ->assertSeeText('إدارة المقررات');

    $outsider = courseUser();
    $this->actingAs($outsider)->get('/admin/students/courses/help')->assertStatus(403);
});

test('index restricts courses to the permitted project scope', function () {
    $projectA = project('مشروع أثر');
    $projectB = project('مشروع ثانٍ');
    Course::create(['project_id' => $projectA->id, 'name_ar' => 'مقرر تاسع']);
    Course::create(['project_id' => $projectB->id, 'name_ar' => 'دورة ICDL']);

    $user = courseUser();
    coursePermission($user, projectId: $projectA->id);

    $this->actingAs($user)
        ->get('/admin/students/courses')
        ->assertOk()
        ->assertSeeText('مقرر تاسع')
        ->assertDontSeeText('دورة ICDL');
});

test('project manager dashboard renders the courses shortcut for an authorised user', function () {
    $user = courseUser();
    Permission::create([
        'user_id' => $user->id,
        'model_names' => ['page:admin.project-manager.dashboard', COURSE_MODEL],
        'can_view' => true,
        'can_create' => true,
        'can_edit' => false,
        'can_delete' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin/project-manager')
        ->assertOk()
        ->assertSeeText('إدارة المقررات')
        ->assertSee('/admin/students/courses');
});

test('training plan create page pre-fills the course project and scopes levels and subjects by the course', function () {
    $project = project('مشروع أثر');
    $course = Course::create(['project_id' => $project->id, 'name_ar' => 'كوافيرة']);
    $courseLevel = $course->levels()->create(['project_id' => $project->id, 'name_ar' => 'مستوى أول', 'type' => 'level']);
    $courseSubject = $course->subjects()->create(['name_ar' => 'أساسيات الكوافير', 'sort_order' => 0]);

    $other = Course::create(['project_id' => $project->id, 'name_ar' => 'دورة مبيعات']);
    $otherSubject = $other->subjects()->create(['name_ar' => 'مهارات البيع', 'sort_order' => 0]);

    $user = courseUser();
    coursePermission($user, ['can_create' => true]);

    $response = $this->actingAs($user)
        ->get('/admin/students/training-plans/create?course_id=' . $course->id)
        ->assertOk()
        ->assertSeeText('تم تجهيز النموذج تلقائياً من المقرر')
        ->assertSeeText('كوافيرة')
        ->assertSee('const subjects = ' . json_encode([['id' => $courseSubject->id, 'name' => $courseSubject->name_ar]]), false)
        ->assertSee('const levels = ' . json_encode([['id' => $courseLevel->id, 'name' => $courseLevel->name_ar]]), false)
        ->assertDontSeeText($otherSubject->name_ar);

    $response->assertSee("value=\"{$project->id}\"", false)
        ->assertSee('selected', false);
});