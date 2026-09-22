<?php

use App\Models\Admin\MonthlyReports\MonthlyReport;
use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use App\Models\User;

function pagesSuperAdmin(): User
{
    return User::factory()->create([
        'type' => 'super-admin',
        'must_change_password' => false,
    ]);
}

function monthlyTemplate(): MonthlyReportTemplate
{
    return MonthlyReportTemplate::create([
        'key' => 'smr-monthly',
        'title_ar' => 'نموذج تقرير شهري تجريبي',
        'version' => 1,
        'default_page_count' => 3,
        'is_active' => true,
        'json_definition' => [
            'header_meta' => ['تجريبي'],
            'sections' => [
                ['key' => 'intro', 'title' => 'مقدمة', 'type' => 'paragraph', 'page' => 1],
                ['key' => 'body', 'title' => 'المتن', 'type' => 'paragraph', 'page' => 2],
                ['key' => 'tail', 'title' => 'الخاتمة', 'type' => 'paragraph', 'page' => 5],
            ],
        ],
    ]);
}

it('inherits template default pages and clamps section pages on monthly report creation', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $this->actingAs($user)
        ->post(route('admin.monthly-reports.store'), [
            'template_id' => $template->id,
            'title' => 'تقرير اختباري',
            'page_count' => 3,
        ])
        ->assertStatus(302);

    $report = MonthlyReport::firstWhere('title', 'تقرير اختباري');
    expect($report->page_count)->toBe(3)
        ->and($report->blocks->firstWhere('block_key', 'intro')->page_number)->toBe(1)
        ->and($report->blocks->firstWhere('block_key', 'body')->page_number)->toBe(2)
        ->and($report->blocks->firstWhere('block_key', 'tail')->page_number)->toBe(3);
});

it('renders multi-page monthly report print with logo identity', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'تقرير طباعة',
        'status' => 'draft',
        'page_count' => 3,
        'created_by' => $user->id,
    ]);
    foreach ($template->sections() as $i => $section) {
        $report->blocks()->create([
            'block_key' => $section['key'],
            'page_number' => min(max(1, (int) ($section['page'] ?? 1)), 3),
            'json_value' => 'محتوى ' . $section['key'],
        ]);
    }

    $response = $this->actingAs($user)->get(route('admin.monthly-reports.print', $report));

    $response->assertStatus(200)
        ->assertSee('branding/logo.png', false)
        ->assertDontSee('Picture1.jpg')
        ->assertSee('صفحة 1 من 3')
        ->assertSee('صفحة 3 من 3')
        ->assertSee('الخاتمة');
});

it('moves a monthly report section to another page and updates page count', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'status' => 'draft',
        'page_count' => 3,
        'created_by' => $user->id,
    ]);
    foreach ($template->sections() as $section) {
        $report->blocks()->create([
            'block_key' => $section['key'],
            'page_number' => (int) ($section['page'] ?? 1),
        ]);
    }

    $this->actingAs($user)->put(route('admin.monthly-reports.update', $report), [
        'page_count' => '4',
        'blocks' => ['body' => ['paragraph' => 'نص محدث']],
        'lock' => ['body_page' => '4'],
    ])->assertStatus(302);

    $report->refresh();
    expect($report->page_count)->toBe(4)
        ->and($report->blocks->firstWhere('block_key', 'body')->page_number)->toBe(4)
        ->and($report->blocks->firstWhere('block_key', 'body')->json_value)->toBe('نص محدث');
});

