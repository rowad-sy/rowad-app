<?php

use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

function p2User(array $models = [], string $type = 'employee', array $flags = []): User
{
    $user = User::factory()->create(['type' => $type, 'must_change_password' => false]);

    if ($models) {
        Permission::create(array_merge([
            'user_id' => $user->id,
            'model_names' => $models,
            'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ], $flags));
    }

    return $user;
}

function p2Employee(int $n = 1): Employee
{
    return Employee::forceCreate([
        'employee_code' => sprintf('T-%03d', $n), 'first_name_ar' => 'موظف', 'last_name_ar' => 'رقم'.$n,
        'gender' => 'male', 'status' => 'active', 'children_count' => 0,
    ]);
}

test('validation messages are Arabic on admin pages only and the app locale is untouched', function () {
    $admin = p2User([], 'super-admin');

    $this->actingAs($admin)->from('/admin/students/create')->post('/admin/students', ['status' => 'active'])
        ->assertSessionHasErrors(['student_code', 'first_name_ar', 'last_name_ar', 'gender']);

    $errors = session('errors')->getBag('default');
    expect($errors->first('last_name_ar'))->toBe('حقل اللقب بالعربية مطلوب.')
        ->and($errors->first('student_code'))->toBe('حقل كود الطالب مطلوب.');

    // خارج /admin: تبقى الرسائل الافتراضية (الإنجليزية) كما هي
    app()->instance('request', Request::create('/login'));
    $outside = Validator::make([], ['name' => 'required']);
    expect($outside->errors()->first('name'))->toBe('The name field is required.')
        ->and(app()->getLocale())->toBe('en');
});

test('student form keeps dynamic enrollment rows and shows per-row errors after failed validation', function () {
    $admin = p2User([], 'super-admin');
    $project = Project::create(['name' => 'مشروع']);
    $course = Course::create(['project_id' => $project->id, 'name_ar' => 'مقرر اختبار']);
    Period::create(['project_id' => $project->id, 'name_ar' => 'الفصل', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

    $html = $this->actingAs($admin)->followingRedirects()->from('/admin/students/create')->post('/admin/students', [
        'student_code' => 'S-ROWS', 'first_name_ar' => 'ليلى', 'last_name_ar' => '', 'gender' => 'female', 'status' => 'active',
        'enrollments' => [3 => ['course_id' => $course->id, 'period_id' => '', 'grade' => '150']],
    ])->getContent();

    expect($html)->toContain('name="enrollments[3][course_id]"')
        ->and($html)->toMatch('/value="'.$course->id.'"[^>]*selected/')            // الاختيار محفوظ
        ->and($html)->toContain('حقل الفترة في التسجيل رقم 4 مطلوب.')             // خطأ الصف بمفتاحه الأصلي
        ->and($html)->toContain('err-enr-3-period_id')
        ->and($html)->toContain('aria-invalid="true"')
        ->and($html)->toContain('حقل اللقب بالعربية مطلوب.')
        ->and($html)->toContain('value="150"')                                       // القيم المُدخلة تبقى
        ->and($html)->toContain('enrollIdx = 4;');                                   // الصف الجديد لا يصطدم بالمفتاح 3
});

test('student name fields have independent labels and errors beside them', function () {
    $html = $this->actingAs(p2User([], 'super-admin'))->get('/admin/students/create')->assertOk()->getContent();

    foreach (['first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en'] as $f) {
        expect($html)->toContain('for="f-'.$f.'"')->and($html)->toContain('id="f-'.$f.'"');
    }
});

test('list filters apply only with the apply button, keep query in pagination, and distinguish empty from no-match', function () {
    $admin = p2User([], 'super-admin');
    foreach (range(1, 12) as $i) {
        p2Employee($i);
    }

    $html = $this->actingAs($admin)->get('/admin/hr/employees?search=T-0&per_page=10')->assertOk()->getContent();
    $bar = str($html)->after('<form method="GET" class="filter-bar"')->before('</form>')->toString();

    expect($bar)->not->toContain('this.form.submit()')             // لا إرسال تلقائي مع زر التطبيق
        ->and($bar)->toContain('تطبيق')
        ->and($bar)->toContain('مسح الفلاتر')
        ->and($html)->toContain('search=T-0')                       // الترقيم يحتفظ بالفلتر
        ->and($html)->toContain('page=2');

    $this->get('/admin/hr/employees?search=zzz-none')->assertOk()->assertSeeText('لا توجد نتائج تطابق الفلاتر');
    Employee::query()->delete();
    $this->get('/admin/hr/employees')->assertOk()->assertSeeText('لا يوجد موظفون بعد')->assertDontSeeText('لا توجد نتائج تطابق الفلاتر');
});

test('employee statistics page is reachable and no longer shadowed by the employee resource route', function () {
    $user = p2User(['App\Models\Admin\Hr\Employee']);
    p2Employee();

    $this->actingAs($user)->get('/admin/hr/employees/statistics')->assertOk();
});

test('leave requests page links to the personal-review page only for users who can act on approvals', function () {
    $viewer = p2User(['App\Models\Admin\Hr\LeaveRequest']);
    $approver = p2User(['App\Models\Admin\Hr\LeaveRequest'], 'employee', ['can_edit' => true]);
    $link = route('admin.hr.leave-approvals.index');

    $this->actingAs($viewer)->get('/admin/hr/leave-requests')->assertOk()->assertDontSee($link, false);
    $this->actingAs($approver)->get('/admin/hr/leave-requests')->assertOk()->assertSee($link, false)->assertSeeText('الطلبات المستحقة لمراجعتي');
    $this->actingAs($approver)->get('/admin/hr/leave-approvals')->assertOk()->assertSeeText('طلبات الإجازات المستحقة لمراجعتك');
});

test('permission matrix checkboxes have unique ids matched by their labels', function () {
    $admin = p2User([], 'super-admin');
    $target = p2User(['App\Models\Admin\Center']);
    $perm = Permission::where('user_id', $target->id)->first();

    $html = $this->actingAs($admin)->get(route('admin.permissions.edit', $perm))->assertOk()->getContent();

    preg_match_all('/<input type="checkbox"[^>]*\sid="(perm_[^"]+)"/', $html, $ids);
    expect($ids[1])->not->toBeEmpty()->and(count($ids[1]))->toBe(count(array_unique($ids[1])));
    foreach (array_slice($ids[1], 0, 20) as $id) {
        expect($html)->toContain('<label for="'.$id.'">');
    }
});

test('cohorts and centers lists render distinct empty and filtered states', function () {
    $admin = p2User([], 'super-admin');
    Center::create(['name' => 'مركز وحيد']);

    $this->actingAs($admin)->get('/admin/centers?search=غير-موجود')->assertOk()->assertSeeText('لا توجد نتائج تطابق الفلاتر');
    $this->get('/admin/cohorts')->assertOk()->assertSeeText('لا توجد أفواج بعد');
});
