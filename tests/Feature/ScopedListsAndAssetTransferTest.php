<?php

// اختبارات HTTP حقيقية عبر الـmiddleware الفعلي: قوائم التقنية ضمن النطاق، وتصدير/استيراد الأصول.
// المستخدمون هنا مرتبطون بسجلات Employee فعلية (مركز الموظف = مركز الصلاحية) حتى لا يخفي الاختبار أثر CheckPermission.

use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Logistics\Asset;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechEquipment;
use App\Models\Admin\Tech\TechIssue;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function slUser(array $flags, array $scope = [], ?Center $employeeCenter = null): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    if ($employeeCenter) {
        static $n = 0;
        Employee::forceCreate(['employee_code' => 'SL-'.(++$n).uniqid(), 'first_name_ar' => 'موظف', 'last_name_ar' => 'نطاق', 'gender' => 'male', 'status' => 'active',
            'children_count' => 0, 'user_id' => $u->id, 'center_id' => $employeeCenter->id]);
    }
    if ($flags !== null) {
        Permission::create(array_merge(['user_id' => $u->id, 'model_names' => [TechIssue::class, TechEquipment::class, Asset::class], 'can_view' => true, 'can_create' => false,
            'can_edit' => false, 'can_delete' => false], $flags, $scope));
    }

    return $u;
}

function slWorld(): object
{
    $w = new stdClass;
    $w->a = Center::create(['name' => 'مركز أ']);
    $w->b = Center::create(['name' => 'مركز ب']);
    $w->x = Project::create(['name' => 'مشروع س']);
    $w->y = Project::create(['name' => 'مشروع ص']);
    $rep = User::factory()->create(['type' => 'employee']);
    $mk = fn ($t, $c, $p, $st = 'open') => TechIssue::create(['reported_by' => $rep->id, 'title' => $t, 'description' => 'وصف '.$t, 'center_id' => $c->id, 'project_id' => $p->id, 'status' => $st, 'priority' => 'low']);
    $w->AX = $mk('تذكرة-AX', $w->a, $w->x);
    $w->AY = $mk('تذكرة-AY', $w->a, $w->y);
    $w->BX = $mk('تذكرة-BX', $w->b, $w->x);
    $w->BY = $mk('تذكرة-BY', $w->b, $w->y, 'completed');
    foreach (['AX' => [$w->a, $w->x], 'AY' => [$w->a, $w->y], 'BX' => [$w->b, $w->x], 'BY' => [$w->b, $w->y]] as $k => [$c, $p]) {
        $w->{'E'.$k} = TechEquipment::create(['name' => 'معدة-'.$k, 'type' => 'خادم', 'condition' => 'a', 'center_id' => $c->id, 'project_id' => $p->id]);
        $w->{'S'.$k} = Asset::create(['asset_code' => 'CODE-'.$k, 'name' => 'أصل-'.$k, 'type' => 'أجهزة', 'status' => 'in_use', 'center_id' => $c->id, 'project_id' => $p->id, 'notes' => 'ملاحظة-'.$k]);
    }

    return $w;
}

function slTitles($response, string $prefix): array
{
    preg_match_all('/'.$prefix.'-[A-Z]{2}/u', $response->getContent(), $m);

    return collect($m[0])->unique()->sort()->values()->all();
}

function slPairs(User $u, $w, string $model): void
{
    Permission::where('user_id', $u->id)->delete();
    foreach ([[$w->a, $w->x], [$w->b, $w->y]] as [$c, $p]) {
        Permission::create(['user_id' => $u->id, 'model_names' => [$model], 'center_id' => $c->id, 'project_id' => $p->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    }
}

// ───── قوائم التقنية ─────

test('ticket list: (A,X) and (B,Y) permissions never expose (A,Y) or (B,X), through search, filters, counters and pagination', function () {
    $w = slWorld();
    $u = slUser(['can_view' => true], [], null);
    slPairs($u, $w, TechIssue::class);

    $all = $this->actingAs($u)->get(route('admin.tech.issues.index'))->assertOk();
    expect(slTitles($all, 'تذكرة'))->toBe(['تذكرة-AX', 'تذكرة-BY']);
    $all->assertSee('إجمالي: 2', false);

    // البحث والفلاتر لا توسّع النطاق
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['search' => 'تذكرة']))->assertOk(), 'تذكرة'))->toBe(['تذكرة-AX', 'تذكرة-BY']);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['search' => 'AY']))->assertOk(), 'تذكرة'))->toBe([]);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['center_id' => $w->b->id, 'project_id' => $w->x->id]))->assertOk(), 'تذكرة'))->toBe([]);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['center_id' => $w->a->id]))->assertOk(), 'تذكرة'))->toBe(['تذكرة-AX']);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['status' => 'completed']))->assertOk(), 'تذكرة'))->toBe(['تذكرة-BY']);

    // الترقيم: صفحة صفحة لا تكشف غير المسموح
    $seen = [];
    foreach ([1, 2, 3] as $page) {
        $seen = array_merge($seen, slTitles($this->actingAs($u)->get(route('admin.tech.issues.index', ['per_page' => 1, 'page' => $page]))->assertOk(), 'تذكرة'));
    }
    expect(collect($seen)->unique()->sort()->values()->all())->toBe(['تذكرة-AX', 'تذكرة-BY']);
});