it('inherits section pages from annex template on document creation', function () {
    $user = pagesSuperAdmin();

    $template = AnnexTemplate::create([
        'key' => 'smr-annex',
        'title_ar' => 'قالب اختباري',
        'version' => 1,
        'default_page_count' => 2,
        'is_active' => true,
        'json_definition' => [
            'sections' => [
                ['key' => 'a', 'title' => 'قسم أ', 'type' => 'paragraph', 'page' => 1],
                ['key' => 'b', 'title' => 'قسم ب', 'type' => 'paragraph', 'page' => 2],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('admin.project-docs.documents.store'), [
            'template_id' => $template->id,
            'title' => 'وثيقة اختبارية',
            'page_count' => 2,
        ])
        ->assertStatus(302);

    $document = AnnexDocument::firstWhere('title', 'وثيقة اختبارية');
    expect($document->page_count)->toBe(2)
        ->and($document->blocks->firstWhere('block_key', 'a')->page_number)->toBe(1)
        ->and($document->blocks->firstWhere('block_key', 'b')->page_number)->toBe(2);

    $this->actingAs($user)->get(route('admin.project-docs.documents.print', $document))
        ->assertStatus(200)
        ->assertSee('branding/logo.png', false)
        ->assertDontSee('Picture1.jpg')
        ->assertSee('صفحة 2 من 2');
});

it('keeps empty middle cells aligned when saving monthly report table rows', function () {
    $user = pagesSuperAdmin();
    $template = MonthlyReportTemplate::create([
        'key' => 'smr-monthly-table',
        'title_ar' => 'قالب جدول تجريبي',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => [
            'sections' => [
                ['key' => 'tbl', 'title' => 'جدول', 'type' => 'table', 'columns' => ['س1', 'س2', 'س3'], 'page' => 1],
            ],
        ],
    ]);

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'status' => 'draft',
        'page_count' => 1,
        'created_by' => $user->id,
    ]);
    $report->blocks()->create(['block_key' => 'tbl', 'page_number' => 1]);

    $this->actingAs($user)->put(route('admin.monthly-reports.update', $report), [
        'blocks' => ['tbl' => ['rows' => [
            ['a', '', 'c'],
            ['', '', ''],
            ['x', 'y', 'z'],
        ]]],
    ])->assertStatus(302);

    $report->refresh();
    $value = $report->blocks->firstWhere('block_key', 'tbl')->json_value;
    // ConvertEmptyStringsToNull middleware يحوّل '' إلى null — المهم أن يبقى الموضع (alignment) كما هو
    expect($value)->toBe([['a', null, 'c'], ['x', 'y', 'z']]);
});

it('serves the shared robust add-row script on both edit pages', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'status' => 'draft',
        'page_count' => 3,
        'created_by' => $user->id,
    ]);
    foreach ($template->sections() as $section) {
        $report->blocks()->create(['block_key' => $section['key'], 'page_number' => (int) ($section['page'] ?? 1)]);
    }

    $this->actingAs($user)->get(route('admin.monthly-reports.edit', $report))
        ->assertStatus(200)
        ->assertSee("closest('.card-body')", false)
        ->assertDontSee("closest('.table-responsive')", false);

    $annexTemplate = AnnexTemplate::create([
        'key' => 'smr-annex-edit',
        'title_ar' => 'قالب تحرير اختباري',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => [['key' => 'a', 'title' => 'أ', 'type' => 'table', 'columns' => ['س1', 'س2'], 'page' => 1]]],
    ]);
    $document = AnnexDocument::create([
        'template_id' => $annexTemplate->id,
        'template_version' => 1,
        'status' => 'draft',
        'page_count' => 1,
        'created_by' => $user->id,
    ]);
    $document->blocks()->create(['block_key' => 'a', 'page_number' => 1]);

    $this->actingAs($user)->get(route('admin.project-docs.documents.edit', $document))
        ->assertStatus(200)
        ->assertSee("closest('.card-body')", false)
        ->assertDontSee("closest('.table-responsive')", false);
});

