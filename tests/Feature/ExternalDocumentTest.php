<?php

use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\UploadedDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function extUser(array $flags = []): User
{
    $user = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);

    $defaults = ['view' => true, 'create' => true, 'edit' => true, 'delete' => false];

    foreach (['App\Models\Admin\ProjectDocs\AnnexDocument', 'App\Models\Admin\ProjectDocs\UploadedDocument'] as $model) {
        Permission::create(array_merge([
            'user_id' => $user->id,
            'model_names' => [$model],
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ], collect($flags + $defaults)->mapWithKeys(fn ($v, $k) => ['can_' . $k => $v])->all()));
    }

    return $user;
}

function extPdf(): UploadedFile
{
    return UploadedFile::fake()->create('تقرير-خارجي.pdf', 120, 'application/pdf');
}

it('creates an external PDF document that enters the full approval lifecycle', function () {
    Storage::fake('public');
    $user = extUser();
    $project = Project::create(['name' => 'مشروع الرصد البيئي']);

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'تقرير المسح الخارجي 2026',
        'project_id' => $project->id,
        'category' => 'technical',
        'document_date' => '2026-08-01',
        'file' => extPdf(),
    ])->assertRedirect();

    $document = AnnexDocument::firstWhere('title', 'تقرير المسح الخارجي 2026');

    expect($document)->not->toBeNull()
        ->and($document->document_kind)->toBe(AnnexDocument::KIND_EXTERNAL)
        ->and($document->status)->toBe('draft')
        ->and($document->isExternal())->toBeTrue();

    $linked = $document->sourceFile();
    expect($linked)->not->toBeNull()
        ->and($linked->category)->toBe('technical')
        ->and($linked->project_id)->toBe($project->id)
        ->and(Storage::disk('public')->exists($linked->file_path))->toBeTrue();

    // بلا أقسام — التقديم للمراجعة ينجح فوراً (مع تحديد المُحال إليه)
    $this->actingAs($user)->post(route('admin.project-docs.documents.submit', $document), [
        'reviewer_id' => $user->id,
    ])->assertRedirect();
    expect($document->refresh()->status)->toBe('under_review')
        ->and($document->assigned_to)->toBe($user->id);

    // الاعتماد يعمل كأي وثيقة
    $this->actingAs($user)->post(route('admin.project-docs.documents.signoff', $document), [
        'action' => 'approve',
        'note' => 'مطابق للأصل',
    ]);
    expect($document->refresh()->status)->toBe('approved')->and($document->signed_at)->not->toBeNull();
});

it('streams the original file instead of A4 print for external documents', function () {
    Storage::fake('public');
    $user = extUser();

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'مذكرة ممسوحة',
        'category' => 'correspondence',
        'file' => extPdf(),
    ]);

    $document = AnnexDocument::firstWhere('title', 'مذكرة ممسوحة');

    $this->actingAs($user)->get(route('admin.project-docs.documents.print', $document))->assertOk();
    $this->actingAs($user)->get(route('admin.project-docs.documents.file', $document))->assertOk();
});

it('blocks filling and duplication for external documents', function () {
    Storage::fake('public');
    $user = extUser();

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'عقد مورّد',
        'category' => 'legal',
        'file' => extPdf(),
    ]);

    $document = AnnexDocument::firstWhere('title', 'عقد مورّد');

    $this->actingAs($user)->get(route('admin.project-docs.documents.edit', $document))
        ->assertRedirect(route('admin.project-docs.documents.show', $document));

    $this->actingAs($user)->post(route('admin.project-docs.documents.duplicate', $document))
        ->assertSessionHas('error');

    expect(AnnexDocument::where('title', 'like', '%نسخة%')->count())->toBe(0);
});

it('validates the uploaded file in external mode', function () {
    Storage::fake('public');
    $user = extUser();

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'بلا ملف',
        'category' => 'other',
    ])->assertSessionHasErrors('file');

    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'ملف غير PDF',
        'category' => 'other',
        'file' => UploadedFile::fake()->create('x.docx', 10),
    ])->assertSessionHasErrors('file');

    expect(AnnexDocument::where('title', 'بلا ملف')->exists())->toBeFalse();
});

it('converts an existing archive file into a system document', function () {
    Storage::fake('public');
    $user = extUser(['delete' => true]);
    $project = Project::create(['name' => 'مشروع التدريب المهني']);

    $this->actingAs($user)->post(route('admin.documents-archive.store'), [
        'title' => 'محضر تسليم العيادة',
        'category' => 'administrative',
        'project_id' => $project->id,
        'file' => extPdf(),
    ]);

    $archived = UploadedDocument::firstWhere('title', 'محضر تسليم العيادة');
    expect($archived->annex_document_id)->toBeNull();

    $this->actingAs($user)->post(route('admin.documents-archive.convert', $archived))->assertRedirect();

    $archived->refresh();
    expect($archived->annex_document_id)->not->toBeNull();

    $annex = $archived->annexDocument;
    expect($annex->document_kind)->toBe(AnnexDocument::KIND_EXTERNAL)
        ->and($annex->status)->toBe('draft')
        ->and($annex->title)->toBe('محضر تسليم العيادة')
        ->and($annex->project_id)->toBe($project->id);

    // التحويل مرة أخرى مرفوض، والحذف محجوب أثناء الربط
    $this->actingAs($user)->post(route('admin.documents-archive.convert', $archived))->assertSessionHas('error');
    $this->actingAs($user)->delete(route('admin.documents-archive.destroy', $archived))->assertSessionHas('error');
    expect(UploadedDocument::find($archived->id))->not->toBeNull();
});

