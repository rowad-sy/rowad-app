<?php

use App\Models\Admin\Center;
use App\Models\Admin\Logistics\DeletedItem;
use App\Models\Admin\Logistics\Warehouse;
use App\Models\Admin\Logistics\WarehouseItem;
use App\Models\Admin\Permission;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectActivity;
use App\Models\Admin\Tech\TechIssue;
use App\Models\User;

const P3_MODELS = [
    'App\Models\Admin\Project', 'App\Models\Admin\ProjectActivity', 'App\Models\Admin\Logistics\Warehouse',
    'App\Models\Admin\Logistics\WarehouseItem', 'App\Models\Admin\Tech\TechIssue', 'App\Models\Admin\Physiotherapy\PhysioPatient',
    'page:admin.physiotherapy.followups.index', 'page:admin.physiotherapy.statistics.index',
];

function p3User(array $scope = [], array $flags = []): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge(['user_id' => $u->id, 'model_names' => P3_MODELS, 'can_view' => true, 'can_create' => true,
        'can_edit' => true, 'can_delete' => true], $flags, $scope));

    return $u;
}

// صفوف النماذج الديناميكية تُمرَّر إلى JS عبر @json (يهرّب العربية إلى \uXXXX)
function jsonText(string $t): string
{
    return substr(json_encode($t), 1, -1);
}

function p3Centers(): array
{
    return [Center::create(['name' => 'مركز أ']), Center::create(['name' => 'مركز ب'])];
}

test('project activity edit page opens (route parameter is bound as {activity})', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $project = Project::create(['name' => 'مشروع']);
    $activity = ProjectActivity::create(['project_id' => $project->id, 'responsible' => 'منسق', 'activity_date' => now()->toDateString(),
        'male_count' => 1, 'female_count' => 2, 'created_by' => $admin->id]);

    $this->actingAs($admin)->get(route('admin.project-activities.edit', $activity))->assertOk()->assertSee('منسق');
});

test('warehouse items: list, create with failed validation keeps input, delete moves item to a warehouse-scoped deleted page', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $wh = Warehouse::create(['name' => 'مخزن 1', 'center_id' => p3Centers()[0]->id]);
    $other = Warehouse::create(['name' => 'مخزن 2', 'center_id' => Center::first()->id]);

    $this->actingAs($admin)->get(route('admin.logistics.warehouses.index'))->assertOk()->assertSee('مخزن 1');

    // فشل تحقق حقيقي: تبقى القيم المُدخلة
    $this->actingAs($admin)->from(route('admin.logistics.warehouses.items.create', $wh))
        ->post(route('admin.logistics.warehouses.items.store', $wh), ['name' => 'ورق A4', 'quantity' => '12'])
        ->assertSessionHasErrors('unit');
    $this->actingAs($admin)->get(route('admin.logistics.warehouses.items.create', $wh))->assertOk();

    $this->actingAs($admin)->post(route('admin.logistics.warehouses.items.store', $wh), ['name' => 'ورق A4', 'quantity' => 12, 'unit' => 'رزمة'])->assertRedirect();
    $item = WarehouseItem::where('name', 'ورق A4')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.logistics.warehouses.items.index', $wh))->assertOk()->assertSee('ورق A4')->assertSee('رزمة');

    // الحذف يتطلب سببًا ثم يظهر في صفحة المحذوفات الخاصة بذلك المخزن فقط
    $this->actingAs($admin)->post(route('admin.logistics.warehouses.items.destroy', [$wh, $item]), [])->assertSessionHasErrors('delete_reason');
    $this->actingAs($admin)->post(route('admin.logistics.warehouses.items.destroy', [$wh, $item]), ['delete_reason' => 'تالفة'])->assertRedirect();
    expect(DeletedItem::where('warehouse_id', $wh->id)->count())->toBe(1);

    $this->actingAs($admin)->get(route('admin.logistics.warehouses.items.deleted', $wh))->assertOk()->assertSee('ورق A4')->assertSee('تالفة');
    $this->actingAs($admin)->get(route('admin.logistics.warehouses.items.deleted', $other))->assertOk()->assertDontSee('ورق A4')->assertSee('لا توجد مواد محذوفة');
});

