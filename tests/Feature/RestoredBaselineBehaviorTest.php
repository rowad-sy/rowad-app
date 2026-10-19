<?php

// اختبارات لمسودة استعادة السلوك السابق للواجهات (claude/restore-pre-ui-behavior):
//  1) توصيف السلوك الأصلي المُستعاد (كما في الأساس 9227994)، بما فيه العيوب القائمة، دون الحكم عليها؛
//  2) إثبات التكافؤ لما أُبقي من تهيئة بيانات العرض (التايم شيت، عدّاد/قائمة توقيع PM2، رسائل التحقق العربية).

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeAttendance;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

function rbEmployee(string $code, array $extra = []): Employee
{
    return Employee::forceCreate(array_merge(['employee_code' => $code, 'first_name_ar' => 'موظف', 'last_name_ar' => $code, 'gender' => 'male', 'status' => 'active', 'children_count' => 0], $extra));
}

test('baseline: the timesheet reads day_of_week with Carbon dayOfWeek (0 = Sunday) exactly as before the UI work, and screen data equals print data', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $e = rbEmployee('RB-1');
    $e->workSchedules()->delete();
    // الفهرس المخزَّن يُقرأ كما هو مقابل Carbon::dayOfWeek: 0 (الأحد بحسب Carbon) عطلة، و5 عطلة
    foreach ([0 => true, 1 => false, 2 => false, 3 => false, 4 => false, 5 => true, 6 => false] as $i => $off) {
        $e->workSchedules()->create(['day_of_week' => $i, 'start_time' => $off ? null : '08:00', 'end_time' => $off ? null : '16:00', 'is_day_off' => $off]);
    }
    EmployeeAttendance::forceCreate(['employee_id' => $e->id, 'date' => '2026-03-03', 'status' => 'absent']);

    $q = http_build_query(['month' => '2026-03', 'search' => 'RB-1']);
    $screen = $this->actingAs($admin)->get('/admin/hr/timesheets?'.$q)->assertOk()->viewData('sheet');
    $print = $this->actingAs($admin)->get('/admin/hr/timesheets/print?'.$q)->assertOk()->viewData('timesheets');

    // مرجع مستقل عن المتحكم: خوارزمية الأساس (dayOfWeek من Carbon)
    $expected = [];
    for ($d = 1; $d <= 31; $d++) {
        $date = Carbon::parse(sprintf('2026-03-%02d', $d));
        $off = in_array($date->dayOfWeek, [0, 5], true);
        $expected[$d] = $d === 3 ? 'absent' : ($off ? 'off' : 'present');
    }
    foreach ([$screen['timesheets'] ?? $screen, $print] as $sheet) {
        $daily = collect($sheet[0]['daily'])->mapWithKeys(fn ($x) => [$x['day'] => $x['status']])->all();
        expect($daily)->toBe($expected);
    }
});

test('baseline: the employee form renders saved warnings and notes as submitted fields again (as before the UI work)', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $e = rbEmployee('RB-2');
    $e->warnings()->create(['date' => '2026-02-01', 'reason' => 'تأخر متكرر', 'level' => 'written']);
    $e->notesRelation()->create(['user_id' => $admin->id, 'note' => 'ملاحظة قديمة']);

    $html = $this->actingAs($admin)->get(route('admin.hr.employees.edit', $e))->assertOk()->getContent();
    expect($html)->toContain('name="warnings[0][reason]"')->toContain('تأخر متكرر')->toContain('name="warnings[0][level]"')
        ->and($html)->toContain('name="notes_list[0][note]"')->toContain('ملاحظة قديمة')
        ->and($html)->not->toContain('saved-row');
});

test('baseline: employees/statistics is shadowed by the employee resource route again (as in the base)', function () {
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($admin)->get('/admin/hr/employees/statistics')->assertStatus(404);
});

// ───── تكافؤ ما أُبقي ─────

test('equivalence: the projects-manager dashboard list is exactly the requests its own counter counts (same filter), capped at 8', function () {
    $me = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $other = User::factory()->create(['type' => 'employee']);
    $n = 0;
    $mk = function (array $a) use (&$n, $other) {
        $n++;

        return PurchaseRequest::create(array_merge(['request_number' => 'RB-PR-'.$n, 'specifications' => 'طلب '.$n, 'status' => 'pm_approved', 'quantity' => 1, 'unit' => 'قطعة', 'expected_unit_price' => 1, 'expected_total_price' => 1, 'user_id' => $other->id], $a));
    };
    $mine = collect(range(1, 3))->map(fn () => $mk(['refer_to_pm2_id' => $me->id]));
    $mk(['refer_to_pm2_id' => $other->id]);
    $mk(['refer_to_pm2_id' => $me->id, 'status' => 'priced']);

    $r = $this->actingAs($me)->get('/admin/projects-manager')->assertOk();
    expect($r->viewData('prAwaitingPm2Sign'))->toBe(3)
        ->and($r->viewData('awaitingMyPm2Requests')->pluck('id')->sort()->values()->all())->toBe($mine->pluck('id')->sort()->values()->all());
});

test('equivalence: Arabic validation messages change wording only — the same rules fail on the same fields inside and outside /admin', function () {
    $rules = ['name' => 'required|string|max:5', 'email' => 'required|email', 'age' => 'required|integer|min:18'];
    $data = ['name' => 'طويل جدا جدا', 'email' => 'x', 'age' => '3'];

    app()->instance('request', Request::create('/admin/whatever'));
    $inside = Validator::make($data, $rules);
    app()->instance('request', Request::create('/login'));
    $outside = Validator::make($data, $rules);

    expect($inside->fails())->toBeTrue()->and($outside->fails())->toBeTrue()
        ->and(array_keys($inside->errors()->messages()))->toBe(array_keys($outside->errors()->messages()))
        ->and(array_keys($inside->failed()))->toBe(array_keys($outside->failed()))
        ->and(collect($inside->failed())->map(fn ($f) => array_keys($f))->all())->toBe(collect($outside->failed())->map(fn ($f) => array_keys($f))->all());

    $ok = ['name' => 'قصير', 'email' => 'a@b.co', 'age' => '20'];
    app()->instance('request', Request::create('/admin/whatever'));
    expect(Validator::make($ok, $rules)->passes())->toBeTrue();
});
