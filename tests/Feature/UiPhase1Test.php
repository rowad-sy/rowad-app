<?php

use App\Models\Admin\Center;
use App\Models\Admin\Logistics\PurchaseRequest;
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
        ->and($html)->toContain('مسح الفلاتر (2)');
});

test('sidebar sections are semantic toggle buttons and the sidebar is a labelled navigation', function () {
    $this->actingAs(uiEmployee([], 'super-admin'))->get('/admin')
        ->assertOk()
        ->assertSee('<nav class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">', false)
        ->assertSee('class="nav-section" aria-expanded="true"', false)
        ->assertSee('aria-controls="sidebar"', false);
});

function pmPageUser(array $extra = []): User
{
    return uiEmployee(array_merge(['page:admin.projects-manager.dashboard'], $extra));
}

function pmRequest(array $attrs = []): PurchaseRequest
{
    static $n = 0;
    $n++;

    return PurchaseRequest::create(array_merge([
        'request_number' => 'PR-TEST-'.$n,
        'specifications' => 'طلب اختبار '.$n,
        'status' => 'pm_approved',
        'quantity' => 1,
        'unit' => 'قطعة',
        'expected_unit_price' => 100,
        'expected_total_price' => 100,
    ], $attrs));
}

test('projects manager dashboard prioritises sign requests addressed to the user even with nothing else pending', function () {
    $me = pmPageUser();
    $other = uiEmployee();
    $creator = uiEmployee();

    $mine = pmRequest(['user_id' => $creator->id, 'refer_to_pm2_id' => $me->id, 'specifications' => 'مضخة مياه للمركز']);
    pmRequest(['user_id' => $creator->id, 'refer_to_pm2_id' => $other->id, 'specifications' => 'طلب موجّه لغيري']);
    pmRequest(['user_id' => $creator->id, 'refer_to_pm2_id' => $me->id, 'status' => 'priced', 'specifications' => 'حالة لا تستحق توقيعي']);

    $html = $this->actingAs($me)->get('/admin/projects-manager')->assertOk()->getContent();

    // قسم الأولويات فقط (قائمة «آخر طلبات الشراء» أدناه تعرض كل الطلبات ضمن نطاقها الحالي)
    $section = str($html)->after('id="attn-title"')->before('</section>')->toString();
    expect($html)->toContain('id="attn-title"')
        ->and($section)->toContain('طلبات شراء بانتظار توقيعك')
        ->and($section)->toContain('مضخة مياه للمركز')
        ->and($section)->not->toContain('طلب موجّه لغيري')
        ->and($section)->not->toContain('حالة لا تستحق توقيعي')
        ->and($html)->toContain(route('admin.logistics.purchase-requests.show', $mine))
        ->and($html)->toContain(route('admin.logistics.purchase-requests.index', ['status' => 'pm_approved']))
        // لا توجد قوائم مراجعة عامة، فلا يظهر عنوانها
        ->and($html)->not->toContain('قوائم قيد المراجعة (عامة)');
});

test('projects manager dashboard has no priorities section when nothing is due', function () {
    $me = pmPageUser();
    pmRequest(['user_id' => $me->id, 'refer_to_pm2_id' => uiEmployee()->id]);

    $this->actingAs($me)->get('/admin/projects-manager')
        ->assertOk()
        ->assertDontSee('id="attn-title"', false)
        ->assertDontSeeText('طلبات شراء بانتظار توقيعك');
});

test('projects manager dashboard is forbidden for a limited account', function () {
    $this->actingAs(uiEmployee())->get('/admin/projects-manager')->assertForbidden();
});

test('student form keeps input, links labels to fields and shows errors beside them after failed validation', function () {
    $admin = uiEmployee([], 'super-admin');

    $html = $this->actingAs($admin)->followingRedirects()->from('/admin/students/create')
        ->post('/admin/students', ['student_code' => 'S-KEEP-1', 'first_name_ar' => 'ليلى', 'last_name_ar' => '', 'gender' => 'female', 'status' => 'active', 'notes' => 'ملاحظة يجب أن تبقى'])
        ->getContent();

    expect($html)->toContain('value="S-KEEP-1"')
        ->and($html)->toContain('ملاحظة يجب أن تبقى')
        ->and($html)->toContain('<fieldset class="form-card mb-4">')
        ->and($html)->toContain('is-invalid')
        ->and($html)->toContain('invalid-feedback d-block');

    // كل label بـ for يشير إلى id موجود فعلًا
    preg_match_all('/<label[^>]*\sfor="([^"]+)"/u', $html, $labels);
    expect($labels[1])->not->toBeEmpty();
    foreach ($labels[1] as $id) {
        expect($html)->toContain('id="'.$id.'"');
    }
});