it('requires annex-create permission to convert and blocks out-of-scope conversion', function () {
    Storage::fake('public');

    $seed = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $farProject = Project::create(['name' => 'مشروع الأرشيف المركزي']);

    $this->actingAs($seed)->post(route('admin.documents-archive.store'), [
        'title' => 'ملف للنطاق',
        'category' => 'financial',
        'project_id' => $farProject->id,
        'file' => extPdf(),
    ]);
    $archived = UploadedDocument::firstWhere('title', 'ملف للنطاق');

    // (أ) مستخدم بلا صلاحية إنشاء وثائق → 403
    $noCreate = extUser(['create' => false]);
    $this->actingAs($noCreate)->post(route('admin.documents-archive.convert', $archived))->assertForbidden();
    expect($archived->refresh()->annex_document_id)->toBeNull();

    // (ب) صلاحية إنشاء مقيّدة على مشروع آخر → 403
    $scoped = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $scoped->id,
        'model_names' => ['App\Models\Admin\ProjectDocs\AnnexDocument'],
        'project_id' => Project::create(['name' => 'مشروع بعيد'])->id,
        'can_view' => true, 'can_create' => true,
    ]);
    Permission::create([
        'user_id' => $scoped->id,
        'model_names' => ['App\Models\Admin\ProjectDocs\UploadedDocument'],
        'can_view' => true,
    ]);

    $this->actingAs($scoped)->post(route('admin.documents-archive.convert', $archived))->assertForbidden();
    expect($archived->refresh()->annex_document_id)->toBeNull();
});

it('keeps the sentinel template out of the active templates dropdown', function () {
    $user = extUser();

    AnnexDocument::externalTemplate(); // يضمن الإنشاء التلقائي

    $this->actingAs($user)->get(route('admin.project-docs.documents.create'))
        ->assertOk()
        ->assertDontSee('وثيقة مرفوعة (ملف خارجي)');
});

/* -------------------------------------------------------------------- */
/*  الصفحة الموحّدة: وثائق النظام + أرشيف PDF في قائمة واحدة             */
/* -------------------------------------------------------------------- */

it('lists system documents and standalone archive files together in the unified page', function () {
    Storage::fake('public');
    $user = extUser();

    // وثيقة خارجية (ملف مرتبط بها)
    $this->actingAs($user)->post(route('admin.project-docs.documents.store'), [
        'mode' => 'external',
        'title' => 'وثيقة موحّدة-أ',
        'category' => 'technical',
        'file' => extPdf(),
    ]);

    // ملف أرشيف مستقل
    $this->actingAs($user)->post(route('admin.documents-archive.store'), [
        'title' => 'ملف أرشيف مستقل',
        'category' => 'financial',
        'file' => extPdf(),
    ]);

    $response = $this->actingAs($user)->get(route('admin.project-docs.documents.index'))
        ->assertOk()
        ->assertSee('وثيقة موحّدة-أ')
        ->assertSee('ملف أرشيف مستقل');

    expect($response->getContent())->toContain('ملف خارجي');

    // الملف المرتبط لا يتكرر كسطر أرشيف مستقل
    expect(substr_count($response->getContent(), 'وثيقة موحّدة-أ'))->toBe(1);
});

it('is reachable by archive-only viewers and hides the documents section', function () {
    Storage::fake('public');

    $seed = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
    $this->actingAs($seed)->post(route('admin.documents-archive.store'), [
        'title' => 'قرار اداري قديم',
        'category' => 'administrative',
        'file' => extPdf(),
    ]);

    $viewer = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $viewer->id,
        'model_names' => ['App\Models\Admin\ProjectDocs\UploadedDocument'],
        'can_view' => true,
    ]);

    $this->actingAs($viewer)->get(route('admin.project-docs.documents.index'))
        ->assertOk()
        ->assertSee('قرار اداري قديم')
        ->assertDontSee('وثيقة جديدة');
});

it('redirects the old archive index to the unified page preserving filters', function () {
    $user = extUser();

    $this->actingAs($user)->get('/admin/documents-archive?category=legal&year=2024')
        ->assertRedirect(route('admin.project-docs.documents.index', ['type' => 'archive', 'category' => 'legal', 'year' => 2024]));
});

it('renders public inline-view links for files on the unified page', function () {
    Storage::fake('public');
    $user = extUser();

    $this->actingAs($user)->post(route('admin.documents-archive.store'), [
        'title' => 'مسح ضوئي',
        'category' => 'other',
        'file' => extPdf(),
    ]);

    $path = UploadedDocument::first()->file_path;

    $this->actingAs($user)->get(route('admin.project-docs.documents.index'))
        ->assertOk()
        ->assertSee('storage/'.$path, false);
});