test('tech issues: a center-scoped user sees only their center data and filters show distinct empty states', function () {
    [$a, $b] = p3Centers();
    $reporter = User::factory()->create();
    $mk = fn ($t, $c) => TechIssue::create(['reported_by' => $reporter->id, 'title' => $t, 'description' => 'وصف', 'center_id' => $c->id, 'status' => 'open', 'priority' => 'high']);
    $mk('تذكرة خاصة بمركز أ', $a);
    $mk('تذكرة خاصة بمركز ب', $b);
    $scoped = p3User(['center_id' => $a->id]);
    $full = p3User();

    $this->actingAs($scoped)->get(route('admin.tech.issues.index'))->assertOk()
        ->assertSee('تذكرة خاصة بمركز أ')->assertDontSee('تذكرة خاصة بمركز ب');
    $this->actingAs($full)->get(route('admin.tech.issues.index'))->assertOk()
        ->assertSee('تذكرة خاصة بمركز أ')->assertSee('تذكرة خاصة بمركز ب');

    $this->actingAs($full)->get(route('admin.tech.issues.index', ['search' => 'لا-شيء-مطابق']))->assertOk()->assertSee('لا توجد نتائج تطابق الفلاتر');
    TechIssue::query()->delete();
    $this->actingAs($full)->get(route('admin.tech.issues.index'))->assertOk()->assertDontSee('لا توجد نتائج تطابق الفلاتر');
});

test('tech issue create keeps entered values after a real validation failure', function () {
    $full = p3User();
    $this->actingAs($full)->from(route('admin.tech.issues.create'))
        ->post(route('admin.tech.issues.store'), ['title' => 'عنوان محفوظ', 'priority' => 'high'])
        ->assertSessionHasErrors('description');
    $this->actingAs($full)->withSession(['_old_input' => ['title' => 'عنوان محفوظ', 'priority' => 'high']])
        ->get(route('admin.tech.issues.create'))->assertOk()->assertSee('عنوان محفوظ');
});

test('physiotherapy patients: a center-scoped user cannot see or open patients of other centers', function () {
    [$a, $b] = p3Centers();
    $mk = fn ($n, $c) => PhysioPatient::create(['name' => $n, 'center_id' => $c->id, 'gender' => 'male', 'registration_date' => now()->toDateString()]);
    $mine = $mk('مريض من مركز أ', $a);
    $theirs = $mk('مريض من مركز ب', $b);
    $scoped = p3User(['center_id' => $a->id]);

    $this->actingAs($scoped)->get(route('admin.physiotherapy.patients.index'))->assertOk()
        ->assertSee('مريض من مركز أ')->assertDontSee('مريض من مركز ب');
    $this->actingAs($scoped)->get(route('admin.physiotherapy.patients.show', $mine))->assertOk();
    $this->actingAs($scoped)->get(route('admin.physiotherapy.patients.show', $theirs))->assertStatus(403);
    $this->actingAs($scoped)->get(route('admin.physiotherapy.followups.index'))->assertOk()->assertDontSee('مريض من مركز ب');
});

test('phase 3 list pages render for a full-permission user with the shared header and no legacy header markup', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $wh = Warehouse::create(['name' => 'مخزن', 'center_id' => p3Centers()[0]->id]);
    foreach ([
        route('admin.projects.index'), route('admin.projects.tasks.index'), route('admin.event-cards.index'), route('admin.media-plans.index'),
        route('admin.movement-plans.index'), route('admin.logistics.purchase-requests.index'), route('admin.logistics.warehouses.index'),
        route('admin.logistics.warehouses.items.index', $wh), route('admin.tech.issues.index'), route('admin.tech.equipment.index'),
        route('admin.physiotherapy.patients.index'), route('admin.physiotherapy.followups.index'), route('admin.physiotherapy.statistics.index'),
    ] as $url) {
        $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
        expect(str_contains($html, 'class="page-title"'))->toBeTrue("no shared header on $url")
            ->and(preg_match('/class="page-header[" ]/', $html) === 1)->toBeFalse("legacy header on $url");
    }
});

