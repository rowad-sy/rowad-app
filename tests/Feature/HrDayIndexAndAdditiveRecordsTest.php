<?php

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeNote;
use App\Models\Admin\Hr\Warning;
use App\Models\User;
use Carbon\Carbon;

/*
 * اختبارات حقيقية (HTTP على sqlite بالذاكرة) لبوابة الإصلاح: معنى day_of_week المخزَّن، والتنبيهات/الملاحظات الإضافية فقط.
 */

function gAdmin(): User
{
    return User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
}

function gBasics(string $code, array $extra = []): array
{
    return array_merge(['employee_code' => $code, 'status' => 'active', 'first_name_ar' => 'اختبار', 'last_name_ar' => 'بوابة', 'gender' => 'male'], $extra);
}

/** جدول دوام كما يرسله النموذج: الفهرس 0=السبت ... 6=الجمعة، مع تحديد أيام العطلة المطلوبة فقط */
function gSchedule(array $daysOff): array
{
    $rows = [];
    foreach (range(0, 6) as $i) {
        $rows[$i] = ['day_of_week' => $i] + (in_array($i, $daysOff, true) ? ['is_day_off' => '1'] : ['start_time' => '08:00', 'end_time' => '16:00']);
    }

    return $rows;
}

function gScreenDays(string $html, string $code): array
{
    preg_match('#<th scope="row" class="ts-employee">(?:(?!</tr>).)*?'.preg_quote($code, '#').'(.*?)</tr>#s', $html, $row);
    preg_match_all('#title="(\d+) [^:"]+: ([^"(]+?)( \(افتراضي[^"]*\))?"#u', $row[1], $m, PREG_SET_ORDER);

    return collect($m)->mapWithKeys(fn ($d) => [(int) $d[1] => trim($d[2])])->all();
}

function gPrintDays(string $html, string $code): array
{
    $section = str($html)->after('('.$code.')')->after('الحالة</td>')->before('<td class="present summary">')->toString();
    preg_match_all('#<td class="([a-z]*)">#', $section, $m);
    $map = ['off' => 'عطلة', 'present' => 'حاضر', 'absent' => 'غائب', 'excused' => 'غياب بعذر'];

    return collect($m[1])->mapWithKeys(fn ($c, $i) => [$i + 1 => $map[$c] ?? '؟'])->all();
}

test('a day chosen in the employee form appears as that same weekday in the timesheet, the print page and the profile', function () {
    $this->actingAs(gAdmin());

    // الأربعاء = الفهرس 4 في النموذج (0=السبت). نحفظه عطلة عبر الطلب الفعلي للنموذج.
    $this->post('/admin/hr/employees', gBasics('G-DAY', ['work_schedules' => gSchedule([4])]))->assertRedirect();
    $emp = Employee::where('employee_code', 'G-DAY')->firstOrFail();
    expect($emp->workSchedules()->where('is_day_off', true)->pluck('day_of_week')->all())->toBe([4]);

    // النموذج نفسه يُعنون الفهرس 4 بالأربعاء
    $form = $this->get(route('admin.hr.employees.edit', $emp))->assertOk()->getContent();
    expect($form)->toMatch('#الأربعاء</td>.*?work_schedules\[4\]\[start_time\]#s')
        ->and($form)->toMatch('#work_schedules\[4\]\[is_day_off\]"[^>]*checked#s');

    // آذار 2026: الأربعاء = 4 و11 و18 و25؛ باقي الأيام حضور افتراضي
    $q = '?month=2026-03&search=G-DAY';
    $screen = gScreenDays($this->get('/admin/hr/timesheets'.$q)->assertOk()->getContent(), 'G-DAY');
    $print = gPrintDays($this->get('/admin/hr/timesheets/print'.$q)->assertOk()->getContent(), 'G-DAY');

    foreach (range(1, 31) as $day) {
        $isWednesday = Carbon::create(2026, 3, $day)->isWednesday();
        expect($screen[$day])->toBe($isWednesday ? 'عطلة' : 'حاضر');
    }
    expect($screen)->toBe($print);                                       // الشاشة والطباعة متطابقتان يومًا بيوم

    // بطاقة «مواعيد العمل اليوم» في ملف الموظف
    Carbon::setTestNow('2026-03-04 10:00:00');                            // الأربعاء ⇒ عطلة أسبوعية
    $this->get(route('admin.hr.employees.show', $emp))->assertOk()->assertSeeText('إجازة أسبوعية');
    Carbon::setTestNow('2026-03-05 10:00:00');                            // الخميس ⇒ دوام
    $this->get(route('admin.hr.employees.show', $emp))->assertOk()->assertSeeText('08:00 - 16:00')->assertDontSeeText('إجازة أسبوعية');
    Carbon::setTestNow();
})->skip('PR#3 day-index fix intentionally reverted in this draft (restores Carbon dayOfWeek reading) — see docs/restore-pre-ui-behavior.md');

