<?php

// اختبارات HTTP حقيقية (sqlite بالذاكرة، بيانات اصطناعية) لمسارات الكتابة التي لمستها واجهات المراحل 1–4:
// تتحقق من القيم المحفوظة فعليًا، وأن الفشل/الرفض لا يكتب شيئًا، وأن حفظًا دون تغيير لا يكرر ولا يبدّل.

use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Physiotherapy\PhysioSession;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectTask;
use App\Models\Admin\Tech\TechIssue;
use App\Models\AuditLog;
use App\Models\User;

function pvUser(array $models, array $flags = [], array $scope = []): User
{
    $u = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create(array_merge(['user_id' => $u->id, 'model_names' => $models, 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true], $flags, $scope));

    return $u;
}

test('tech ticket lifecycle writes exactly the submitted values, respond sets status and resolved_at, invalid input writes nothing', function () {
    $c = Center::create(['name' => 'مركز']);
    $u = pvUser([TechIssue::class]);
    $assignee = User::factory()->create(['type' => 'employee']);

    $this->actingAs($u)->post(route('admin.tech.issues.store'), ['title' => 'عطل الشبكة', 'description' => 'وصف العطل', 'center_id' => $c->id, 'priority' => 'high', 'assigned_to' => $assignee->id])->assertRedirect();
    $issue = TechIssue::sole();
    expect($issue->title)->toBe('عطل الشبكة')->and($issue->status)->toBe('open')->and($issue->priority)->toBe('high')->and($issue->reported_by)->toBe($u->id)
        ->and($issue->assigned_to)->toBe($assignee->id)->and($issue->center_id)->toBe($c->id);

    // فشل تحقق: لا يُنشأ سجل ولا يتغير الموجود
    $this->actingAs($u)->post(route('admin.tech.issues.store'), ['title' => 'ناقص', 'priority' => 'high'])->assertSessionHasErrors('description');
    $this->actingAs($u)->put(route('admin.tech.issues.update', $issue), ['title' => 'يجب ألا يُحفظ', 'description' => 'd', 'priority' => 'bogus', 'status' => 'open'])->assertSessionHasErrors('priority');
    expect(TechIssue::count())->toBe(1)->and($issue->fresh()->title)->toBe('عطل الشبكة');

    // حفظ التعديل بالقيم نفسها لا يبدّل المُبلِّغ ولا المُسنَد ولا يكرر
    $this->actingAs($u)->put(route('admin.tech.issues.update', $issue), ['title' => 'عطل الشبكة', 'description' => 'وصف العطل', 'center_id' => $c->id, 'priority' => 'high', 'assigned_to' => $assignee->id, 'status' => 'open'])->assertRedirect();
    $issue->refresh();
    expect(TechIssue::count())->toBe(1)->and($issue->reported_by)->toBe($u->id)->and($issue->assigned_to)->toBe($assignee->id)->and($issue->resolved_at)->toBeNull();

    // الرد: تغيير الحالة، والإكمال يضبط resolved_at
    $this->actingAs($u)->post(route('admin.tech.issues.respond', $issue), ['admin_response' => 'تم الحل', 'status' => 'completed'])->assertRedirect();
    $issue->refresh();
    expect($issue->status)->toBe('completed')->and($issue->admin_response)->toBe('تم الحل')->and($issue->resolved_at)->not->toBeNull();

    // سجل التدقيق يسجّل التعديلات تلقائيًا ويُعرض
    expect(AuditLog::where('model_id', $issue->id)->where('model_name', 'like', '%TechIssue%')->count())->toBeGreaterThan(0);
    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($admin)->get(route('admin.audit-logs.history', ['model' => TechIssue::class, 'model_id' => $issue->id]))->assertOk();
});