test('event card form restores entered rows after a failed save, including when every row was deleted', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $card = App\Models\Admin\EventCard::create(['name' => 'بطاقة', 'created_by' => $admin->id, 'status' => 'draft',
        'content_items' => [['item' => 'فقرة محفوظة في السجل', 'content' => 'x', 'responsible' => 'y', 'duration' => '5']]]);

    // أول فتح: صفوف السجل
    $this->actingAs($admin)->get(route('admin.event-cards.edit', $card))->assertOk()->assertSee(jsonText('فقرة محفوظة في السجل'), false);

    // بعد محاولة فاشلة وقد حُذفت كل الصفوف: لا تعود صفوف السجل المحذوفة
    $this->actingAs($admin)->withSession(['_old_input' => ['name' => 'اسم بعد الفشل']])
        ->get(route('admin.event-cards.edit', $card))->assertOk()->assertDontSee(jsonText('فقرة محفوظة في السجل'), false);

    // صف مُدخَل يُستعاد
    $this->actingAs($admin)->withSession(['_old_input' => ['name' => 'س', 'content_items' => [5 => ['item' => 'فقرة جديدة مُدخلة', 'content' => '', 'responsible' => '', 'duration' => '']]]])
        ->get(route('admin.event-cards.edit', $card))->assertOk()->assertSee(jsonText('فقرة جديدة مُدخلة'), false);
});

test('purchase request form restores entered items after a failed save', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($admin)->withSession(['_old_input' => ['items' => [3 => ['description' => 'بند مُدخل سابقًا', 'quantity' => 4, 'unit' => 'علبة', 'unit_price' => 2.5]]]])
        ->get(route('admin.logistics.purchase-requests.create'))->assertOk()->assertSee(jsonText('بند مُدخل سابقًا'), false);
});

test('a view-only user sees no create/edit/delete actions on phase 3 lists', function () {
    $viewer = p3User([], ['can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    [$a] = p3Centers();
    $reporter = User::factory()->create();
    TechIssue::create(['reported_by' => $reporter->id, 'title' => 'تذكرة للعرض', 'description' => 'و', 'center_id' => $a->id, 'status' => 'open', 'priority' => 'low']);
    $wh = Warehouse::create(['name' => 'مخزن عرض', 'center_id' => $a->id]);
    WarehouseItem::create(['warehouse_id' => $wh->id, 'name' => 'مادة عرض', 'quantity' => 1, 'unit' => 'قطعة']);

    $this->actingAs($viewer)->get(route('admin.tech.issues.index'))->assertOk()->assertSee('تذكرة للعرض')
        ->assertDontSee(route('admin.tech.issues.create'), false)->assertDontSee('/edit"', false);
    $this->actingAs($viewer)->get(route('admin.logistics.warehouses.items.index', $wh))->assertOk()->assertSee('مادة عرض')
        ->assertDontSee(route('admin.logistics.warehouses.items.create', $wh), false)->assertDontSee('deleteModal_', false);
});

test('tasks statistics view renders with shared KPI cards (view-only test: the controller query uses MySQL MONTH() and cannot run on sqlite)', function () {
    $this->actingAs(User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]));
    view()->share('errors', new Illuminate\Support\ViewErrorBag);
    $html = view('admin.projects.tasks.statistics', [
        'total' => 9, 'byStatus' => collect(), 'executed' => 4, 'notExecuted' => 5, 'withDelay' => 2, 'mediaDone' => 3,
        'pendingCount' => 6, 'inProgress' => 1, 'completed' => 1, 'delayed' => 1, 'byMonth' => collect([3 => 4, 5 => 5]),
    ])->render();

    expect($html)->toContain('class="kpi tone-brand"')->toContain('إجمالي المهام')->toContain('لم تنفذ')
        ->and(str_contains($html, 'stat-card'))->toBeFalse();
});
