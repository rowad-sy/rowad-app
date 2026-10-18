<?php

use App\Models\Admin\MonthlyReports\MonthlyReport;
use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use App\Models\Admin\Permission;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use App\Models\User;

function refSuper(): User
{
    return User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
}

function refEmployee(): User
{
    return User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
}

function refGrantEdit(User $user, string $model): void
{
    Permission::create([
        'user_id' => $user->id,
        'model_names' => [$model],
        'can_view' => true,
        'can_edit' => true,
    ]);
}

function refTpl(): AnnexTemplate
{
    return AnnexTemplate::create([
        'key' => 'ref-tpl-' . uniqid(),
        'title_ar' => 'قالب الإحالة',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => [['key' => 'a', 'title' => 'قسم', 'type' => 'paragraph', 'page' => 1]]],
    ]);
}

function refDoc(User $creator, ?int $assignedTo = null, string $status = 'draft'): AnnexDocument
{
    $doc = AnnexDocument::create([
        'template_id' => refTpl()->id,
        'template_version' => 1,
        'title' => 'وثيقة إحالة',
        'status' => $status,
        'page_count' => 1,
        'assigned_to' => $assignedTo,
        'created_by' => $creator->id,
    ]);
    $doc->blocks()->create(['block_key' => 'a', 'page_number' => 1, 'json_value' => 'نص']);

    return $doc;
}

function refMonthlyDoc(User $creator, ?int $assignedTo = null, string $status = 'draft'): MonthlyReport
{
    $template = MonthlyReportTemplate::create([
        'key' => 'ref-mr-' . uniqid(),
        'title_ar' => 'قالب تقرير الإحالة',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => [
            'sections' => [['key' => 'a', 'title' => 'قسم', 'type' => 'paragraph', 'page' => 1]],
        ],
    ]);

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'تقرير إحالة',
        'status' => $status,
        'page_count' => 1,
        'assigned_to' => $assignedTo,
        'created_by' => $creator->id,
    ]);
    $report->blocks()->create(['block_key' => 'a', 'page_number' => 1, 'json_value' => 'نص']);

    return $report;
}

it('requires choosing a reviewer before submitting a document', function () {
    $creator = refEmployee();
    refGrantEdit($creator, AnnexDocument::class);
    $document = refDoc($creator);

    $this->actingAs($creator)
        ->post(route('admin.project-docs.documents.submit', $document))
        ->assertSessionHasErrors('reviewer_id');

    expect($document->refresh()->status)->toBe('draft')
        ->and($document->assigned_to)->toBeNull();
});

it('assigns the selected reviewer on submit', function () {
    $creator = refEmployee();
    refGrantEdit($creator, AnnexDocument::class);
    $reviewer = refEmployee();
    $document = refDoc($creator);

    $this->actingAs($creator)
        ->post(route('admin.project-docs.documents.submit', $document), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHasNoErrors();

    $document->refresh();
    expect($document->status)->toBe('under_review')
        ->and($document->assigned_to)->toBe($reviewer->id)
        ->and($document->blocks()->where('locked', true)->count())->toBe(1);
});

it('blocks approve, reject and comment for editors who are not the referred reviewer', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    $other = refEmployee();
    refGrantEdit($other, AnnexDocument::class);
    $document = refDoc($creator, $reviewer->id, 'under_review');

    foreach (['approve', 'reject', 'comment'] as $action) {
        $this->actingAs($other)
            ->post(route('admin.project-docs.documents.signoff', $document), [
                'action' => $action,
                'note' => 'محاولة',
            ])
            ->assertSessionHas('error');
    }

    expect($document->refresh()->status)->toBe('under_review')
        ->and($document->signoffs)->toHaveCount(0);
});

it('does not exempt super-admin from the referral rule but lets him reassign to himself', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    $admin = refSuper();
    $document = refDoc($creator, $reviewer->id, 'under_review');

    $this->actingAs($admin)
        ->post(route('admin.project-docs.documents.signoff', $document), [
            'action' => 'approve',
        ])
        ->assertSessionHas('error');
    expect($document->refresh()->status)->toBe('under_review');

    $this->actingAs($admin)
        ->post(route('admin.project-docs.documents.reassign', $document), [
            'reviewer_id' => $admin->id,
        ])
        ->assertSessionHas('success');
    expect($document->refresh()->assigned_to)->toBe($admin->id);

    $this->actingAs($admin)
        ->post(route('admin.project-docs.documents.signoff', $document), [
            'action' => 'approve',
            'note' => 'معتمد بعد الإحالة لنفسي',
        ]);
    expect($document->refresh()->status)->toBe('approved');
});

it('lets the referred reviewer approve or reject', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    refGrantEdit($reviewer, AnnexDocument::class);

    $approved = refDoc($creator, $reviewer->id, 'under_review');
    $this->actingAs($reviewer)
        ->post(route('admin.project-docs.documents.signoff', $approved), [
            'action' => 'approve',
            'note' => 'مطابق',
        ]);
    expect($approved->refresh()->status)->toBe('approved')
        ->and($approved->signoffs()->where('user_id', $reviewer->id)->count())->toBe(1);

    $rejected = refDoc($creator, $reviewer->id, 'under_review');
    $this->actingAs($reviewer)
        ->post(route('admin.project-docs.documents.signoff', $rejected), [
            'action' => 'reject',
            'note' => 'ناقص',
        ]);
    expect($rejected->refresh()->status)->toBe('rejected');
});

