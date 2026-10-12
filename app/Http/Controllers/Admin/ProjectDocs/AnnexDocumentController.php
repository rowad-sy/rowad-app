<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\AnnexDocumentBlock;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use App\Models\Admin\ProjectDocs\UploadedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnexDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,view')->only(['show', 'printDocument', 'file']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,create')->only(['create', 'store', 'duplicate']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,edit')->only(['edit', 'update', 'submit', 'reopen', 'signoff']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,delete')->only(['destroy']);
    }

    /*
     * صفحة الوثائق الموحّدة: ملحقات نظامية (منظّمة/خارجية) + ملفات الأرشيف غير المرتبطة
     * في قائمة واحدة بالشارات والفلاتر. الوصول: صلاحية عرض لأيٍّ من المصدرين.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $canDocs = $user->type === 'super-admin' || PermissionHelper::can($user, AnnexDocument::class, 'view');
        $canArchive = $user->type === 'super-admin' || PermissionHelper::can($user, UploadedDocument::class, 'view');

        abort_unless($canDocs || $canArchive, 403, 'ليس لديك صلاحية على الوثائق.');

        $filters = [
            'type' => in_array($request->input('type'), ['structured', 'external', 'archive'], true) ? $request->input('type') : null,
            'status' => array_key_exists((string) $request->input('status'), AnnexDocument::STATUSES) ? $request->input('status') : null,
            'category' => array_key_exists((string) $request->input('category'), UploadedDocument::CATEGORIES) ? $request->input('category') : null,
            'project_id' => $request->input('project_id') ? (int) $request->input('project_id') : null,
            'center_id' => $request->input('center_id') ? (int) $request->input('center_id') : null,
            'year' => preg_match('/^\d{4}$/', (string) $request->input('year')) ? (int) $request->input('year') : null,
            'q' => trim((string) $request->input('q')) ?: null,
        ];

        $rows = collect();

        // ===== وثائق النظام (منظّمة/خارجية) =====
        if ($canDocs && $filters['type'] !== 'archive') {
            $scope = PermissionHelper::getEffectiveScope($user, AnnexDocument::class);

            $documents = AnnexDocument::with(['template', 'project', 'creator', 'linkedFiles'])
                ->when(! $scope['sees_all'], fn ($q) => $q->whereIn('project_id', $scope['project_ids']))
                ->when($filters['type'], fn ($q, $v) => $q->where('document_kind', $v))
                ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
                ->when($filters['project_id'], fn ($q, $v) => $q->where('project_id', $v))
                ->when($filters['year'], fn ($q, $v) => $q->whereYear('created_at', $v))
                ->when($filters['q'], fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
                ->get();

            $rows = $rows->merge($documents->map(fn ($document) => [
                'kind' => 'document',
                'source' => $document->isExternal() ? 'external' : 'structured',
                'model' => $document,
                'title' => $document->title ?: ($document->template?->title_ar ?? 'وثيقة'),
                'subtitle' => $document->isExternal() ? 'ملف مرفوع (PDF)' : ($document->template?->title_ar ?? '—'),
                'project' => $document->project?->name,
                'center' => null,
                'when' => $document->updated_at,
                'person' => $document->creator?->name,
                'file' => $document->isExternal() ? $document->linkedFiles->sortByDesc('id')->first() : null,
            ])->all());
        }

        // ===== ملفات الأرشيف المستقلة (غير المرتبطة بوثيقة) =====
        // فلتر الحالة لا حياة دورته للملفات المستقلة — يُهدأ الأرشيف حينها
        if ($canArchive && in_array($filters['type'], [null, 'archive'], true) && ! $filters['status']) {
            $rows = $rows->merge($this->unifiedArchiveRows($user, $filters));
        }

        $rows = $rows
            ->sortByDesc(fn ($row) => $row['when'] ? $row['when']->timestamp : 0)
            ->values();

        $page = max(1, (int) $request->get('page', 1));
        $perPage = 15;

        $documents = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.project-docs.documents.index', [
            'documents' => $documents,
            'rows' => $rows,
            'filters' => $filters,
            'canDocs' => $canDocs,
            'canArchive' => $canArchive,
            'projects' => Project::orderBy('name')->get(),
            'centers' => \App\Models\Admin\Center::orderBy('name')->get(),
            'categories' => UploadedDocument::CATEGORIES,
            'typeCounts' => $this->unifiedTypeCounts($user, $canDocs, $canArchive),
        ]);
    }

    private function unifiedArchiveRows($user, array $filters): array
    {
        $scope = PermissionHelper::getEffectiveScope($user, UploadedDocument::class);

        return UploadedDocument::with(['project', 'center', 'uploader'])
            ->whereNull('annex_document_id')
            ->when(! $scope['sees_all'] && $user->type !== 'super-admin', function ($q) use ($scope) {
                $centerIds = $scope['center_ids'] ?: [0];
                $projectIds = $scope['project_ids'] ?: [0];

                $q->where(function ($w) use ($centerIds, $projectIds) {
                    $w->whereNull('center_id')->whereNull('project_id')
                        ->orWhereIn('center_id', $centerIds)
                        ->orWhereIn('project_id', $projectIds);
                });
            })
            ->when($filters['category'], fn ($q, $v) => $q->where('category', $v))
            ->when($filters['project_id'], fn ($q, $v) => $q->where('project_id', $v))
            ->when($filters['center_id'], fn ($q, $v) => $q->where('center_id', $v))
            ->when($filters['year'], fn ($q, $v) => $q->whereYear('document_date', $v))
            ->when($filters['q'], function ($q, $v) {
                $q->where(fn ($w) => $w->where('title', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%"));
            })
            ->get()
            ->map(fn ($file) => [
                'kind' => 'archive',
                'source' => 'archive',
                'model' => $file,
                'title' => $file->title,
                'subtitle' => $file->categoryLabel(),
                'project' => $file->project?->name,
                'center' => $file->center?->name,
                'when' => $file->document_date ?? $file->created_at,
                'person' => $file->uploader?->name,
                'file' => $file,
            ])
            ->all();
    }

    private function unifiedTypeCounts($user, bool $canDocs, bool $canArchive): array
    {
        $counts = ['all' => 0, 'structured' => 0, 'external' => 0, 'archive' => 0];

        if ($canDocs) {
            $scope = PermissionHelper::getEffectiveScope($user, AnnexDocument::class);
            $base = fn () => AnnexDocument::query()->when(! $scope['sees_all'], fn ($q) => $q->whereIn('project_id', $scope['project_ids']));

            $counts['structured'] = (clone $base())->where('document_kind', 'structured')->count();
            $counts['external'] = (clone $base())->where('document_kind', 'external')->count();
        }

        if ($canArchive) {
            $scope = PermissionHelper::getEffectiveScope($user, UploadedDocument::class);
            $q = UploadedDocument::query()->whereNull('annex_document_id');

            if (! $scope['sees_all'] && $user->type !== 'super-admin') {
                $centerIds = $scope['center_ids'] ?: [0];
                $projectIds = $scope['project_ids'] ?: [0];
                $q->where(function ($w) use ($centerIds, $projectIds) {
                    $w->whereNull('center_id')->whereNull('project_id')
                        ->orWhereIn('center_id', $centerIds)
                        ->orWhereIn('project_id', $projectIds);
                });
            }

            $counts['archive'] = $q->count();
        }

        $counts['all'] = array_sum([$counts['structured'], $counts['external'], $counts['archive']]);

        return $counts;
    }

    public function help()
    {
        return view('admin.project-docs.documents.help');
    }

    public function create()
    {
        $templates = AnnexTemplate::where('is_active', true)->orderBy('title_ar')->get();
        $projects = Project::orderBy('name')->get();
        $categories = UploadedDocument::CATEGORIES;

        return view('admin.project-docs.documents.form', compact('templates', 'projects', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', \Illuminate\Validation\Rule::in(['structured', 'external'])],
            'template_id' => 'required_if:mode,structured|nullable|exists:annex_templates,id',
            'title' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'period' => 'nullable|string|max:20',
            'page_count' => 'required_if:mode,structured|nullable|integer|min:1|max:60',
            'category' => 'required_if:mode,external|nullable|string|in:' . implode(',', array_keys(UploadedDocument::CATEGORIES)),
            'document_date' => 'nullable|date',
            'file' => 'required_if:mode,external|nullable|file|mimes:pdf|max:25600',
        ], [
            'file.required_if' => 'الملف مطلوب للوثيقة الخارجية.',
            'file.mimes' => 'يسمح بملفات PDF فقط.',
            'file.max' => 'حجم الملف يتجاوز 25 ميجابايت.',
            'category.required_if' => 'التصنيف مطلوب للوثيقة الخارجية.',
        ]);

        if ($validated['mode'] === 'external') {
            return $this->storeExternal($request, $validated);
        }

        $template = AnnexTemplate::findOrFail($validated['template_id']);
        $pageCount = (int) $validated['page_count'];

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'status' => 'draft',
            'document_kind' => AnnexDocument::KIND_STRUCTURED,
            'page_count' => $pageCount,
            'data' => null,
            'created_by' => auth()->id(),
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        foreach ($template->sections() as $section) {
            $document->blocks()->create([
                'block_key' => $section['key'] ?? null,
                'page_number' => max(1, min((int) ($section['page'] ?? 1), $pageCount)),
                'json_value' => null,
                'updated_by' => null,
                'locked' => false,
            ]);
        }

        $document->logWorkflow('create', null, 'تم إنشاء وثيقة من قالب: ' . $template->title_ar, 'draft');

        return redirect()->route('admin.project-docs.documents.edit', $document)
            ->with('success', 'تم إنشاء الوثيقة — ابدأ بتعبئة أقسامها');
    }

    /*
     * وثيقة ملف خارجي: تُنشأ كمستند دورة حياة (AnnexDocument) بلا أقسام،
     * ويُحفظ ملفها في الأرشيف (uploaded_documents) مرتبطاً بها.
     */
    private function storeExternal(Request $request, array $validated)
    {
        $template = AnnexDocument::externalTemplate();

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? $template->title_ar,
            'project_id' => $validated['project_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'status' => 'draft',
            'document_kind' => AnnexDocument::KIND_EXTERNAL,
            'page_count' => 1,
            'data' => null,
            'created_by' => auth()->id(),
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            'document-archive',
            date('Y-m') . '-' . \Illuminate\Support\Str::random(10) . '.pdf',
            'public'
        );

        $archived = UploadedDocument::create([
            'title' => $validated['title'] ?? $template->title_ar,
            'description' => 'وثيقة ملف خارجي مرتبطة بالوثيقة النظامية',
            'category' => $validated['category'],
            'document_date' => $validated['document_date'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'annex_document_id' => $document->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        $document->logWorkflow('create', null, 'إنشاء وثيقة ملف خارجي (' . $archived->file_name . ')', 'draft');

        return redirect()->route('admin.project-docs.documents.show', $document)
            ->with('success', 'تم إنشاء الوثيقة من الملف المرفوع');
    }

    public function duplicate(AnnexDocument $document)
    {
        if ($document->isExternal()) {
            return back()->with('error', 'لا يمكن نسخ وثيقة ملف خارجي — أنشئ وثيقة جديدة بملفها');
        }

        $pageCount = max(1, (int) $document->page_count);
        $template = $document->template;

        $copy = AnnexDocument::create([
            'template_id' => $document->template_id,
            'template_version' => $document->template_version,
            'title' => trim(($document->title ?: ($template?->title_ar ?? 'وثيقة')) . ' — نسخة'),
            'project_id' => $document->project_id,
            'period' => $document->period,
            'status' => 'draft',
            'page_count' => $pageCount,
            'data' => $document->data,
            'cover_path' => $document->cover_path,
            'created_by' => auth()->id(),
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        $copiedKeys = [];
        foreach ($document->blocks as $block) {
            $copy->blocks()->create([
                'block_key' => $block->block_key,
                'page_number' => max(1, min((int) $block->page_number, $pageCount)),
                'json_value' => $block->json_value,
                'updated_by' => auth()->id(),
                'locked' => false,
            ]);
            $copiedKeys[] = $block->block_key;
        }

        // أقسام أُضيفت على القالب بعد إنشاء الوثيقة الأصلية — تُنسخ كفراغات
        foreach ($template->sections() as $section) {
            if (! in_array($section['key'] ?? null, $copiedKeys, true)) {
                $copy->blocks()->create([
                    'block_key' => $section['key'] ?? null,
                    'page_number' => max(1, min((int) ($section['page'] ?? 1), $pageCount)),
                    'json_value' => null,
                    'updated_by' => null,
                    'locked' => false,
                ]);
            }
        }

        $copy->logWorkflow('create', null, 'نسخة من الوثيقة #' . $document->id . ' قابلة للتعديل', 'draft');

        return redirect()->route('admin.project-docs.documents.edit', $copy)
            ->with('success', 'تم إنشاء نسخة من الوثيقة — عدّلها كما تريد');
    }

    public function show(AnnexDocument $document)
    {
        $document->load(['template', 'project', 'creator', 'assignee', 'blocks.updater', 'signoffs.user', 'workflowActions.fromUser', 'workflowActions.toUser', 'linkedFiles']);

        return view('admin.project-docs.documents.show', compact('document'));
    }

    public function edit(AnnexDocument $document)
    {
        if ($document->isExternal()) {
            return redirect()->route('admin.project-docs.documents.show', $document)
                ->with('error', 'وثيقة الملف الخارجي لا تحتوي أقساماً للتعبئة — تُدار من صفحة العرض');
        }

        if (! in_array($document->status, ['draft', 'rejected'], true)) {
            return redirect()->route('admin.project-docs.documents.show', $document)
                ->with('error', 'تعبئة الأقسام متاحة فقط للمسودات أو الوثائق المعاد فتحها');
        }

        $document->load(['template', 'project', 'creator', 'blocks']);

        return view('admin.project-docs.documents.edit', compact('document'));
    }

    public function update(Request $request, AnnexDocument $document)
    {
        if (in_array($document->status, ['under_review', 'approved'], true)) {
            return back()->with('error', 'الوثيقة ليست في حالة قابلة للتعديل حالياً');
        }

        $user = auth()->user();
        $isCreator = $user->id === (int) $document->created_by;

        $coverInput = $request->validate([
            'cover_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
            'remove_cover' => 'nullable|boolean',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($document->cover_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($document->cover_path);
            }
            $document->update(['cover_path' => $request->file('cover_image')->store('doc-covers', 'public')]);
        } elseif (! empty($coverInput['remove_cover']) && $document->cover_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->cover_path);
            $document->update(['cover_path' => null]);
        }

        $pageCount = (int) $document->page_count;
        if ($request->filled('page_count')) {
            $validatedCount = $request->validate([
                'page_count' => 'integer|min:1|max:60',
            ])['page_count'];
            $pageCount = (int) $validatedCount;
            $document->update(['page_count' => $pageCount]);
        }

        $blocksInput = $request->input('blocks', []);
        $locksInput = $request->input('lock', []);
        $sections = $document->sections();

        foreach ($sections as $section) {
            $key = $section['key'];
            $block = $document->blocks->firstWhere('block_key', $key);

            $editable = ! $block?->locked || $isCreator;
            if (! $editable) {
                continue;
            }

            $value = $this->extractValue($section['type'] ?? 'paragraph', $blocksInput[$key] ?? []);
            $shouldLock = ! empty($locksInput[$key]);
            $pageNumber = max(1, min((int) ($locksInput[$key . '_page'] ?? ($block?->page_number ?? 1)), $pageCount));

            if ($block) {
                $block->update([
                    'json_value' => $value,
                    'page_number' => $pageNumber,
                    'updated_by' => $user->id,
                    'locked' => $shouldLock,
                ]);
            }
        }

        $document->logWorkflow('update', null, 'تحديث محتوى الأقسام');

        return back()->with('success', 'تم حفظ الأقسام');
    }

    public function submit(AnnexDocument $document)
    {
        $missing = $document->missingSections();

        if (! empty($missing)) {
            return back()->with('error', 'أقسام ناقصة قبل الإرسال: ' . implode('، ', $missing));
        }

        $document->blocks()->update(['locked' => true]);
        $document->update(['status' => 'under_review']);
        $document->logWorkflow('submit', null, 'أُرسلت الوثيقة للمراجعة', 'under_review');

        return back()->with('success', 'أُرسلت الوثيقة للمراجعة — أقسامها مقفلة الآن');
    }

    public function reopen(AnnexDocument $document)
    {
        if ($document->status !== 'rejected') {
            return back()->with('error', 'إعادة الفتح متاحة فقط للوثائق المرفوضة');
        }

        $document->blocks()->update(['locked' => false]);
        $document->update(['status' => 'draft', 'signed_at' => null]);
        $document->logWorkflow('reopen', null, 'أُعيد فتح الوثيقة للتعديل', 'draft');

        return redirect()->route('admin.project-docs.documents.edit', $document)
            ->with('success', 'أُعيد فتح الوثيقة للتعديل');
    }

    public function signoff(Request $request, AnnexDocument $document)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,comment',
            'note' => 'nullable|string|max:2000',
        ]);

        if (in_array($validated['action'], ['approve', 'reject'], true) && $document->status !== 'under_review') {
            return back()->with('error', 'الاعتماد أو الرفض متاح فقط للوثائق قيد المراجعة');
        }

        if ($document->isApproved()) {
            return back()->with('error', 'الوثيقة معتمدة بالفعل');
        }

        $role = auth()->user()->jobTitle?->title_ar;

        $document->signoffs()->create([
            'user_id' => auth()->id(),
            'role_label' => $role,
            'action' => $validated['action'],
            'note' => $validated['note'] ?? null,
        ]);

        if ($validated['action'] === 'approve') {
            $document->blocks()->update(['locked' => true]);
            $document->update(['status' => 'approved', 'signed_at' => now()]);
            $document->logWorkflow('approve', null, $validated['note'] ?? null, 'approved');

            return back()->with('success', 'تم اعتماد الوثيقة نهائياً');
        }

        if ($validated['action'] === 'reject') {
            $document->update(['status' => 'rejected']);
            $document->logWorkflow('reject', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'تم رفض الوثيقة — أعد فتحها للتعديل من صفحتها');
        }

        $document->logWorkflow('comment', null, $validated['note'] ?? null);

        return back()->with('success', 'تمت إضافة الملاحظة');
    }

    public function printDocument(AnnexDocument $document)
    {
        if ($document->isExternal()) {
            return $this->file($document);
        }

        $document->load(['template', 'project', 'creator', 'signoffs.user', 'blocks.updater']);

        return view('admin.project-docs.documents.print', compact('document'));
    }

    /*
     * تدفّق ملف الوثيقة الخارجية — بصلاحية عرض الوثيقة النظامية نفسها (لا نمرّ بالأرشيف).
     */
    public function file(AnnexDocument $document)
    {
        abort_unless($document->isExternal(), 404, 'هذه الوثيقة بلا ملف خارجي.');

        $source = $document->sourceFile();

        abort_unless($source && Storage::disk('public')->exists($source->file_path), 404, 'الملف الأصلي غير متاح.');

        return Storage::disk('public')->download($source->file_path, $source->file_name ?: ($document->title . '.pdf'));
    }

    public function destroy(AnnexDocument $document)
    {
        $user = auth()->user();

        if ($document->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف وثيقة خارج حالة المسودة');
        }

        if (! PermissionHelper::canAccessRecord($user, AnnexDocument::class, 'delete', $document)) {
            return back()->with('error', 'لا تملك صلاحية حذف هذه الوثيقة أو أنها خارج نطاق صلاحياتك');
        }

        $document->delete();

        return redirect()->route('admin.project-docs.documents.index')
            ->with('success', 'تم حذف الوثيقة');
    }

    private function extractValue(string $type, array $input): mixed
    {
        return match ($type) {
            'fields' => collect($input['fields'] ?? [])->filter(fn ($v) => trim((string) $v) !== '')->all(),
            'table' => collect($input['rows'] ?? [])
                ->map(fn ($row) => array_values($row ?? []))
                ->filter(fn ($row) => collect($row)->contains(fn ($v) => trim((string) $v) !== ''))
                ->values()
                ->all(),
            'list' => collect($input['items'] ?? [])->filter(fn ($v) => trim((string) $v) !== '')->values()->all(),
            default => trim((string) ($input['paragraph'] ?? '')),
        };
    }
}