test('view-only user is rejected by the server on tech ticket writes and nothing is written', function () {
    $ro = pvUser([TechIssue::class], ['can_create' => false, 'can_edit' => false, 'can_delete' => false]);
    $reporter = User::factory()->create();
    $issue = TechIssue::create(['reported_by' => $reporter->id, 'title' => 'ثابتة', 'description' => 'd', 'center_id' => Center::create(['name' => 'م'])->id, 'status' => 'open', 'priority' => 'low']);

    $this->actingAs($ro)->post(route('admin.tech.issues.store'), ['title' => 'x', 'description' => 'y', 'priority' => 'low'])->assertForbidden();
    $this->actingAs($ro)->put(route('admin.tech.issues.update', $issue), ['title' => 'x', 'description' => 'y', 'priority' => 'low', 'status' => 'open'])->assertForbidden();
    $this->actingAs($ro)->delete(route('admin.tech.issues.destroy', $issue))->assertForbidden();
    expect(TechIssue::count())->toBe(1)->and($issue->fresh()->title)->toBe('ثابتة');
});

test('physiotherapy sessions: in-scope save keeps values and chronological session numbers; out-of-scope patient is rejected without writing; medical fields are not in the patients list', function () {
    $a = Center::create(['name' => 'أ']); $b = Center::create(['name' => 'ب']);
    $u = pvUser([PhysioPatient::class, PhysioSession::class], [], ['center_id' => $a->id]);
    $mine = PhysioPatient::create(['name' => 'مريض أ', 'center_id' => $a->id, 'gender' => 'male', 'registration_date' => now()->toDateString(), 'medical_history' => 'تاريخ مرضي سري']);
    $theirs = PhysioPatient::create(['name' => 'مريض ب', 'center_id' => $b->id, 'gender' => 'male', 'registration_date' => now()->toDateString()]);

    $this->actingAs($u)->post(route('admin.physiotherapy.sessions.store'), ['patient_id' => $mine->id, 'session_date' => '2026-03-01', 'what_done' => 'تمرين أول'])->assertRedirect();
    $this->actingAs($u)->post(route('admin.physiotherapy.sessions.store'), ['patient_id' => $mine->id, 'session_date' => '2026-03-08', 'what_done' => 'تمرين ثانٍ'])->assertRedirect();
    expect($mine->sessions()->pluck('session_number')->all())->toBe([1, 2])->and($mine->sessions()->pluck('what_done')->all())->toBe(['تمرين أول', 'تمرين ثانٍ']);

    // خارج النطاق: 403 دون كتابة؛ فشل تحقق: لا كتابة
    $this->actingAs($u)->post(route('admin.physiotherapy.sessions.store'), ['patient_id' => $theirs->id, 'session_date' => '2026-03-01', 'what_done' => 'x'])->assertForbidden();
    $this->actingAs($u)->post(route('admin.physiotherapy.sessions.store'), ['patient_id' => $mine->id, 'session_date' => '2026-03-09'])->assertSessionHasErrors('what_done');
    expect(PhysioSession::count())->toBe(2);

    // القائمة لا توسّع البيانات الطبية: لا تاريخ مرضي ولا مريض خارج النطاق
    $list = $this->actingAs($u)->get(route('admin.physiotherapy.patients.index'))->assertOk();
    $list->assertSee('مريض أ')->assertDontSee('تاريخ مرضي سري')->assertDontSee('مريض ب');
});

test('projects and tasks: create/edit save exact values, an unchanged re-save changes nothing, failed validation writes nothing', function () {
    $u = pvUser([Project::class, ProjectTask::class]);
    $assignee = User::factory()->create(['type' => 'employee']);
    $c = Center::create(['name' => 'مركز']);

    $this->actingAs($u)->post(route('admin.projects.store'), ['name' => 'مشروع الاختبار', 'status' => 'active'])->assertRedirect();
    $p = Project::where('name', 'مشروع الاختبار')->firstOrFail();
    expect($p->status)->toBe('active');
    $this->actingAs($u)->post(route('admin.projects.store'), ['status' => 'active'])->assertSessionHasErrors('name');
    expect(Project::count())->toBe(1);

    $payload = ['title' => 'مهمة', 'purpose' => 'غاية', 'start_date' => '2026-04-01', 'end_date' => '2026-04-10', 'assigned_to' => $assignee->id, 'center_id' => $c->id, 'status' => 'pending'];
    $this->actingAs($u)->post(route('admin.projects.tasks.store'), $payload)->assertRedirect();
    $t = ProjectTask::sole();
    expect($t->created_by)->toBe($u->id)->and($t->start_date->toDateString())->toBe('2026-04-01')->and($t->end_date->toDateString())->toBe('2026-04-10')->and($t->assigned_to)->toBe($assignee->id);

    // إعادة حفظ دون تغيير: نفس السجل، لا تكرار، ولا تبديل لمن أنشأ
    $this->actingAs($u)->put(route('admin.projects.tasks.update', $t), $payload)->assertRedirect();
    $t->refresh();
    expect(ProjectTask::count())->toBe(1)->and($t->created_by)->toBe($u->id)->and($t->title)->toBe('مهمة');

    // نهاية قبل البداية أو حقل ناقص: مرفوض بلا كتابة
    $this->actingAs($u)->put(route('admin.projects.tasks.update', $t), array_merge($payload, ['title' => '']))->assertSessionHasErrors('title');
    expect($t->fresh()->title)->toBe('مهمة');
});

