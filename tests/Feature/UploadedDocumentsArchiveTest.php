<?php

use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\UploadedDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

const UDOC_MODEL = 'App\Models\Admin\ProjectDocs\UploadedDocument';

function udocEmployee(): User
{
    return User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
}

function udocGrant(User $user, array $flags = [], ?int $centerId = null): void
{
    Permission::create([
        'user_id' => $user->id,
        'model_names' => [UDOC_MODEL],
        'center_id' => $centerId,
        'can_view' => $flags['view'] ?? true,
        'can_create' => $flags['create'] ?? false,
        'can_edit' => $flags['edit'] ?? false,
        'can_delete' => $flags['delete'] ?? false,
    ]);
}

function udocPdf(): UploadedFile
{
    return UploadedFile::fake()->create('old-doc.pdf', 120, 'application/pdf');
}

test('UploadedDocs: upload a pdf appears in its tab and is viewable and downloadable', function () {
    Storage::fake('public');

    $user = udocEmployee();
    udocGrant($user, ['create' => true, 'edit' => true, 'delete' => true]);
    $center = Center::create(['name' => 'مركز اعزاز']);
    $project = Project::create(['name' => 'مشروع تعليم']);

    $this->actingAs($user)->post('/admin/documents-archive', [
        'title' => 'عقد إيجار 2023',
        'category' => 'legal',
        'document_date' => '2023-05-01',
        'center_id' => $center->id,
        'project_id' => $project->id,
        'file' => udocPdf(),
    ])->assertRedirect();

    $doc = UploadedDocument::first();
    expect($doc)->not->toBeNull()
        ->and($doc->category)->toBe('legal')
        ->and($doc->uploaded_by)->toBe($user->id)
        ->and(Storage::disk('public')->exists($doc->file_path))->toBeTrue();

    $this->actingAs($user)->get('/admin/documents-archive')
        ->assertOk()
        ->assertSee('قانونية وعقود')
        ->assertSee('عقد إيجار 2023');

    // التبويب المالي لا يعرضها
    $this->actingAs($user)->get('/admin/documents-archive?category=financial')
        ->assertOk()
        ->assertDontSee('عقد إيجار 2023');

    $this->actingAs($user)->get('/admin/documents-archive/' . $doc->id)
        ->assertOk()
        ->assertSee('عقد إيجار 2023');

    $this->actingAs($user)->get('/admin/documents-archive/' . $doc->id . '/download')
        ->assertOk()
        ->assertDownload('old-doc.pdf');
});

test('UploadedDocs: permission gates block non-holders entirely', function () {
    Storage::fake('public');

    $user = udocEmployee();
    $noBody = udocEmployee();

    $this->actingAs($noBody)->get('/admin/documents-archive')->assertStatus(403);
    $this->actingAs($noBody)->post('/admin/documents-archive', [])->assertStatus(403);

    $this->actingAs($user);
    udocGrant($user, ['create' => true]);

    $this->actingAs($user)->post('/admin/documents-archive', [
        'title' => 'قرار',
        'category' => 'administrative',
        'file' => udocPdf(),
    ])->assertRedirect();

    $doc = UploadedDocument::first();

    // بلا صلاحية حذف لا يحذف
    $this->actingAs($user)->delete('/admin/documents-archive/' . $doc->id)->assertStatus(403);
    expect(UploadedDocument::count())->toBe(1);
});

test('UploadedDocs: center-scoped viewers only see their center and general docs', function () {
    Storage::fake('public');

    $admin = udocEmployee();
    udocGrant($admin, ['create' => true]);

    $c1 = Center::create(['name' => 'مركز عفرين']);
    $c2 = Center::create(['name' => 'مركز اعزاز']);

    $this->actingAs($admin)->post('/admin/documents-archive', ['title' => 'وثيقة عفرين', 'category' => 'financial', 'center_id' => $c1->id, 'file' => udocPdf()]);
    $this->actingAs($admin)->post('/admin/documents-archive', ['title' => 'وثيقة اعزاز', 'category' => 'financial', 'center_id' => $c2->id, 'file' => udocPdf()]);
    $this->actingAs($admin)->post('/admin/documents-archive', ['title' => 'وثيقة عامة', 'category' => 'financial', 'file' => udocPdf()]);

    $viewer = udocEmployee();
    udocGrant($viewer, ['view' => true], $c1->id); // مركز واحد

    $this->actingAs($viewer)->get('/admin/documents-archive?category=financial')
        ->assertOk()
        ->assertSee('وثيقة عفرين')
        ->assertSee('وثيقة عامة')
        ->assertDontSee('وثيقة اعزاز');

    $other = UploadedDocument::where('title', 'وثيقة اعزاز')->first();
    $this->actingAs($viewer)->get('/admin/documents-archive/' . $other->id)->assertStatus(403);
});

test('UploadedDocs: pdf-only validation rejects other files', function () {
    Storage::fake('public');

    $user = udocEmployee();
    udocGrant($user, ['create' => true]);

    $this->actingAs($user)->post('/admin/documents-archive', [
        'title' => 'خطأ',
        'category' => 'other',
        'file' => UploadedFile::fake()->create('doc.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ])->assertSessionHasErrors('file');

    expect(UploadedDocument::count())->toBe(0);
});