test('ticket list: a record-specific permission lists that record only; direct + group permissions are united; global and super-admin see all', function () {
    $w = slWorld();
    $one = slUser(['can_view' => true], ['model_id' => $w->BX->id]);
    expect(slTitles($this->actingAs($one)->get(route('admin.tech.issues.index'))->assertOk(), 'تذكرة'))->toBe(['تذكرة-BX']);

    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $g = Group::create(['name' => 'فريق']);
    $u->groups()->attach($g);
    Permission::create(['user_id' => $u->id, 'model_names' => [TechIssue::class], 'center_id' => $w->a->id, 'project_id' => $w->x->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    Permission::create(['group_id' => $g->id, 'model_names' => [TechIssue::class], 'center_id' => $w->b->id, 'project_id' => $w->y->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index'))->assertOk(), 'تذكرة'))->toBe(['تذكرة-AX', 'تذكرة-BY']);

    $global = slUser(['can_view' => true]);
    expect(slTitles($this->actingAs($global)->get(route('admin.tech.issues.index'))->assertOk(), 'تذكرة'))->toBe(['تذكرة-AX', 'تذكرة-AY', 'تذكرة-BX', 'تذكرة-BY']);
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    expect(slTitles($this->actingAs($super)->get(route('admin.tech.issues.index', ['search' => 'تذكرة']))->assertOk(), 'تذكرة'))->toHaveCount(4);
});

test('ticket list with an Employee-linked center-scoped account (real middleware) shows only its center', function () {
    $w = slWorld();
    $u = slUser(['can_view' => true], ['center_id' => $w->a->id], $w->a);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.issues.index'))->assertOk(), 'تذكرة'))->toBe(['تذكرة-AX', 'تذكرة-AY']);
});

test('equipment list: same pair semantics, record-specific and global access', function () {
    $w = slWorld();
    $u = slUser(['can_view' => true]);
    slPairs($u, $w, TechEquipment::class);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.equipment.index', ['search' => 'معدة']))->assertOk(), 'معدة'))->toBe(['معدة-AX', 'معدة-BY']);
    expect(slTitles($this->actingAs($u)->get(route('admin.tech.equipment.index', ['center_id' => $w->a->id, 'project_id' => $w->y->id]))->assertOk(), 'معدة'))->toBe([]);

    $one = slUser(['can_view' => true], ['model_id' => $w->EAY->id]);
    expect(slTitles($this->actingAs($one)->get(route('admin.tech.equipment.index'))->assertOk(), 'معدة'))->toBe(['معدة-AY']);
    $global = slUser(['can_view' => true]);
    expect(slTitles($this->actingAs($global)->get(route('admin.tech.equipment.index'))->assertOk(), 'معدة'))->toHaveCount(4);
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    expect(slTitles($this->actingAs($super)->get(route('admin.tech.equipment.index'))->assertOk(), 'معدة'))->toHaveCount(4);
});

// ───── تصدير الأصول ─────

function slExportRows($response): array
{
    $response->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();
    $rows = IOFactory::load($path)->getActiveSheet()->toArray();

    return $rows;
}

test('asset export: no view permission is rejected; scoped exports contain only allowed assets; format and values are kept', function () {
    $w = slWorld();
    $none = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $this->actingAs($none)->get(route('admin.logistics.export.assets'))->assertForbidden();
    $noView = slUser(['can_view' => false, 'can_create' => true]);
    $this->actingAs($noView)->get(route('admin.logistics.export.assets'))->assertForbidden();

    $expectedHeadings = ['الكود', 'الاسم', 'التصنيف', 'الحالة', 'المركز', 'المشروع', 'الغرفة', 'المستلم', 'القيمة', 'الملاحظات', 'تاريخ الإنشاء'];
    $cu = slUser(['can_view' => true], ['center_id' => $w->a->id], $w->a);
    $rows = slExportRows($this->actingAs($cu)->get(route('admin.logistics.export.assets')));
    expect($rows[0])->toBe($expectedHeadings)->and(collect(array_slice($rows, 1))->pluck(1)->sort()->values()->all())->toBe(['أصل-AX', 'أصل-AY']);
    $ax = collect($rows)->firstWhere(1, 'أصل-AX');
    expect($ax[3])->toBe('قيد الاستخدام')->and($ax[4])->toBe('مركز أ')->and($ax[5])->toBe('مشروع س')->and($ax[9])->toBe('ملاحظة-AX');

    $pu = slUser(['can_view' => true], ['project_id' => $w->y->id]);
    expect(collect(array_slice(slExportRows($this->actingAs($pu)->get(route('admin.logistics.export.assets'))), 1))->pluck(1)->sort()->values()->all())->toBe(['أصل-AY', 'أصل-BY']);

    $one = slUser(['can_view' => true], ['model_id' => $w->SBX->id]);
    expect(collect(array_slice(slExportRows($this->actingAs($one)->get(route('admin.logistics.export.assets'))), 1))->pluck(1)->all())->toBe(['أصل-BX']);

    $pairs = slUser(['can_view' => true]);
    slPairs($pairs, $w, Asset::class);
    expect(collect(array_slice(slExportRows($this->actingAs($pairs)->get(route('admin.logistics.export.assets'))), 1))->pluck(1)->sort()->values()->all())->toBe(['أصل-AX', 'أصل-BY']);

    $global = slUser(['can_view' => true]);
    expect(array_slice(slExportRows($this->actingAs($global)->get(route('admin.logistics.export.assets'))), 1))->toHaveCount(4);
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    expect(array_slice(slExportRows($this->actingAs($super)->get(route('admin.logistics.export.assets'))), 1))->toHaveCount(4);
});

// ───── استيراد الأصول ─────

function slXlsx(array $dataRows): UploadedFile
{
    $ss = new Spreadsheet;
    $ss->getActiveSheet()->fromArray(array_merge([['الكود', 'الاسم', 'القيمة', 'ملاحظات']], $dataRows));
    $f = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($ss))->save($f);

    return new UploadedFile($f, 'assets.xlsx', null, null, true);
}

test('asset import: no create permission is rejected on the server with no write', function () {
    $w = slWorld();
    $before = Asset::count();
    $file = fn () => slXlsx([['N1', 'أصل جديد', 5, 'ن']]);
    $none = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $ro = slUser(['can_view' => true]);
    foreach ([$none, $ro] as $u) {
        $this->actingAs($u)->post(route('admin.logistics.import.assets'), ['file' => $file()])->assertForbidden();
    }
    expect(Asset::count())->toBe($before)->and(AuditLog::where('event', 'imported')->count())->toBe(0);
});

test('asset import: a scoped creator is rejected before any write, with an Arabic message naming the row', function () {
    $w = slWorld();
    $before = Asset::count();
    $u = slUser(['can_create' => true], ['center_id' => $w->a->id], $w->a);
    $res = $this->actingAs($u)->from(route('admin.logistics.assets.index'))->post(route('admin.logistics.import.assets'), ['file' => slXlsx([['N1', 'أصل ١', 5, ''], ['N2', 'أصل ٢', 6, '']])]);
    $res->assertRedirect(route('admin.logistics.assets.index'));
    expect(session('error'))->toContain('الصف 2')->toContain('لم يُحفظ أي أصل')->and(Asset::count())->toBe($before)->and(AuditLog::where('event', 'imported')->count())->toBe(0);
});

test('asset import: an unscoped creator passes authorization; with the current file format the database cannot accept the rows, so nothing is written and no success event is logged (pre-existing limitation)', function () {
    $w = slWorld();
    $before = Asset::count();
    $u = slUser(['can_create' => true], [], $w->a);
    $this->actingAs($u)->from(route('admin.logistics.assets.index'))->post(route('admin.logistics.import.assets'), ['file' => slXlsx([['N1', 'أصل ١', 5, ''], ['N2', 'أصل ٢', 6, '']])])->assertRedirect();
    // ليس رفض صلاحية: الرفض يأتي من قاعدة البيانات (المركز/الكود/النوع مطلوبة وغير موجودة في تنسيق الملف الحالي) والتراجع كامل
    expect(session('error'))->toContain('بيانات الملف لا تكفي')->and(session('error'))->not->toContain('نطاق')
        ->and(Asset::count())->toBe($before)->and(AuditLog::where('event', 'imported')->count())->toBe(0);
});