test('the default weekend (Friday and Saturday off, Sunday to Thursday working) matches the stored day meaning', function () {
    $this->actingAs(gAdmin());
    $emp = Employee::forceCreate(['employee_code' => 'G-DEF', 'first_name_ar' => 'أ', 'last_name_ar' => 'ب', 'gender' => 'male', 'status' => 'active', 'children_count' => 0]);

    $days = collect(range(1, 31))->mapWithKeys(fn ($d) => [$d => Carbon::create(2026, 3, $d)])->map(
        fn ($date) => ($date->isFriday() || $date->isSaturday()) ? 'عطلة' : 'حاضر'
    )->all();

    expect(gScreenDays($this->get('/admin/hr/timesheets?month=2026-03&search=G-DEF')->getContent(), 'G-DEF'))->toBe($days);
})->skip('PR#3 day-index fix intentionally reverted in this draft — see docs/restore-pre-ui-behavior.md');

/* ---------------- التنبيهات والملاحظات: إضافية فقط ---------------- */

function gEmployeeWithRecords(string $code, User $noteOwner): array
{
    $emp = Employee::forceCreate(['employee_code' => $code, 'first_name_ar' => 'س', 'last_name_ar' => 'ص', 'gender' => 'male', 'status' => 'active', 'children_count' => 0]);
    $w = Warning::create(['employee_id' => $emp->id, 'date' => '2026-01-10', 'reason' => 'تأخر متكرر', 'level' => 'written', 'is_folded' => true, 'fold_reason' => 'انتهت المدة', 'folded_at' => '2026-02-01 09:00:00']);
    $n = EmployeeNote::create(['employee_id' => $emp->id, 'user_id' => $noteOwner->id, 'note' => 'ملاحظة قديمة مهمة']);
    EmployeeNote::whereKey($n->id)->update(['created_at' => '2025-12-01 08:00:00']);

    return [$emp, $w->refresh(), $n->refresh()];
}

test('the employee form shows existing warnings and notes read-only and only new additions are inputs', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['name' => 'صاحب الملاحظة', 'type' => 'employee']);
    [$emp] = gEmployeeWithRecords('G-RO', $author);

    $html = $this->actingAs($actor)->get(route('admin.hr.employees.edit', $emp))->assertOk()->getContent();

    expect($html)->toContain('تأخر متكرر')->toContain('2026-01-10')->toContain('انتهت المدة')->toContain('مطوي')
        ->and($html)->toContain('ملاحظة قديمة مهمة')->toContain('صاحب الملاحظة')
        ->and($html)->not->toContain('name="warnings[0]')          // لا حقول لسجل موجود
        ->and($html)->not->toContain('name="notes_list[0]')
        ->and($html)->toContain('id="warnings-table" data-next-index="0"');
})->skip('PR#3/#4 additive warnings/notes reverted in this draft (saved rows are posted again as in the base) — see docs/restore-pre-ui-behavior.md');

test('saving an employee repeatedly without changes never duplicates or alters warnings and notes', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['type' => 'employee']);
    [$emp, $w, $n] = gEmployeeWithRecords('G-REP', $author);
    $this->actingAs($actor);

    foreach (range(1, 3) as $_) {
        $this->put(route('admin.hr.employees.update', $emp), gBasics('G-REP'))->assertRedirect(route('admin.hr.employees.index'));
    }

    expect(Warning::where('employee_id', $emp->id)->count())->toBe(1)
        ->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(1);
    $w2 = Warning::find($w->id);
    $n2 = EmployeeNote::find($n->id);
    expect([$w2->date->toDateString(), $w2->reason, $w2->level, $w2->is_folded, $w2->fold_reason, (string) $w2->folded_at])->toBe(['2026-01-10', 'تأخر متكرر', 'written', true, 'انتهت المدة', '2026-02-01 09:00:00'])
        ->and([$n2->user_id, $n2->note, (string) $n2->created_at])->toBe([$author->id, 'ملاحظة قديمة مهمة', '2025-12-01 08:00:00']);
});

