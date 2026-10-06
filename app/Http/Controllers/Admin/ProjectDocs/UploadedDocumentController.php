<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
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
        $this->middleware('permission:' . self::MODEL . ',view')->only(['index', 'show', 'download']);
        $this->middleware('permission:' . self::MODEL . ',create')->only(['create', 'store']);
        $this->middleware('permission:' . self::MODEL . ',edit')->only(['edit', 'update']);
        $this->middleware('permission:' . self::MODEL . ',delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $scope = PermissionHelper::getEffectiveScope($user, self::MODEL);

        $base = fn () => $this->scoped(UploadedDocument::query(), $scope, $user);

        $category = $request->input('category');
        $category = $category && array_key_exists($category, UploadedDocument::CATEGORIES) ? $category : null;

        $query = $base()->with(['center', 'project', 'uploader'])
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($request->input('year'), fn ($q, $v) => $q->whereYear('document_date', $v))
            ->when($request->input('project_id'), fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->input('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->when($request->input('q'), function ($q, $v) {
                $q->where(function ($s) use ($v) {
                    $s->where('title', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%");
                });
            })
            ->latest('document_date')->latest('id');

        $documents = $query->paginate(15)->withQueryString();

        $tabCounts = $base()
            ->selectRaw('category, COUNT(*) c')
            ->groupBy('category')
            ->pluck('c', 'category');

        return view('admin.project-docs.uploaded.index', [
            'documents' => $documents,
            'tabCounts' => $tabCounts,
            'totalCount' => $base()->count(),
            'category' => $category,
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
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

        $document->load(['center', 'project', 'uploader']);

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
        $document->delete();

        return redirect()->route('admin.documents-archive.index')
            ->with('success', 'حُذفت الوثيقة (يمكن استرجاعها من سجل التدقيق إن لزم)');
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
