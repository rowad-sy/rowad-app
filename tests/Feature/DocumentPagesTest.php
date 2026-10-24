<?php

use App\Models\Admin\MonthlyReports\MonthlyReport;
use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
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

it('moves a monthly report section to another page while any posted page count is ignored', function () {
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
        'page_count' => '99',
        'blocks' => ['body' => ['paragraph' => 'نص محدث']],
        'lock' => ['body_page' => '3'],
    ])->assertStatus(302);

    $report->refresh();
    expect($report->page_count)->toBe(3)
        ->and($report->blocks->firstWhere('block_key', 'body')->page_number)->toBe(3)
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
            'mode' => 'structured',
            'template_id' => $template->id,
            'title' => 'وثيقة اختبارية',
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

it('ignores any posted page_count on document store and update — template owns the count', function () {
    $user = pagesSuperAdmin();

    $template = AnnexTemplate::create([
        'key' => 'smr-fixed-pages',
        'title_ar' => 'قالب صفحات ثابتة',
        'version' => 1,
        'default_page_count' => 3,
        'is_active' => true,
        'json_definition' => [
            'sections' => [
                ['key' => 'a', 'title' => 'قسم أ', 'type' => 'paragraph', 'page' => 1],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('admin.project-docs.documents.store'), [
            'mode' => 'structured',
            'template_id' => $template->id,
            'title' => 'وثيقة بعدد مرفوض',
            'page_count' => 5,
        ])
        ->assertStatus(302);

    $document = AnnexDocument::firstWhere('title', 'وثيقة بعدد مرفوض');
    expect($document->page_count)->toBe(3);

    $this->actingAs($user)
        ->put(route('admin.project-docs.documents.update', $document), [
            'page_count' => 1,
            'blocks' => ['a' => ['paragraph' => 'نص']],
        ])
        ->assertStatus(302);

    expect($document->refresh()->page_count)->toBe(3);
});

it('attaches an A4 cover printed as the first page of an annex document, and removes it', function () {
    $user = pagesSuperAdmin();

    $template = AnnexTemplate::create([
        'key' => 'smr-cover-annex',
        'title_ar' => 'قالب غلاف',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => [['key' => 'a', 'title' => 'قسم أ', 'type' => 'paragraph', 'page' => 1]]],
    ]);

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'structured',
        'template_id' => $template->id,
        'title' => 'وثيقة بغلاف',
    ])->assertStatus(302);

    $document = AnnexDocument::firstWhere('title', 'وثيقة بغلاف');

    $this->actingAs($user)->put(route('admin.project-docs.documents.update', $document), [
        'cover_image' => \Illuminate\Http\UploadedFile::fake()->image('cover.jpg', 1240, 1754),
    ])->assertStatus(302);

    $document->refresh();
    expect($document->cover_path)->not->toBeNull()
        ->and($document->cover_path)->toStartWith('doc-covers/');

    $print = $this->actingAs($user)->get(route('admin.project-docs.documents.print', $document));
    $print->assertStatus(200)
        ->assertSee('print-sheet cover-sheet', false)
        ->assertSee(e('storage/'.$document->cover_path), false)
        ->assertSee('branding/logo.png', false); // صفحات المحتوى تحفظ ترويسة الشعار كما هي

    // الغلاف أول صفحة قبل المحتوى
    $html = $print->getContent();
    expect(strpos($html, 'print-sheet cover-sheet'))->toBeLessThan(strpos($html, 'class="sheet-head"'));

    $this->actingAs($user)->put(route('admin.project-docs.documents.update', $document), [
        'remove_cover' => '1',
    ])->assertStatus(302);
    expect($document->refresh()->cover_path)->toBeNull();

    $this->actingAs($user)->get(route('admin.project-docs.documents.print', $document))
        ->assertStatus(200)
        ->assertDontSee('print-sheet cover-sheet', false);
});

