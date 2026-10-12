<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\UploadedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/*
 * مكتبة الوثائق المرفوعة (PDF) — نظام مستقل عن وثائق المشاريع المُعبَّأة.
 * الفلترة عبر تبويبات التصنيف + سنة + مشروع/مركز + بحث نصي.
 */
class UploadedDocumentController extends Controller
{
    public const MODEL = UploadedDocument::class;

    public function __construct()
    {
        $this->middleware('permission:' . self::MODEL . ',view')->only(['show', 'download', 'convert']);
        $this->middleware('permission:' . self::MODEL . ',create')->only(['create', 'store']);
        $this->middleware('permission:' . self::MODEL . ',edit')->only(['edit', 'update']);
        $this->middleware('permission:' . self::MODEL . ',delete')->only(['destroy']);
    }

    public function create()
    {
        return view('admin.project-docs.uploaded.form', [
            'doc' => null,
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:' . implode(',', array_keys(UploadedDocument::CATEGORIES)),
            'document_date' => 'nullable|date',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'file' => 'required|file|mimes:pdf|max:25600',
        ], [
            'file.mimes' => 'المسموح رفع ملفات PDF فقط.',
            'file.max' => 'حجم الملف الأقصى 25 ميغابايت.',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            'document-archive',
            date('Y-m') . '-' . \Illuminate\Support\Str::random(10) . '.pdf',
            'public'
        );

        $doc = UploadedDocument::create($validated + [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.documents-archive.show', $doc)
            ->with('success', 'تم رفع الوثيقة إلى الأرشيف');
    }

    public function show(UploadedDocument $document)
    {
        $this->ensureVisible($document);

        $document->load(['center', 'project', 'uploader', 'annexDocument']);

        return view('admin.project-docs.uploaded.show', compact('document'));
    }

    public function download(UploadedDocument $document)
    {
        $this->ensureVisible($document);

        abort_unless(Storage::disk('public')->exists($document->file_path), 404, 'الملف غير موجود');

        return Storage::disk('public')->download($document->file_path, $document->file_name ?: ($document->title . '.pdf'));
    }

    public function edit(UploadedDocument $document)
    {
        $this->ensureVisible($document);

        return view('admin.project-docs.uploaded.form', [
            'doc' => $document,
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, UploadedDocument $document)
    {
        $this->ensureVisible($document);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:' . implode(',', array_keys(UploadedDocument::CATEGORIES)),
            'document_date' => 'nullable|date',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'file' => 'nullable|file|mimes:pdf|max:25600',
        ]);

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);
            $file = $request->file('file');
            $validated['file_path'] = $file->storeAs(
                'document-archive',
                date('Y-m') . '-' . \Illuminate\Support\Str::random(10) . '.pdf',
                'public'
            );
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        $document->update($validated);

        return redirect()->route('admin.documents-archive.show', $document)
            ->with('success', 'تم تحديث بيانات الوثيقة');
    }

    public function destroy(UploadedDocument $document)
    {
        $document->loadMissing('annexDocument');

        if ($document->annexDocument) {
            return back()->with('error', 'هذا الملف مرتبط بالوثيقة النظامية «' . ($document->annexDocument->title ?? '#'.$document->annex_document_id) . '» — احذف الوثيقة أو فكّ الربط أولاً.');
        }

        $document->delete();

        return redirect()->route('admin.documents-archive.index')
            ->with('success', 'حُذفت الوثيقة (يمكن استرجاعها من سجل التدقيق إن لزم)');
    }

    /*
     * تحويل ملف أرشيفي جاهز إلى وثيقة نظامية (ملف خارجي) تدخل دورة الاعتماد.
     * تتطلب صلاحية إنشاء وثائق نظامية، وتُطبَّق على نطاق مشروع الملف.
     */
    public function convert(UploadedDocument $document)
    {
        $this->ensureVisible($document);

        $user = auth()->user();

        if ($document->annex_document_id) {
            return back()->with('error', 'هذا الملف مرتبط أصلاً بوثيقة نظامية.');
        }

        $probe = new AnnexDocument(['project_id' => $document->project_id]);

        abort_unless(
            PermissionHelper::canAccessRecord($user, AnnexDocument::class, 'create', $probe),
            403,
            'تحتاج صلاحية إنشاء الوثائق النظامية ضمن نطاق هذا المشروع.'
        );

        $template = AnnexDocument::externalTemplate();

        $annex = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $document->title,
            'project_id' => $document->project_id,
            'status' => 'draft',
            'document_kind' => AnnexDocument::KIND_EXTERNAL,
            'page_count' => 1,
            'data' => null,
            'created_by' => $user->id,
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        $document->update(['annex_document_id' => $annex->id]);

        $annex->logWorkflow('create', null, 'تحويل ملف أرشيفي (' . $document->file_name . ') إلى وثيقة نظامية', 'draft');

        return redirect()->route('admin.project-docs.documents.show', $annex)
            ->with('success', 'تم التحويل — الوثيقة الآن مسودة قابلة للتقديم للاعتماد');
    }

    /* ------------------------------------------------------------ helpers */

    private function scoped($query, array $scope, $user)
    {
        if ($scope['sees_all'] || $user->type === 'super-admin') {
            return $query;
        }

        $centerIds = $scope['center_ids'] ?: [0];
        $projectIds = $scope['project_ids'] ?: [0];

        return $query->where(function ($q) use ($centerIds, $projectIds) {
            $q->whereNull('center_id')->whereNull('project_id')
                ->orWhereIn('center_id', $centerIds)
                ->orWhereIn('project_id', $projectIds);
        });
    }

    private function ensureVisible(UploadedDocument $document): void
    {
        $user = auth()->user();
        $scope = PermissionHelper::getEffectiveScope($user, self::MODEL);

        if ($scope['sees_all'] || $user->type === 'super-admin') {
            return;
        }

        $visible = $document->center_id === null && $document->project_id === null
            || in_array((int) $document->center_id, array_map('intval', $scope['center_ids']), true)
            || in_array((int) $document->project_id, array_map('intval', $scope['project_ids']), true);

        abort_if(! $visible, 403, 'هذه الوثيقة خارج نطاق صلاحياتك');
    }
}