it('blocks legacy unassigned documents until super-admin refers them', function () {
    $creator = refEmployee();
    $admin = refSuper();
    $reviewer = refEmployee();
    refGrantEdit($reviewer, AnnexDocument::class);
    $document = refDoc($creator, null, 'under_review');

    $this->actingAs($admin)
        ->post(route('admin.project-docs.documents.signoff', $document), [
            'action' => 'approve',
        ])
        ->assertSessionHas('error');
    expect($document->refresh()->status)->toBe('under_review');

    $this->actingAs($admin)
        ->post(route('admin.project-docs.documents.reassign', $document), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHas('success');

    $this->actingAs($reviewer)
        ->post(route('admin.project-docs.documents.signoff', $document), [
            'action' => 'approve',
        ]);
    expect($document->refresh()->status)->toBe('approved');
});

it('restricts reassignment to the current reviewer or super-admin', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    $other = refEmployee();
    refGrantEdit($reviewer, AnnexDocument::class);
    refGrantEdit($other, AnnexDocument::class);
    $document = refDoc($creator, $reviewer->id, 'under_review');

    $this->actingAs($other)
        ->post(route('admin.project-docs.documents.reassign', $document), [
            'reviewer_id' => $other->id,
        ])
        ->assertSessionHas('error');
    expect($document->refresh()->assigned_to)->toBe($reviewer->id);

    $this->actingAs($reviewer)
        ->post(route('admin.project-docs.documents.reassign', $document), [
            'reviewer_id' => $creator->id,
        ])
        ->assertSessionHas('success');
    expect($document->refresh()->assigned_to)->toBe($creator->id);
});

it('clears the referral when a rejected document is reopened', function () {
    $creator = refEmployee();
    refGrantEdit($creator, AnnexDocument::class);
    $reviewer = refEmployee();
    $document = refDoc($creator, $reviewer->id, 'rejected');

    $this->actingAs($creator)
        ->post(route('admin.project-docs.documents.reopen', $document))
        ->assertSessionHas('success');

    $document->refresh();
    expect($document->status)->toBe('draft')
        ->and($document->assigned_to)->toBeNull();
});

it('lists every user in the referral dropdown on the document page', function () {
    $creator = refEmployee();
    refGrantEdit($creator, AnnexDocument::class);
    $noPermissionUser = User::factory()->create(['name' => 'مستخدم بلا صلاحيات', 'must_change_password' => false]);
    $document = refDoc($creator);

    $this->actingAs($creator)
        ->get(route('admin.project-docs.documents.show', $document))
        ->assertOk()
        ->assertSee('name="reviewer_id"', false)
        ->assertSee('مستخدم بلا صلاحيات');
});

it('applies the referral rule to monthly reports as well', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    $other = refEmployee();
    refGrantEdit($creator, MonthlyReport::class);
    refGrantEdit($reviewer, MonthlyReport::class);
    refGrantEdit($other, MonthlyReport::class);

    $report = refMonthlyDoc($creator);

    // إرسال بلا مُحال → مرفوض
    $this->actingAs($creator)
        ->post(route('admin.monthly-reports.submit', $report))
        ->assertSessionHasErrors('reviewer_id');
    expect($report->refresh()->status)->toBe('draft');

    // إرسال مع مُحال
    $this->actingAs($creator)
        ->post(route('admin.monthly-reports.submit', $report), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHasNoErrors();
    expect($report->refresh()->assigned_to)->toBe($reviewer->id);

    // غير المُحال إليه لا يعتمد ولا يعلق
    $this->actingAs($other)
        ->post(route('admin.monthly-reports.signoff', $report), [
            'action' => 'approve',
        ])
        ->assertSessionHas('error');
    expect($report->refresh()->status)->toBe('under_review');

    // المُحال إليه يعتمد
    $this->actingAs($reviewer)
        ->post(route('admin.monthly-reports.signoff', $report), [
            'action' => 'approve',
        ]);
    expect($report->refresh()->status)->toBe('approved');
});

it('keeps commenting open for editors on drafts but closes it for others during review', function () {
    $creator = refEmployee();
    $reviewer = refEmployee();
    $editor = refEmployee();
    refGrantEdit($editor, AnnexDocument::class);

    $draft = refDoc($creator);
    $this->actingAs($editor)
        ->post(route('admin.project-docs.documents.signoff', $draft), [
            'action' => 'comment',
            'note' => 'ملاحظة على المسودة',
        ]);
    expect($draft->refresh()->signoffs()->where('action', 'comment')->count())->toBe(1);

    $underReview = refDoc($creator, $reviewer->id, 'under_review');
    $this->actingAs($editor)
        ->post(route('admin.project-docs.documents.signoff', $underReview), [
            'action' => 'comment',
            'note' => 'ملاحظة أثناء المراجعة',
        ])
        ->assertSessionHas('error');
    expect($underReview->refresh()->signoffs)->toHaveCount(0);
});