it('attaches an A4 cover printed as the first page of a monthly report', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();

    $this->actingAs($user)->post(route('admin.monthly-reports.store'), [
        'template_id' => $template->id,
        'title' => 'تقرير بغلاف',
    ])->assertStatus(302);

    $report = MonthlyReport::firstWhere('title', 'تقرير بغلاف');

    $this->actingAs($user)->put(route('admin.monthly-reports.update', $report), [
        'cover_image' => \Illuminate\Http\UploadedFile::fake()->image('cover.png', 1240, 1754),
    ])->assertStatus(302);

    $report->refresh();
    expect($report->cover_path)->not->toBeNull()->and($report->cover_path)->toStartWith('doc-covers/');

    $this->actingAs($user)->get(route('admin.monthly-reports.print', $report))
        ->assertStatus(200)
        ->assertSee('print-sheet cover-sheet', false)
        ->assertSee(e('storage/'.$report->cover_path), false);
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

it('lets a draft monthly report update its title and section spacing', function () {
    $user = pagesSuperAdmin();
    $template = monthlyTemplate();
    $report = MonthlyReport::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'العنوان السابق',
        'status' => 'draft',
        'page_count' => 3,
        'created_by' => $user->id,
    ]);
    foreach ($template->sections() as $section) {
        $report->blocks()->create(['block_key' => $section['key'], 'page_number' => (int) ($section['page'] ?? 1)]);
    }

    $this->actingAs($user)->put(route('admin.monthly-reports.update', $report), [
        'title' => 'العنوان الجديد',
        'section_spacing' => 'spacious',
    ])->assertRedirect();

    $report->refresh();
    expect($report->title)->toBe('العنوان الجديد')
        ->and($report->data['section_spacing'])->toBe('spacious');

    $this->actingAs($user)->get(route('admin.monthly-reports.show', $report))
        ->assertOk()
        ->assertSee('data-section-spacing="spacious"', false);
    $this->actingAs($user)->get(route('admin.monthly-reports.print', $report))
        ->assertOk()
        ->assertSee('data-section-spacing="spacious"', false);
});

it('lets a draft project document update its title', function () {
    $user = pagesSuperAdmin();
    $template = AnnexTemplate::create([
        'key' => 'smr-document-title',
        'title_ar' => 'قالب وثيقة تجريبي',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => [
            'sections' => [['key' => 'intro', 'title' => 'مقدمة', 'type' => 'paragraph', 'page' => 1]],
        ],
    ]);
    $document = AnnexDocument::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'الاسم السابق',
        'status' => 'draft',
        'page_count' => 1,
        'created_by' => $user->id,
    ]);
    $document->blocks()->create(['block_key' => 'intro', 'page_number' => 1]);

    $this->actingAs($user)->get(route('admin.project-docs.documents.edit', $document))
        ->assertOk()
        ->assertSee('name="title"', false)
        ->assertSee('value="الاسم السابق"', false);

    $this->actingAs($user)->put(route('admin.project-docs.documents.update', $document), [
        'title' => 'الاسم الجديد',
    ])->assertRedirect();

    expect($document->refresh()->title)->toBe('الاسم الجديد');
    $this->actingAs($user)->get(route('admin.project-docs.documents.show', $document))
        ->assertOk()
        ->assertSee('الاسم الجديد');
});

it('allows selecting and paginating project documents by page size', function () {
    $user = pagesSuperAdmin();
    $template = AnnexTemplate::create([
        'key' => 'smr-document-page-size',
        'title_ar' => 'قالب ترقيم الوثائق',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => []],
    ]);

    foreach (range(1, 27) as $number) {
        AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => 1,
            'title' => "وثيقة {$number}",
            'status' => 'draft',
            'page_count' => 1,
            'created_by' => $user->id,
        ]);
    }

    $this->actingAs($user)->get(route('admin.project-docs.documents.index', ['per_page' => 10]))
        ->assertOk()
        ->assertSee('name="per_page"', false)
        ->assertSee('value="10" selected', false)
        ->assertViewHas('documents', fn ($documents) => $documents->perPage() === 10 && $documents->count() === 10);

    $this->actingAs($user)->get(route('admin.project-docs.documents.index', ['per_page' => 10, 'page' => 2]))
        ->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->perPage() === 10
            && $documents->currentPage() === 2
            && $documents->count() === 10);

    $this->actingAs($user)->get(route('admin.project-docs.documents.index', ['per_page' => 999]))
        ->assertOk()
        ->assertViewHas('documents', fn ($documents) => $documents->perPage() === 15);
});