test('adding a new warning and note keeps the existing ones intact and owns the new note by the actor', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['type' => 'employee']);
    [$emp, $w, $n] = gEmployeeWithRecords('G-ADD', $author);
    $this->actingAs($actor);

    $this->put(route('admin.hr.employees.update', $emp), gBasics('G-ADD', [
        'warnings' => [0 => ['date' => '2026-03-01', 'reason' => 'غياب بلا إذن', 'level' => 'verbal']],
        'notes_list' => [0 => ['note' => 'ملاحظة جديدة']],
    ]))->assertRedirect();

    expect(Warning::where('employee_id', $emp->id)->pluck('reason')->sort()->values()->all())->toBe(['تأخر متكرر', 'غياب بلا إذن'])
        ->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(2)
        ->and(EmployeeNote::whereKey($n->id)->value('user_id'))->toBe($author->id)
        ->and(EmployeeNote::where('note', 'ملاحظة جديدة')->value('user_id'))->toBe($actor->id);
});

test('failed validation then retry creates each new record exactly once and keeps existing ones untouched', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['type' => 'employee']);
    [$emp] = gEmployeeWithRecords('G-RETRY', $author);
    $this->actingAs($actor);
    $payload = ['warnings' => [3 => ['date' => '2026-03-02', 'reason' => 'إنذار جديد', 'level' => 'written']], 'notes_list' => [5 => ['note' => 'ملاحظة بعد الخطأ']]];

    // المحاولة الأولى تفشل (لقب فارغ): لا يُنشأ شيء، وتعود الإضافات الجديدة وحدها بمفاتيحها
    $html = $this->followingRedirects()->from(route('admin.hr.employees.edit', $emp))
        ->put(route('admin.hr.employees.update', $emp), gBasics('G-RETRY', ['last_name_ar' => ''] + $payload))->getContent();
    expect(Warning::where('employee_id', $emp->id)->count())->toBe(1)
        ->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(1)
        ->and($html)->toContain('name="warnings[3][reason]"')->toContain('value="إنذار جديد"')->toContain('name="notes_list[5][note]"')
        ->and($html)->toContain('id="warnings-table" data-next-index="4"')->toContain('id="notes-table" data-next-index="6"');

    // إعادة المحاولة بعد التصحيح: تُنشأ مرة واحدة، والموجود كما هو
    $this->put(route('admin.hr.employees.update', $emp), gBasics('G-RETRY') + $payload)->assertRedirect();
    $this->put(route('admin.hr.employees.update', $emp), gBasics('G-RETRY'))->assertRedirect();
    expect(Warning::where('employee_id', $emp->id)->count())->toBe(2)
        ->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(2)
        ->and(Warning::where('employee_id', $emp->id)->where('reason', 'تأخر متكرر')->value('is_folded'))->toBeTrue();
});