it('duplicates a document with its content as a fresh editable draft', function () {
    $user = pagesSuperAdmin();
    $other = pagesSuperAdmin();

    $template = AnnexTemplate::create([
        'key' => 'smr-dup',
        'title_ar' => 'قالب نسخ',
        'version' => 1,
        'default_page_count' => 2,
        'is_active' => true,
        'json_definition' => [
            'sections' => [
                ['key' => 'intro', 'title' => 'مقدمة', 'type' => 'paragraph', 'page' => 1],
                ['key' => 'grid', 'title' => 'جدول', 'type' => 'table', 'columns' => ['أ', 'ب'], 'page' => 2],
            ],
        ],
    ]);

    $document = AnnexDocument::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'وثيقة أصلية',
        'status' => 'approved',
        'page_count' => 2,
        'created_by' => $other->id,
    ]);
    $document->blocks()->create(['block_key' => 'intro', 'page_number' => 1, 'json_value' => 'نص الأصلي', 'locked' => true]);
    $document->blocks()->create(['block_key' => 'grid', 'page_number' => 2, 'json_value' => [['1', '2']], 'locked' => true]);
    $document->signoffs()->create(['user_id' => $other->id, 'action' => 'approve']);

    $this->actingAs($user)
        ->post(route('admin.project-docs.documents.duplicate', $document))
        ->assertStatus(302);

    $copy = AnnexDocument::firstWhere('title', 'وثيقة أصلية — نسخة');
    expect($copy)->not->toBeNull()
        ->and($copy->status)->toBe('draft')
        ->and($copy->created_by)->toBe($user->id)
        ->and($copy->page_count)->toBe(2)
        ->and($copy->signoffs)->toHaveCount(0)
        ->and($copy->blocks->firstWhere('block_key', 'intro')->json_value)->toBe('نص الأصلي')
        ->and($copy->blocks->firstWhere('block_key', 'intro')->locked)->toBeFalse()
        ->and($copy->blocks->firstWhere('block_key', 'grid')->page_number)->toBe(2);
});

it('duplicates a monthly report with its content as a fresh editable draft', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'تقرير أصلي',
        'status' => 'under_review',
        'page_count' => 3,
        'created_by' => $user->id,
    ]);
    $report->blocks()->create(['block_key' => 'intro', 'page_number' => 1, 'json_value' => 'محتوى التقرير', 'locked' => true]);
    $report->blocks()->create(['block_key' => 'body', 'page_number' => 2]);
    $report->blocks()->create(['block_key' => 'tail', 'page_number' => 3]);

    $this->actingAs($user)
        ->post(route('admin.monthly-reports.duplicate', $report))
        ->assertStatus(302);

    $copy = MonthlyReport::firstWhere('title', 'تقرير أصلي — نسخة');
    expect($copy)->not->toBeNull()
        ->and($copy->status)->toBe('draft')
        ->and($copy->page_count)->toBe(3)
        ->and($copy->blocks)->toHaveCount(3)
        ->and($copy->blocks->firstWhere('block_key', 'intro')->json_value)->toBe('محتوى التقرير')
        ->and($copy->blocks->firstWhere('block_key', 'intro')->locked)->toBeFalse()
        ->and($copy->blocks->firstWhere('block_key', 'tail')->page_number)->toBe(3);
});

it('shows default page count for production templates after seeding', function () {
    $this->seed(\Database\Seeders\AnnexTemplatesSeeder::class);
    $this->seed(\Database\Seeders\MonthlyReportTemplatesSeeder::class);

    expect(AnnexTemplate::firstWhere('key', 'project-idea')->default_page_count)->toBe(2)
        ->and(AnnexTemplate::firstWhere('key', 'project-preliminary-study')->default_page_count)->toBe(6)
        ->and(AnnexTemplate::firstWhere('key', 'beneficiary-criteria')->default_page_count)->toBe(5)
        ->and(AnnexTemplate::firstWhere('key', 'project-card')->default_page_count)->toBe(1)
        ->and(AnnexTemplate::firstWhere('key', 'project-final-report')->default_page_count)->toBe(19)
        ->and(MonthlyReportTemplate::firstWhere('key', 'monthly-project-report')->default_page_count)->toBe(15);

    $final = AnnexTemplate::firstWhere('key', 'project-final-report');
    expect($final->sections()->firstWhere('key', 'closing')['page'])->toBe(19)
        ->and($final->sections()->firstWhere('key', 'project_card')['page'])->toBe(1);

    $monthly = MonthlyReportTemplate::firstWhere('key', 'monthly-project-report');
    expect($monthly->sections()->firstWhere('key', 'approvals')['page'])->toBe(15);
});