it('stores normalized reporting periods and filters documents by template and month range', function () {
    $user = pagesSuperAdmin();
    $template = AnnexTemplate::create([
        'key' => 'smr-period-filter',
        'title_ar' => 'قالب فترة اختبارية',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => []],
    ]);
    $otherTemplate = AnnexTemplate::create([
        'key' => 'smr-period-filter-other',
        'title_ar' => 'قالب آخر',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => []],
    ]);

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'structured',
        'template_id' => $template->id,
        'title' => 'وثيقة تغطي الربيع',
        'period_start' => '2025-04',
        'period_end' => '2025-06',
    ])->assertRedirect();

    $target = AnnexDocument::firstWhere('title', 'وثيقة تغطي الربيع');
    expect($target->period)->toBe('2025-04 — 2025-06')
        ->and($target->period_start->format('Y-m-d'))->toBe('2025-04-01')
        ->and($target->period_end->format('Y-m-d'))->toBe('2025-06-30');

    $this->actingAs($user)->get(route('admin.project-docs.documents.edit', $target))
        ->assertOk()
        ->assertSee('value="2025-04"', false)
        ->assertSee('value="2025-06"', false);

    $this->actingAs($user)->put(route('admin.project-docs.documents.update', $target), [
        'period_start' => '2025-04',
        'period_end' => '2025-06',
    ])->assertRedirect();

    expect($target->refresh()->period_end->format('Y-m-d'))->toBe('2025-06-30');

    foreach ([
        ['title' => 'وثيقة بعد الفترة', 'template_id' => $template->id, 'period' => '2025-07', 'start' => '2025-07-01', 'end' => '2025-07-31'],
        ['title' => 'قالب مختلف في الفترة', 'template_id' => $otherTemplate->id, 'period' => '2025-05', 'start' => '2025-05-01', 'end' => '2025-05-31'],
    ] as $record) {
        AnnexDocument::create([
            'template_id' => $record['template_id'],
            'template_version' => 1,
            'title' => $record['title'],
            'period' => $record['period'],
            'period_start' => $record['start'],
            'period_end' => $record['end'],
            'status' => 'draft',
            'page_count' => 1,
            'created_by' => $user->id,
        ]);
    }

    $this->actingAs($user)->get(route('admin.project-docs.documents.index', [
        'template_id' => $template->id,
        'period_from' => '2025-05',
        'period_to' => '2025-05',
    ]))
        ->assertOk()
        ->assertSee('وثيقة تغطي الربيع')
        ->assertDontSee('وثيقة بعد الفترة')
        ->assertDontSee('قالب مختلف في الفترة')
        ->assertViewHas('documents', fn ($documents) => $documents->total() === 1);
});