test('ids sent with warnings or notes must belong to the employee; another employee record is never touched', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['type' => 'employee']);
    [$a, $wa, $na] = gEmployeeWithRecords('G-A', $author);
    [$b, $wb, $nb] = gEmployeeWithRecords('G-B', $author);
    $this->actingAs($actor);

    // معرّف تنبيه/ملاحظة تخص موظفًا آخر ⇒ خطأ تحقق، ولا يتغير شيء
    $this->from(route('admin.hr.employees.edit', $a))
        ->put(route('admin.hr.employees.update', $a), gBasics('G-A', [
            'warnings' => [0 => ['id' => $wb->id, 'date' => '2026-03-03', 'reason' => 'سيُرفض', 'level' => 'verbal']],
            'notes_list' => [0 => ['id' => $nb->id, 'note' => 'سيُرفض']],
        ]))
        ->assertRedirect(route('admin.hr.employees.edit', $a))->assertSessionHasErrors(['warnings.0.id', 'notes_list.0.id']);
    expect(Warning::count())->toBe(2)->and(EmployeeNote::count())->toBe(2)
        ->and(Warning::find($wb->id)->reason)->toBe('تأخر متكرر')->and(EmployeeNote::find($nb->id)->note)->toBe('ملاحظة قديمة مهمة');

    // معرّف يخص هذا الموظف ⇒ يُعدّ سجلًا موجودًا فلا يُنشأ ولا يُعدَّل
    $this->put(route('admin.hr.employees.update', $a), gBasics('G-A', [
        'warnings' => [0 => ['id' => $wa->id, 'date' => '2026-05-05', 'reason' => 'تعديل مرفوض', 'level' => 'verbal']],
        'notes_list' => [0 => ['id' => $na->id, 'note' => 'تعديل مرفوض']],
    ]))->assertRedirect(route('admin.hr.employees.index'));
    expect(Warning::count())->toBe(2)->and(Warning::find($wa->id)->reason)->toBe('تأخر متكرر')
        ->and(EmployeeNote::count())->toBe(2)->and(EmployeeNote::find($na->id)->note)->toBe('ملاحظة قديمة مهمة');

    // موظف جديد: لا معرّفات
    $this->post('/admin/hr/employees', gBasics('G-NEW', ['warnings' => [0 => ['id' => $wa->id, 'date' => '2026-03-03', 'reason' => 'x', 'level' => 'verbal']]]))
        ->assertSessionHasErrors('warnings.0.id');
})->skip('PR#3 id-ownership validation reverted in this draft — see docs/restore-pre-ui-behavior.md');

test('two intentional additions with identical content are saved as two independent records, and a save without additions duplicates nothing', function () {
    $actor = gAdmin();
    $author = User::factory()->create(['type' => 'employee']);
    [$emp, $w, $n] = gEmployeeWithRecords('G-SAME', $author);
    $this->actingAs($actor);

    // إضافتان بمحتوى واحد في الطلب نفسه، ومحتوى مطابق لسجل موجود
    $this->put(route('admin.hr.employees.update', $emp), gBasics('G-SAME', [
        'warnings' => [
            0 => ['date' => '2026-01-10', 'reason' => 'تأخر متكرر', 'level' => 'written'],   // مطابق تمامًا للموجود
            1 => ['date' => '2026-04-01', 'reason' => 'إنذار مكرر', 'level' => 'verbal'],
            2 => ['date' => '2026-04-01', 'reason' => 'إنذار مكرر', 'level' => 'verbal'],
        ],
        'notes_list' => [
            0 => ['note' => 'ملاحظة قديمة مهمة'],                                             // مطابقة للموجودة
            1 => ['note' => 'ملاحظة مكررة'],
            2 => ['note' => 'ملاحظة مكررة'],
        ],
    ]))->assertRedirect(route('admin.hr.employees.index'));

    expect(Warning::where('employee_id', $emp->id)->count())->toBe(4)
        ->and(Warning::where('employee_id', $emp->id)->where('reason', 'إنذار مكرر')->count())->toBe(2)
        ->and(Warning::where('employee_id', $emp->id)->where('reason', 'تأخر متكرر')->count())->toBe(2)
        ->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(4)
        ->and(EmployeeNote::where('note', 'ملاحظة مكررة')->count())->toBe(2)
        ->and(EmployeeNote::where('note', 'ملاحظة مكررة')->pluck('user_id')->unique()->all())->toBe([$actor->id]);

    // السجلان الأصليان لم يتغيّرا (ملكية/تاريخ/حالة الطي)
    expect(EmployeeNote::find($n->id)->user_id)->toBe($author->id)
        ->and((string) EmployeeNote::find($n->id)->created_at)->toBe('2025-12-01 08:00:00')
        ->and(Warning::find($w->id)->is_folded)->toBeTrue()
        ->and(Warning::find($w->id)->fold_reason)->toBe('انتهت المدة');

    // حفظ الموظف دون إضافات (كما يرسله النموذج) لا يكرر شيئًا
    foreach (range(1, 2) as $_) {
        $this->put(route('admin.hr.employees.update', $emp), gBasics('G-SAME'))->assertRedirect();
    }
    expect(Warning::where('employee_id', $emp->id)->count())->toBe(4)->and(EmployeeNote::where('employee_id', $emp->id)->count())->toBe(4);
});
