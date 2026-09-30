<?php

// اختبارات تعمل فقط على قاعدة MySQL/MariaDB تجريبية معزولة (استعلامات DATE_FORMAT وMONTH() لا تعمل على sqlite).
// التشغيل: انظر docs/ui-phase-4/README.md. تُتخطّى تلقائيًا على sqlite، وتُرفض إن لم تكن القاعدة محلية واسمها *_test.
// تنبيه: RefreshDatabase يعيد بناء القاعدة المتصلة — لذلك يوجد حارس عزل قبل أي شيء.

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectTask;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use App\Models\User;

beforeEach(function () {
    if (config('database.default') !== 'mysql') {
        $this->markTestSkipped('يتطلب MySQL/MariaDB تجريبيًا معزولًا (DB_CONNECTION=mysql).');
    }
    $c = config('database.connections.mysql');
    if (! in_array($c['host'], ['127.0.0.1', 'localhost'], true) || ! str_ends_with((string) $c['database'], '_test')) {
        throw new RuntimeException('رُفض التشغيل: القاعدة ليست محلية بمعرّف *_test.');
    }
});

test('student statistics (DATE_FORMAT) counts enrollments per month and respects date filters, on MySQL', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $center = Center::create(['name' => 'مركز']);
    $project = Project::create(['name' => 'مشروع']);
    $course = Course::create(['project_id' => $project->id, 'name_ar' => 'دورة']);
    $period = Period::create(['project_id' => $project->id, 'name_ar' => 'فترة', 'year' => now()->year, 'start_date' => now()->subYear(), 'end_date' => now()->addYear()]);
    $mk = fn ($n, $g, $st) => Student::forceCreate(['student_code' => "S-$n", 'first_name_ar' => 'طالب', 'last_name_ar' => "رقم$n", 'gender' => $g, 'status' => $st, 'center_id' => $center->id]);
    $s = [$mk(1, 'male', 'active'), $mk(2, 'female', 'active'), $mk(3, 'male', 'graduated')];
    $enroll = fn ($st, $date) => StudentEnrollment::forceCreate(['student_id' => $st->id, 'course_id' => $course->id, 'period_id' => $period->id, 'enrollment_date' => $date]);
    $thisMonth = now()->startOfMonth()->addDays(1)->toDateString();
    $prevMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(2)->toDateString();
    $enroll($s[0], $thisMonth); $enroll($s[1], $thisMonth); $enroll($s[2], $prevMonth);

    $res = $this->actingAs($admin)->get(route('admin.students.statistics'))->assertOk();
    $res->assertViewHas('totalStudents', 3)->assertViewHas('activeStudents', 2)->assertViewHas('graduatedStudents', 1)
        ->assertViewHas('maleStudents', 2)->assertViewHas('femaleStudents', 1)->assertViewHas('totalEnrollments', 3);
    $data = $res->viewData('monthlyData');
    expect($data)->toHaveCount(12)->and($data[11])->toBe(2)->and($data[10])->toBe(1)->and(array_sum($data))->toBe(3);

    // فلتر الفترة: تسجيلات الشهر الحالي فقط
    $f = $this->actingAs($admin)->get(route('admin.students.statistics', ['date_from' => now()->startOfMonth()->toDateString()]))->assertOk();
    expect($f->viewData('totalEnrollments'))->toBe(2)->and($f->viewData('monthlyData')[10])->toBe(0)->and($f->viewData('monthlyData')[11])->toBe(2);
});

test('task statistics (MONTH()) groups by start month and the page renders real counts, on MySQL', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $mk = fn ($t, $start, $status) => ProjectTask::forceCreate(['title' => $t, 'start_date' => $start, 'end_date' => $start, 'assigned_to' => $admin->id,
        'created_by' => $admin->id, 'status' => $status]);
    $mk('م1', '2026-03-05', 'completed'); $mk('م2', '2026-03-20', 'pending'); $mk('م3', '2026-07-01', 'delayed'); $mk('م4', '2025-03-01', 'pending');

    $all = $this->actingAs($admin)->get(route('admin.projects.statistics'))->assertOk();
    $all->assertViewHas('total', 4)->assertViewHas('completed', 1)->assertViewHas('delayed', 1)->assertViewHas('pendingCount', 2);
    expect($all->viewData('byMonth')->all())->toBe([3 => 3, 7 => 1]);

    $y = $this->actingAs($admin)->get(route('admin.projects.statistics', ['year' => 2026]))->assertOk();
    expect($y->viewData('total'))->toBe(3)->and($y->viewData('byMonth')->all())->toBe([3 => 2, 7 => 1]);
});