it('preserves all columns for dynamically added rows in project document output tables', function () {
    $user = pagesSuperAdmin();
    $template = AnnexTemplate::create([
        'key' => 'smr-output-table',
        'title_ar' => 'دراسة أولية تجريبية',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => [
            'sections' => [[
                'key' => 'design_outputs',
                'title' => 'المخرجات',
                'type' => 'table',
                'columns' => ['المخرج', 'المؤشر', 'وسيلة التحقق'],
                'page' => 1,
            ]],
        ],
    ]);
    $document = AnnexDocument::create([
        'template_id' => $template->id,
        'template_version' => 1,
        'title' => 'دراسة تجريبية',
        'status' => 'draft',
        'page_count' => 1,
        'created_by' => $user->id,
    ]);
    $document->blocks()->create(['block_key' => 'design_outputs', 'page_number' => 1]);

    $this->actingAs($user)->get(route('admin.project-docs.documents.edit', $document))
        ->assertOk()
        ->assertSee('data-next-row-index="1"', false)
        ->assertSee("const inputName = 'blocks[' + addRow.dataset.key + '][rows][' + rowIndex + ']';", false);

    $this->actingAs($user)->put(route('admin.project-docs.documents.update', $document), [
        'blocks' => ['design_outputs' => ['rows' => [
            ['المخرج الأول', 'مؤشر أول', 'تحقق أول'],
            ['المخرج الثاني', 'مؤشر ثان', 'تحقق ثان'],
        ]]],
    ])->assertRedirect();

    expect($document->refresh()->blocks->firstWhere('block_key', 'design_outputs')->json_value)->toBe([
        ['المخرج الأول', 'مؤشر أول', 'تحقق أول'],
        ['المخرج الثاني', 'مؤشر ثان', 'تحقق ثان'],
    ]);
    $this->actingAs($user)->get(route('admin.project-docs.documents.show', $document))
        ->assertOk()
        ->assertSee('مؤشر أول')
        ->assertSee('تحقق ثان');
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

/* -------------------------------------------------------------------- */
/*  حذف الوثيقة عبر نظام الصلاحيات                                       */
/* -------------------------------------------------------------------- */

function deletePermTemplate(): AnnexTemplate
{
    static $n = 0;
    $n++;

    return AnnexTemplate::create([
        'key' => 'del-tpl-'.$n,
        'title_ar' => 'قالب للحذف',
        'version' => 1,
        'default_page_count' => 1,
        'is_active' => true,
        'json_definition' => ['sections' => [['key' => 'a', 'title' => 'بند', 'type' => 'paragraph', 'page' => 1]]],
    ]);
}

function deletePermDoc(User $creator, ?int $projectId = null, string $status = 'draft'): AnnexDocument
{
    return AnnexDocument::create([
        'template_id' => deletePermTemplate()->id,
        'template_version' => 1,
        'status' => $status,
        'page_count' => 1,
        'project_id' => $projectId,
        'created_by' => $creator->id,
    ]);
}

it('lets a user with delete permission remove a draft they did not create', function () {
    $creator = pagesSuperAdmin();
    $deleter = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $deleter->id,
        'model_names' => [AnnexDocument::class],
        'can_view' => true,
        'can_delete' => true,
    ]);

    $document = deletePermDoc($creator);

    $this->actingAs($deleter)
        ->delete(route('admin.project-docs.documents.destroy', $document))
        ->assertRedirect(route('admin.project-docs.documents.index'));

    expect(AnnexDocument::find($document->id))->toBeNull();
});

it('blocks deletion for users without the delete permission', function () {
    $creator = pagesSuperAdmin();
    $user = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $user->id,
        'model_names' => [AnnexDocument::class],
        'can_view' => true,
    ]);

    $document = deletePermDoc($creator);

    $this->actingAs($user)
        ->delete(route('admin.project-docs.documents.destroy', $document))
        ->assertForbidden();

    expect(AnnexDocument::find($document->id))->not->toBeNull();
});

it('refuses to delete a document outside draft status even with permission', function () {
    $creator = pagesSuperAdmin();
    $deleter = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $deleter->id,
        'model_names' => [AnnexDocument::class],
        'can_view' => true,
        'can_delete' => true,
    ]);

    $document = deletePermDoc($creator, status: 'approved');

    $this->actingAs($deleter)
        ->from(route('admin.project-docs.documents.show', $document))
        ->delete(route('admin.project-docs.documents.destroy', $document))
        ->assertRedirect(route('admin.project-docs.documents.show', $document));

    expect(AnnexDocument::find($document->id))->not->toBeNull();
});

it('enforces the permission scope on deletion', function () {
    $creator = pagesSuperAdmin();
    $inside = Project::create(['name' => 'مشروع النطاق']);
    $outside = Project::create(['name' => 'مشروع خارج النطاق']);

    $scoped = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $scoped->id,
        'model_names' => [AnnexDocument::class],
        'project_id' => $inside->id,
        'can_view' => true,
        'can_delete' => true,
    ]);

    $allowed = deletePermDoc($creator, $inside->id);
    $denied = deletePermDoc($creator, $outside->id);

    $this->actingAs($scoped)
        ->from(route('admin.project-docs.documents.show', $denied))
        ->delete(route('admin.project-docs.documents.destroy', $denied))
        ->assertRedirect(route('admin.project-docs.documents.show', $denied));
    expect(AnnexDocument::find($denied->id))->not->toBeNull();

    $this->actingAs($scoped)
        ->delete(route('admin.project-docs.documents.destroy', $allowed))
        ->assertRedirect(route('admin.project-docs.documents.index'));
    expect(AnnexDocument::find($allowed->id))->toBeNull();
});
