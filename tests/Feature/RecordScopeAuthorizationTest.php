<?php

// اختبارات HTTP حقيقية: الإذن ونطاق السجل شرطان مستقلان في التقنية والأصول (بيانات اصطناعية، قاعدة معزولة).
// بعد كل رفض: لا تغيّر في القيم ولا في عدد السجلات ولا في الرد/الحالة/resolved_at.

use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Logistics\Asset;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechEquipment;
use App\Models\Admin\Tech\TechIssue;
use App\Models\AuditLog;
use App\Models\User;

function rsUser(array $flags, array $scope = [], ?array $models = null): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge(['user_id' => $u->id, 'model_names' => $models ?? [TechIssue::class, TechEquipment::class, Asset::class],
        'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false], $flags, $scope));

    return $u;
}

function rsWorld(): object
{
    $w = new stdClass;
    $w->a = Center::create(['name' => 'مركز أ']);
    $w->b = Center::create(['name' => 'مركز ب']);
    $w->pa = Project::create(['name' => 'مشروع أ']);
    $w->pb = Project::create(['name' => 'مشروع ب']);
    $w->reporter = User::factory()->create(['type' => 'employee']);
    $issue = fn ($t, $c, $p) => TechIssue::create(['reported_by' => $w->reporter->id, 'title' => $t, 'description' => 'وصف '.$t, 'center_id' => $c->id, 'project_id' => $p->id,
        'status' => 'open', 'priority' => 'low', 'admin_response' => null]);
    $w->issueA = $issue('تذكرة-أ', $w->a, $w->pa);
    $w->issueB = $issue('تذكرة-ب', $w->b, $w->pb);
    $eq = fn ($n, $c, $p) => TechEquipment::create(['name' => $n, 'type' => 'خادم', 'condition' => 'a', 'center_id' => $c->id, 'project_id' => $p->id]);
    $w->eqA = $eq('معدة-أ', $w->a, $w->pa);
    $w->eqB = $eq('معدة-ب', $w->b, $w->pb);
    $asset = fn ($n, $c, $p) => Asset::create(['asset_code' => 'C-'.$n, 'name' => 'أصل-'.$n, 'type' => 'أجهزة', 'status' => 'جيد', 'center_id' => $c->id, 'project_id' => $p->id, 'notes' => 'ملاحظة-سرية-'.$n]);
    $w->assetA = $asset('أ', $w->a, $w->pa);
    $w->assetB = $asset('ب', $w->b, $w->pb);

    return $w;
}

function rsSnapshot(TechIssue $i): array
{
    $i->refresh();

    return [$i->title, $i->status, $i->admin_response, $i->resolved_at?->toDateTimeString(), $i->center_id, $i->project_id, $i->assigned_to, $i->priority];
}

// ───── التذاكر ─────

test('ticket: no tech permission and view-only accounts are rejected on respond/update/destroy with nothing changed', function () {
    $w = rsWorld();
    $before = rsSnapshot($w->issueA);
    $body = ['admin_response' => 'اختراق', 'status' => 'completed'];
    $upd = ['title' => 'مخترقة', 'description' => 'd', 'priority' => 'high', 'status' => 'completed', 'center_id' => $w->a->id, 'project_id' => $w->pa->id];

    $none = rsUser([], [], [Project::class]);
    $ro = rsUser([]); // عرض فقط بنطاق مفتوح
    foreach ([$none, $ro] as $u) {
        $this->actingAs($u)->post(route('admin.tech.issues.respond', $w->issueA), $body)->assertForbidden();
        $this->actingAs($u)->put(route('admin.tech.issues.update', $w->issueA), $upd)->assertForbidden();
        $this->actingAs($u)->delete(route('admin.tech.issues.destroy', $w->issueA))->assertForbidden();
    }
    expect(rsSnapshot($w->issueA))->toBe($before)->and(TechIssue::count())->toBe(2)->and(AuditLog::where('event', 'updated')->where('model_name', 'like', '%TechIssue%')->count())->toBe(0);
});

test('ticket: an in-scope editor can respond (status and resolved_at) and edit; a global editor and super-admin keep access', function () {
    $w = rsWorld();
    $ed = rsUser(['can_edit' => true], ['center_id' => $w->a->id]);
    $this->actingAs($ed)->post(route('admin.tech.issues.respond', $w->issueA), ['admin_response' => 'تم', 'status' => 'completed'])->assertRedirect();
    $w->issueA->refresh();
    expect($w->issueA->admin_response)->toBe('تم')->and($w->issueA->status)->toBe('completed')->and($w->issueA->resolved_at)->not->toBeNull();

    $this->actingAs($ed)->put(route('admin.tech.issues.update', $w->issueA), ['title' => 'معدّلة', 'description' => 'd', 'priority' => 'high', 'status' => 'in_progress', 'center_id' => $w->a->id, 'project_id' => $w->pa->id])->assertRedirect();
    expect($w->issueA->fresh()->title)->toBe('معدّلة');

    $global = rsUser(['can_edit' => true, 'can_delete' => true]);
    $this->actingAs($global)->post(route('admin.tech.issues.respond', $w->issueB), ['admin_response' => 'عام', 'status' => 'in_progress'])->assertRedirect();
    expect($w->issueB->fresh()->admin_response)->toBe('عام');
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($super)->get(route('admin.tech.issues.show', $w->issueB))->assertOk();
    $this->actingAs($global)->delete(route('admin.tech.issues.destroy', $w->issueB))->assertRedirect();
});

test('ticket: a center-scoped user is rejected on show/edit/update/respond/destroy for another center, before any write', function () {
    $w = rsWorld();
    $u = rsUser(['can_create' => true, 'can_edit' => true, 'can_delete' => true], ['center_id' => $w->a->id]);
    $before = rsSnapshot($w->issueB);

    $this->actingAs($u)->get(route('admin.tech.issues.show', $w->issueA))->assertOk()->assertSee('تذكرة-أ');
    $r = $this->actingAs($u)->get(route('admin.tech.issues.show', $w->issueB));
    $r->assertForbidden();
    expect($r->getContent())->not->toContain('تذكرة-ب')->not->toContain('وصف تذكرة-ب');
    $this->actingAs($u)->get(route('admin.tech.issues.edit', $w->issueB))->assertForbidden();
    $this->actingAs($u)->put(route('admin.tech.issues.update', $w->issueB), ['title' => 'مخترقة', 'description' => 'd', 'priority' => 'urgent', 'status' => 'completed', 'center_id' => $w->b->id])->assertForbidden();
    $this->actingAs($u)->post(route('admin.tech.issues.respond', $w->issueB), ['admin_response' => 'اختراق', 'status' => 'completed'])->assertForbidden();
    $this->actingAs($u)->delete(route('admin.tech.issues.destroy', $w->issueB))->assertForbidden();

    expect(rsSnapshot($w->issueB))->toBe($before)->and(TechIssue::count())->toBe(2);
});

test('ticket: create/move to a center or project outside the scope is rejected, including null bypass', function () {
    $w = rsWorld();
    $u = rsUser(['can_create' => true, 'can_edit' => true], ['center_id' => $w->a->id]);
    $ok = ['title' => 'جديدة', 'description' => 'd', 'priority' => 'low'];

    $this->actingAs($u)->post(route('admin.tech.issues.store'), $ok + ['center_id' => $w->b->id])->assertSessionHasErrors('center_id');
    $this->actingAs($u)->post(route('admin.tech.issues.store'), $ok + ['center_id' => null])->assertSessionHasErrors('center_id');
    expect(TechIssue::count())->toBe(2);
    $this->actingAs($u)->post(route('admin.tech.issues.store'), $ok + ['center_id' => $w->a->id, 'project_id' => $w->pa->id])->assertRedirect();
    expect(TechIssue::count())->toBe(3);

    // نقل تذكرة داخل النطاق إلى مركز خارجه، أو إزالة المركز/المشروع
    $before = rsSnapshot($w->issueA);
    $base = ['title' => 'تذكرة-أ', 'description' => 'd', 'priority' => 'low', 'status' => 'open'];
    $this->actingAs($u)->put(route('admin.tech.issues.update', $w->issueA), $base + ['center_id' => $w->b->id, 'project_id' => $w->pa->id])->assertSessionHasErrors('center_id');
    $this->actingAs($u)->put(route('admin.tech.issues.update', $w->issueA), $base + ['center_id' => null])->assertSessionHasErrors('center_id');
    expect(rsSnapshot($w->issueA))->toBe($before);

    // نطاق مركز+مشروع: مشروع آخر أو حذف المشروع مرفوض
    $sc = rsUser(['can_edit' => true], ['center_id' => $w->a->id, 'project_id' => $w->pa->id]);
    $this->actingAs($sc)->put(route('admin.tech.issues.update', $w->issueA), $base + ['center_id' => $w->a->id, 'project_id' => $w->pb->id])->assertSessionHasErrors('center_id');
    $this->actingAs($sc)->put(route('admin.tech.issues.update', $w->issueA), $base + ['center_id' => $w->a->id, 'project_id' => null])->assertSessionHasErrors('center_id');
    expect(rsSnapshot($w->issueA))->toBe($before);
});

test('ticket: project-scoped user is rejected outside the project; view in one center and edit in another never yields edit in the first', function () {
    $w = rsWorld();
    $pu = rsUser(['can_edit' => true], ['project_id' => $w->pa->id]);
    $this->actingAs($pu)->post(route('admin.tech.issues.respond', $w->issueB), ['admin_response' => 'x', 'status' => 'open'])->assertForbidden();
    $this->actingAs($pu)->post(route('admin.tech.issues.respond', $w->issueA), ['admin_response' => 'ok', 'status' => 'open'])->assertRedirect();

    $mixed = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(['user_id' => $mixed->id, 'model_names' => [TechIssue::class], 'center_id' => $w->a->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    Permission::create(['user_id' => $mixed->id, 'model_names' => [TechIssue::class], 'center_id' => $w->b->id, 'can_view' => true, 'can_create' => false, 'can_edit' => true, 'can_delete' => false]);
    $before = rsSnapshot($w->issueA);
    $this->actingAs($mixed)->post(route('admin.tech.issues.respond', $w->issueA), ['admin_response' => 'x', 'status' => 'completed'])->assertForbidden();
    $this->actingAs($mixed)->post(route('admin.tech.issues.respond', $w->issueB), ['admin_response' => 'مسموح', 'status' => 'open'])->assertRedirect();
    expect(rsSnapshot($w->issueA))->toBe($before)->and($w->issueB->fresh()->admin_response)->toBe('مسموح');
});

test('group permissions apply with the same scope; the assignee or reporter does not bypass the scope', function () {
    $w = rsWorld();
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $g = Group::create(['name' => 'فريق التقنية']);
    $u->groups()->attach($g);
    Permission::create(['group_id' => $g->id, 'model_names' => [TechIssue::class], 'center_id' => $w->a->id, 'can_view' => true, 'can_create' => false, 'can_edit' => true, 'can_delete' => false]);

    $this->actingAs($u)->post(route('admin.tech.issues.respond', $w->issueA), ['admin_response' => 'من المجموعة', 'status' => 'open'])->assertRedirect();
    expect($w->issueA->fresh()->admin_response)->toBe('من المجموعة');

    // مُسنَد إليه ومُبلِّغ لتذكرة مركز آخر: لا تجاوز للنطاق (قرار الإسناد عبر المراكز غير محسوم — موثّق)
    $w->issueB->update(['assigned_to' => $u->id, 'reported_by' => $u->id]);
    $before = rsSnapshot($w->issueB);
    $this->actingAs($u)->post(route('admin.tech.issues.respond', $w->issueB), ['admin_response' => 'x', 'status' => 'completed'])->assertForbidden();
    $this->actingAs($u)->get(route('admin.tech.issues.show', $w->issueB))->assertForbidden();
    expect(rsSnapshot($w->issueB))->toBe($before);
});

test('ticket page shows respond/edit/delete actions only when the server would allow them', function () {
    $w = rsWorld();
    $ed = rsUser(['can_edit' => true, 'can_delete' => true], ['center_id' => $w->a->id]);
    $show = $this->actingAs($ed)->get(route('admin.tech.issues.show', $w->issueA))->assertOk();
    $show->assertSee(route('admin.tech.issues.respond', $w->issueA), false)->assertSee(route('admin.tech.issues.edit', $w->issueA), false);
    $list = $this->actingAs($ed)->get(route('admin.tech.issues.index'))->assertOk();
    $list->assertSee(route('admin.tech.issues.edit', $w->issueA), false)->assertDontSee(route('admin.tech.issues.edit', $w->issueB), false);

    $ro = rsUser([]);
    $this->actingAs($ro)->get(route('admin.tech.issues.show', $w->issueA))->assertOk()->assertDontSee(route('admin.tech.issues.respond', $w->issueA), false)->assertDontSee(route('admin.tech.issues.edit', $w->issueA), false);
});

// ───── المعدات ─────

test('equipment: in-scope view/edit/update/delete work; out-of-scope is rejected with nothing changed; targets are scope-checked', function () {
    $w = rsWorld();
    $u = rsUser(['can_create' => true, 'can_edit' => true, 'can_delete' => true], ['center_id' => $w->a->id]);
    $form = ['name' => 'معدة-أ', 'type' => 'خادم', 'condition' => 'b', 'center_id' => $w->a->id, 'project_id' => $w->pa->id];

    $this->actingAs($u)->get(route('admin.tech.equipment.show', $w->eqA))->assertOk();
    $this->actingAs($u)->get(route('admin.tech.equipment.edit', $w->eqA))->assertOk();
    $this->actingAs($u)->put(route('admin.tech.equipment.update', $w->eqA), $form)->assertRedirect();
    expect($w->eqA->fresh()->condition)->toBe('b');

    $this->actingAs($u)->get(route('admin.tech.equipment.show', $w->eqB))->assertForbidden();
    $this->actingAs($u)->get(route('admin.tech.equipment.edit', $w->eqB))->assertForbidden();
    $this->actingAs($u)->put(route('admin.tech.equipment.update', $w->eqB), ['name' => 'مخترقة', 'center_id' => $w->b->id] + $form)->assertForbidden();
    $this->actingAs($u)->delete(route('admin.tech.equipment.destroy', $w->eqB))->assertForbidden();
    expect($w->eqB->fresh()->name)->toBe('معدة-ب');

    // وجهات خارج النطاق (نقل/إنشاء/إزالة المركز)
    $this->actingAs($u)->put(route('admin.tech.equipment.update', $w->eqA), ['center_id' => $w->b->id] + $form)->assertSessionHasErrors('center_id');
    $this->actingAs($u)->put(route('admin.tech.equipment.update', $w->eqA), ['center_id' => null] + $form)->assertSessionHasErrors('center_id');
    $this->actingAs($u)->post(route('admin.tech.equipment.store'), ['center_id' => $w->b->id] + $form)->assertSessionHasErrors('center_id');
    expect($w->eqA->fresh()->center_id)->toBe($w->a->id)->and(TechEquipment::count())->toBe(2);

    $this->actingAs($u)->delete(route('admin.tech.equipment.destroy', $w->eqA))->assertRedirect();
    expect(TechEquipment::count())->toBe(1);
    $ro = rsUser([]);
    $this->actingAs($ro)->put(route('admin.tech.equipment.update', $w->eqB), $form)->assertForbidden();
    $this->actingAs($ro)->delete(route('admin.tech.equipment.destroy', $w->eqB))->assertForbidden();
    expect(TechEquipment::count())->toBe(1);
});

// ───── الأصول ─────

test('asset details: scoped by the asset center/project, list is consistent, rejected requests reveal and change nothing', function () {
    $w = rsWorld();
    $cu = rsUser([], ['center_id' => $w->a->id]);
    $this->actingAs($cu)->get(route('admin.logistics.assets.show', $w->assetA))->assertOk()->assertSee('ملاحظة-سرية-أ');
    $r = $this->actingAs($cu)->get(route('admin.logistics.assets.show', $w->assetB));
    $r->assertForbidden();
    expect($r->getContent())->not->toContain('ملاحظة-سرية-ب')->not->toContain('C-ب');
    $list = $this->actingAs($cu)->get(route('admin.logistics.assets.index'))->assertOk();
    $list->assertSee('أصل-أ')->assertDontSee('أصل-ب')->assertDontSee(route('admin.logistics.assets.show', $w->assetB), false);

    $pu = rsUser([], ['project_id' => $w->pa->id]);
    $this->actingAs($pu)->get(route('admin.logistics.assets.show', $w->assetA))->assertOk();
    $this->actingAs($pu)->get(route('admin.logistics.assets.show', $w->assetB))->assertForbidden();
    $this->actingAs($pu)->get(route('admin.logistics.assets.index'))->assertOk()->assertSee('أصل-أ')->assertDontSee('أصل-ب');

    $none = rsUser([], [], [Project::class]);
    $this->actingAs($none)->get(route('admin.logistics.assets.show', $w->assetA))->assertForbidden();

    // عام مخوّل وsuper-admin
    $global = rsUser([]);
    $this->actingAs($global)->get(route('admin.logistics.assets.show', $w->assetB))->assertOk();
    $this->actingAs($global)->get(route('admin.logistics.assets.index'))->assertOk()->assertSee('أصل-أ')->assertSee('أصل-ب');
    $super = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($super)->get(route('admin.logistics.assets.show', $w->assetB))->assertOk();

    // كتابة خارج النطاق مرفوضة دون تغيير
    $wu = rsUser(['can_create' => true, 'can_edit' => true, 'can_delete' => true], ['center_id' => $w->a->id]);
    $this->actingAs($wu)->put(route('admin.logistics.assets.update', $w->assetB), ['asset_code' => 'C-ب', 'name' => 'مخترق', 'center_id' => $w->b->id])->assertForbidden();
    $this->actingAs($wu)->delete(route('admin.logistics.assets.destroy', $w->assetB))->assertForbidden();
    $this->actingAs($wu)->post(route('admin.logistics.assets.store'), ['asset_code' => 'N-1', 'name' => 'جديد', 'center_id' => $w->b->id])->assertSessionHasErrors('center_id');
    expect($w->assetB->fresh()->name)->toBe('أصل-ب')->and(Asset::count())->toBe(2);
});

test('asset details honour group permissions and a record-specific permission; view in one center does not grant edit in it', function () {
    $w = rsWorld();
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $g = Group::create(['name' => 'مجموعة الأصول']);
    $u->groups()->attach($g);
    Permission::create(['group_id' => $g->id, 'model_names' => [Asset::class], 'center_id' => $w->a->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    Permission::create(['user_id' => $u->id, 'model_names' => [Asset::class], 'center_id' => $w->b->id, 'can_view' => false, 'can_create' => false, 'can_edit' => true, 'can_delete' => false]);
    $this->actingAs($u)->get(route('admin.logistics.assets.show', $w->assetA))->assertOk();
    $this->actingAs($u)->get(route('admin.logistics.assets.edit', $w->assetA))->assertForbidden(); // عرض في أ لا يولّد تعديلًا في أ
    $this->actingAs($u)->get(route('admin.logistics.assets.show', $w->assetB))->assertForbidden(); // تعديل في ب لا يمنح عرضًا في ب

    $s = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(['user_id' => $s->id, 'model_names' => [Asset::class], 'model_id' => $w->assetB->id, 'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    $this->actingAs($s)->get(route('admin.logistics.assets.show', $w->assetB))->assertOk();
    $this->actingAs($s)->get(route('admin.logistics.assets.show', $w->assetA))->assertForbidden();
});