test('leave request: known working-day count (Fri/Sat excluded), insufficient balance and invalid dates write nothing, unlinked users get 404', function () {
    $u = pvUser([\App\Models\Admin\Hr\LeaveRequest::class]);
    $this->actingAs($u)->get(route('admin.hr.leave-requests.create'))->assertNotFound(); // مستخدم غير مرتبط بموظف (السلوك الحالي)

    $emp = \App\Models\Admin\Hr\Employee::forceCreate(['employee_code' => 'L-1', 'first_name_ar' => 'موظف', 'last_name_ar' => 'إجازة', 'gender' => 'male', 'status' => 'active', 'children_count' => 0, 'user_id' => $u->id]);
    $type = \App\Models\Admin\Hr\LeaveType::create(['name_ar' => 'اعتيادية', 'annual_days' => 10]);
    \App\Models\Admin\Hr\LeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $type->id, 'year' => now()->year, 'total_days' => 3, 'used_days' => 0]);
    $this->actingAs($u)->get(route('admin.hr.leave-requests.create'))->assertOk()->assertSee('اعتيادية');

    $sunday = now()->next(\Carbon\Carbon::SUNDAY);
    // 7 أيام (أحد–سبت) = 5 أيام عمل > الرصيد 3: مرفوض بلا كتابة وتبقى المدخلات
    $this->actingAs($u)->from(route('admin.hr.leave-requests.create'))->post(route('admin.hr.leave-requests.store'), ['leave_type_id' => $type->id, 'start_date' => $sunday->toDateString(), 'end_date' => $sunday->copy()->addDays(6)->toDateString()])
        ->assertSessionHasErrors('leave_type_id');
    // نهاية قبل البداية
    $this->actingAs($u)->post(route('admin.hr.leave-requests.store'), ['leave_type_id' => $type->id, 'start_date' => $sunday->toDateString(), 'end_date' => $sunday->copy()->subDay()->toDateString()])->assertSessionHasErrors('end_date');
    // جمعة–سبت فقط: لا أيام عمل
    $friday = $sunday->copy()->addDays(5);
    $this->actingAs($u)->post(route('admin.hr.leave-requests.store'), ['leave_type_id' => $type->id, 'start_date' => $friday->toDateString(), 'end_date' => $friday->copy()->addDay()->toDateString()])->assertSessionHasErrors('end_date');
    expect(\App\Models\Admin\Hr\LeaveRequest::count())->toBe(0);

    // أحد–الثلاثاء = 3 أيام عمل تساوي الرصيد: يُحفظ بالقيم الصحيحة (pending)
    $this->actingAs($u)->post(route('admin.hr.leave-requests.store'), ['leave_type_id' => $type->id, 'start_date' => $sunday->toDateString(), 'end_date' => $sunday->copy()->addDays(2)->toDateString(), 'reason' => 'سفر'])->assertRedirect();
    $lr = \App\Models\Admin\Hr\LeaveRequest::sole();
    expect($lr->days_count)->toBe(3)->and($lr->employee_id)->toBe($emp->id)->and($lr->status)->toBe('pending')->and($lr->reason)->toBe('سفر')->and($lr->start_date->toDateString())->toBe($sunday->toDateString());
});
