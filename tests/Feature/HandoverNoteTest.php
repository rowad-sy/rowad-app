<?php

use App\Models\Admin\Center;
use App\Models\Admin\Logistics\HandoverNote;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;

const HO_PR_MODEL = 'App\Models\Admin\Logistics\PurchaseRequest';
const HO_MODEL = 'App\Models\Admin\Logistics\HandoverNote';

function hoUser(string $type = 'employee', array $flags = []): User
{
    $u = User::factory()->create(['type' => $type, 'must_change_password' => false]);
    if ($type === 'employee') {
        Permission::create(array_merge([
            'user_id' => $u->id,
            'model_names' => [HO_PR_MODEL],
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ], $flags));

        $canCreateHo = ! empty($flags['ho_create']);
        Permission::create([
            'user_id' => $u->id,
            'model_names' => [HO_MODEL],
            'can_view' => $canCreateHo || ! empty($flags['ho_view']),
            'can_create' => $canCreateHo,
        ]);
    }

    return $u;
}

function hoExecutedRequest(User $creator): PurchaseRequest
{
    $center = Center::create(['name' => 'عفرين', 'code' => 'B01-01-00']);
    $project = Project::create(['name' => 'الرعاية الصحية', 'code' => '2503']);

    $pr = PurchaseRequest::create([
        'request_number' => 'PR-HO-0001',
        'user_id' => $creator->id,
        'pr_date' => now()->toDateString(),
        'center_id' => $center->id,
        'project_id' => $project->id,
        'specifications' => 'حاسوب محمول',
        'quantity' => 1,
        'unit' => 'قطعة',
        'expected_unit_price' => 500,
        'expected_total_price' => 500,
        'status' => 'executed',
    ]);
    $pr->items()->create(['description' => 'حاسوب محمول Lenovo', 'quantity' => 2, 'unit' => 'قطعة', 'unit_price' => 500, 'total_price' => 1000, 'currency' => 'USD']);
    $pr->items()->create(['description' => 'شاشة 24', 'quantity' => 1, 'unit' => 'قطعة', 'unit_price' => 120, 'total_price' => 120, 'currency' => 'USD']);

    return $pr;
}

test('handover create form prefills items from executed request and blocks non-executed', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);

    $this->actingAs($creator)->get(route('admin.logistics.handover-notes.create', $pr))
        ->assertOk()
        ->assertSee('حاسوب محمول Lenovo')
        ->assertSee('محضر تسليم عهدة');

    $pending = PurchaseRequest::create([
        'request_number' => 'PR-HO-0002', 'user_id' => $creator->id, 'pr_date' => now()->toDateString(),
        'specifications' => 'x', 'quantity' => 1, 'unit' => 'قطعة', 'expected_unit_price' => 1, 'expected_total_price' => 1,
        'status' => 'review',
    ]);
    $this->actingAs($creator)->get(route('admin.logistics.handover-notes.create', $pending))->assertStatus(403);
});

test('handover note stores with registered receiver syncing name and position', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $receiver = hoUser();

    $items = $pr->items()->get()->map(fn ($i) => ['item_id' => $i->id, 'description' => $i->description, 'serial' => 'SN-1', 'unit' => $i->unit, 'quantity' => $i->quantity, 'status' => 'جيدة'])->all();

    $this->actingAs($creator)->post(route('admin.logistics.handover-notes.store', $pr), [
        'grn_number' => 'GRN-316',
        'handover_date' => now()->toDateString(),
        'delivered_by_name' => 'محمد زويدة',
        'delivered_by_position' => 'مدير المكتب',
        'received_by_user_id' => $receiver->id,
        'received_by_name' => '',
        'items' => $items,
    ])->assertRedirect();

    $note = HandoverNote::firstWhere('grn_number', 'GRN-316');
    expect($note)->not->toBeNull()
        ->and($note->received_by_user_id)->toBe($receiver->id)
        ->and($note->received_by_name)->toBe($receiver->name)
        ->and(count($note->items))->toBe(2);
});

test('handover note accepts manual receiver without account and rejects nameless one', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $item = $pr->items()->first();

    $this->actingAs($creator)->post(route('admin.logistics.handover-notes.store', $pr), [
        'grn_number' => 'GRN-317',
        'handover_date' => now()->toDateString(),
        'delivered_by_name' => 'محمد زويدة',
        'received_by_name' => 'وارد جديد — لا يملك حساباً',
        'items' => [['description' => $item->description, 'quantity' => 2, 'unit' => 'قطعة', 'status' => 'جيدة']],
    ])->assertRedirect();

    $note = HandoverNote::firstWhere('grn_number', 'GRN-317');
    expect($note->received_by_user_id)->toBeNull()
        ->and($note->received_by_name)->toBe('وارد جديد — لا يملك حساباً');

    $this->actingAs($creator)->post(route('admin.logistics.handover-notes.store', $pr), [
        'grn_number' => 'GRN-318',
        'handover_date' => now()->toDateString(),
        'delivered_by_name' => 'ز',
        'items' => [['description' => $item->description]],
    ])->assertSessionHasErrors('received_by_name');
});

test('handover print and excel render the GRN with identity', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $item = $pr->items()->first();

    $note = HandoverNote::create([
        'purchase_request_id' => $pr->id,
        'grn_number' => 'GRN-319',
        'handover_date' => now()->toDateString(),
        'delivered_by_user_id' => $creator->id,
        'delivered_by_name' => 'محمد زويدة',
        'delivered_by_position' => 'مدير المكتب',
        'received_by_name' => 'أمين المخزن',
        'items' => [['description' => $item->description, 'serial' => 'SN-9', 'unit' => 'قطعة', 'quantity' => 2, 'status' => 'جيدة']],
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator)->get(route('admin.logistics.handover-notes.show', $note))
        ->assertOk()->assertSee('GRN-319')->assertSee('أمين المخزن');

    $this->actingAs($creator)->get(route('admin.logistics.handover-notes.print', $note))
        ->assertOk()
        ->assertSee('تسليم عهدة')
        ->assertSee('Goods Receipt Note')
        ->assertSee('RCD-OPR-PS-F04')
        ->assertSee('SN-9')
        ->assertSee('PR-HO-0001')
        ->assertSee('logo.png', false);

    $this->actingAs($creator)->get(route('admin.logistics.handover-notes.export', $note))->assertOk();
});

test('handover grn number must be unique', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $item = $pr->items()->first();

    $payload = fn (string $no) => [
        'grn_number' => $no,
        'handover_date' => now()->toDateString(),
        'delivered_by_name' => 'م',
        'received_by_name' => 'س',
        'items' => [['description' => $item->description]],
    ];

    $this->actingAs($creator)->post(route('admin.logistics.handover-notes.store', $pr), $payload('GRN-DUP'))->assertRedirect();
    $this->actingAs($creator)->post(route('admin.logistics.handover-notes.store', $pr), $payload('GRN-DUP'))->assertSessionHasErrors('grn_number');
});

test('handover tab lists notes on the purchase-requests page, gated by permission', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $item = $pr->items()->first();

    HandoverNote::create([
        'purchase_request_id' => $pr->id, 'grn_number' => 'GRN-TAB-1', 'handover_date' => now()->toDateString(),
        'delivered_by_name' => 'محمد', 'received_by_name' => 'أمين المستودع',
        'items' => [['description' => $item->description, 'quantity' => 2, 'unit' => 'قطعة', 'status' => 'جيدة']],
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator)->get('/admin/logistics/purchase-requests?tab=handovers')
        ->assertOk()
        ->assertSee('محاضر التسليم')
        ->assertSee('GRN-TAB-1')
        ->assertSee('أمين المستودع');

    // بدون صلاحية المحاضر: لا يظهر التبويب ولا قائمة المحاضر
    $plain = hoUser();
    $plainHtml = $this->actingAs($plain)->get('/admin/logistics/purchase-requests?tab=handovers')
        ->assertOk()
        ->getContent();
    expect($plainHtml)->not->toContain('tab=handovers');
});

test('outsider cannot create or view handover notes', function () {
    $creator = hoUser(flags: ['ho_create' => true]);
    $pr = hoExecutedRequest($creator);
    $outsider = hoUser();

    $this->actingAs($outsider)->get(route('admin.logistics.handover-notes.create', $pr))->assertStatus(403);

    $note = HandoverNote::create([
        'purchase_request_id' => $pr->id, 'grn_number' => 'GRN-OUT', 'handover_date' => now(),
        'delivered_by_name' => 'م', 'received_by_name' => 'س', 'items' => [], 'created_by' => $creator->id,
    ]);
    $this->actingAs($outsider)->get(route('admin.logistics.handover-notes.show', $note))->assertStatus(403);
});
