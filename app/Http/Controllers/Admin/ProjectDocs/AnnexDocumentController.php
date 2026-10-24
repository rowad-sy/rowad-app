<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectPath;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\AnnexDocumentBlock;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use App\Models\Admin\ProjectDocs\UploadedDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnexDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,view')->only(['show', 'printDocument', 'file']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,create')->only(['create', 'store', 'duplicate']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,edit')->only(['edit', 'update', 'submit', 'reopen', 'signoff', 'reassign']);
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
            'path_id' => $request->input('path_id') ? (int) $request->input('path_id') : null,
            'center_id' => $request->input('center_id') ? (int) $request->input('center_id') : null,
            'year' => preg_match('/^\d{4}$/', (string) $request->input('year')) ? (int) $request->input('year') : null,
            'period_from' => preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->input('period_from')) ? $request->input('period_from') : null,
            'period_to' => preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->input('period_to')) ? $request->input('period_to') : null,
            'template_id' => $request->input('template_id') ? (int) $request->input('template_id') : null,
            'q' => trim((string) $request->input('q')) ?: null,
        ];

        if ($filters['template_id']) {
            $filters['type'] = 'structured';
            $filters['category'] = null;
        }
        if ($filters['status'] && $filters['type'] === null) {
            $filters['type'] = 'structured';
        }
        if (in_array($filters['type'], ['structured', 'external'], true)) {
            $filters['category'] = null;
        }
        if ($filters['category'] && $filters['type'] === null) {
            $filters['type'] = 'archive';
        }
        if ($filters['type'] === 'archive') {
            $filters['status'] = null;
        }

        $periodFromMonth = $filters['period_from'];
        $periodToMonth = $filters['period_to'];
        if ($periodFromMonth || $periodToMonth) {
            $filters['year'] = null;
        }
        if ($filters['year'] && ! $periodFromMonth && ! $periodToMonth) {
            $periodFromMonth = $filters['year'] . '-01';
            $periodToMonth = $filters['year'] . '-12';
        }

        if ($periodFromMonth && $periodToMonth && $periodFromMonth > $periodToMonth) {
            [$periodFromMonth, $periodToMonth] = [$periodToMonth, $periodFromMonth];
            $filters['period_from'] = $periodFromMonth;
            $filters['period_to'] = $periodToMonth;
        }

        $periodFrom = $periodFromMonth ? $periodFromMonth . '-01' : null;
        $periodTo = $periodToMonth
            ? (new \DateTimeImmutable($periodToMonth . '-01'))->modify('last day of this month')->format('Y-m-d')
            : null;

        $perPageOptions = [10, 15, 25, 50, 100];
        $requestedPerPage = (int) $request->query('per_page', 15);
        $perPage = in_array($requestedPerPage, $perPageOptions, true) ? $requestedPerPage : 15;

        $queries = [];

        if ($canDocs && $filters['type'] !== 'archive') {
            $scope = PermissionHelper::getEffectiveScope($user, AnnexDocument::class);
            $documentQuery = AnnexDocument::query()
                ->selectRaw("'document' as row_kind, annex_documents.id as row_id, COALESCE(annex_documents.period_end, annex_documents.created_at) as sort_at")
                ->when(! $scope['sees_all'], fn ($q) => $q->whereIn('annex_documents.project_id', $scope['project_ids']))
                ->when(in_array($filters['type'], ['structured', 'external'], true), fn ($q) => $q->where('annex_documents.document_kind', $filters['type']))
                ->when($filters['status'], fn ($q, $v) => $q->where('annex_documents.status', $v))
                ->when($filters['project_id'], fn ($q, $v) => $q->where('annex_documents.project_id', $v))
                ->when($filters['path_id'], fn ($q, $v) => $q->whereHas('project', fn ($p) => $p->where('path_id', $v)))
                ->when($filters['center_id'], fn ($q, $v) => $q->where('annex_documents.center_id', $v))
                ->when($filters['template_id'], fn ($q, $v) => $q->where('annex_documents.template_id', $v))
                ->when($periodFrom, fn ($q) => $q->whereNotNull('annex_documents.period_start')->where('annex_documents.period_start', '<=', $periodTo ?? '9999-12-31'))
                ->when($periodFrom, fn ($q) => $q->where('annex_documents.period_end', '>=', $periodFrom))
                ->when(! $periodFrom && $periodTo, fn ($q) => $q->whereNotNull('annex_documents.period_start')->where('annex_documents.period_start', '<=', $periodTo))
                ->when($filters['q'], function ($q, $search) {
                    $q->where(function ($w) use ($search) {
                        $w->where('annex_documents.title', 'like', "%{$search}%")
                            ->orWhere('annex_documents.period', 'like', "%{$search}%")
                            ->orWhereHas('template', fn ($t) => $t->where('title_ar', 'like', "%{$search}%"))
                            ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                    });
                });

            $queries[] = $documentQuery;
        }

        if ($canArchive && in_array($filters['type'], [null, 'archive'], true) && ! $filters['status'] && ! $filters['template_id']) {
            $scope = PermissionHelper::getEffectiveScope($user, UploadedDocument::class);
            $archiveQuery = UploadedDocument::query()
                ->whereNull('annex_document_id')
                ->selectRaw("'archive' as row_kind, uploaded_documents.id as row_id, COALESCE(uploaded_documents.document_date, uploaded_documents.created_at) as sort_at")
                ->when(! $scope['sees_all'] && $user->type !== 'super-admin', function ($q) use ($scope) {
                    $centerIds = $scope['center_ids'] ?: [0];
                    $projectIds = $scope['project_ids'] ?: [0];
                    $q->where(function ($w) use ($centerIds, $projectIds) {
                        $w->where(fn ($unassigned) => $unassigned->whereNull('center_id')->whereNull('project_id'))
                            ->orWhereIn('center_id', $centerIds)
                            ->orWhereIn('project_id', $projectIds);
                    });
                })
                ->when($filters['category'], fn ($q, $v) => $q->where('uploaded_documents.category', $v))
                ->when($filters['project_id'], fn ($q, $v) => $q->where('uploaded_documents.project_id', $v))
                ->when($filters['path_id'], fn ($q, $v) => $q->whereHas('project', fn ($p) => $p->where('path_id', $v)))
                ->when($filters['center_id'], fn ($q, $v) => $q->where('uploaded_documents.center_id', $v))
                ->when($periodFrom, fn ($q) => $q->whereNotNull('uploaded_documents.document_date')->where('uploaded_documents.document_date', '>=', $periodFrom))
                ->when($periodTo, fn ($q) => $q->whereNotNull('uploaded_documents.document_date')->where('uploaded_documents.document_date', '<=', $periodTo))
                ->when($filters['q'], function ($q, $search) {
                    $q->where(function ($w) use ($search) {
                        $w->where('uploaded_documents.title', 'like', "%{$search}%")
                            ->orWhere('uploaded_documents.description', 'like', "%{$search}%")
                            ->orWhere('uploaded_documents.file_name', 'like', "%{$search}%")
                            ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                    });
                });

            $queries[] = $archiveQuery;
        }

        if ($queries === []) {
            $documents = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(), 0, $perPage, 1, ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $union = array_shift($queries);
            foreach ($queries as $query) {
                $union->unionAll($query);
            }

            $documents = DB::query()
                ->fromSub($union, 'unified_documents')
                ->orderByDesc('sort_at')
                ->orderByDesc('row_id')
                ->orderBy('row_kind')
                ->paginate($perPage)
                ->withQueryString();

            $entries = $documents->getCollection();
            $documentIds = $entries->where('row_kind', 'document')->pluck('row_id')->all();
            $archiveIds = $entries->where('row_kind', 'archive')->pluck('row_id')->all();

            $annexById = AnnexDocument::with(['template', 'project.path', 'creator', 'linkedFiles'])
                ->whereIn('id', $documentIds)->get()->keyBy('id');
            $archiveById = UploadedDocument::with(['project.path', 'center', 'uploader'])
                ->whereIn('id', $archiveIds)->get()->keyBy('id');

            $rows = $entries->map(function ($entry) use ($annexById, $archiveById) {
                if ($entry->row_kind === 'archive') {
                    $model = $archiveById->get($entry->row_id);
                    if (! $model) {
                        return null;
                    }

                    return [
                        'kind' => 'archive', 'source' => 'archive', 'model' => $model,
                        'title' => $model->title, 'subtitle' => $model->categoryLabel(),
                        'project' => $model->project?->name, 'path' => $model->project?->path?->name,
                        'center' => $model->center?->name, 'period' => null,
                        'when' => $model->document_date ?? $model->created_at,
                        'person' => $model->uploader?->name, 'file' => $model,
                    ];
                }

                $model = $annexById->get($entry->row_id);
                if (! $model) {
                    return null;
                }

                $file = $model->isExternal() ? $model->linkedFiles->sortByDesc('id')->first() : null;

                return [
                    'kind' => 'document', 'source' => $model->isExternal() ? 'external' : 'structured', 'model' => $model,
                    'title' => $model->title ?: ($model->template?->title_ar ?? 'وثيقة'),
                    'subtitle' => $model->isExternal() ? 'ملف مرفوع (PDF)' : ($model->template?->title_ar ?? '—'),
                    'project' => $model->project?->name, 'path' => $model->project?->path?->name,
                    'center' => null, 'period' => $model->periodLabel(),
                    'when' => $file?->document_date ?? $model->created_at,
                    'person' => $model->creator?->name, 'file' => $file,
                ];
            })->filter()->values();

            $documents->setCollection($rows);
        }

        return view('admin.project-docs.documents.index', [
            'documents' => $documents,
            'filters' => $filters,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'canDocs' => $canDocs,
            'canArchive' => $canArchive,
            'projects' => Project::ordered()->with('path')->get(),
            'paths' => ProjectPath::ordered()->get(),
            'templates' => AnnexTemplate::orderBy('title_ar')->get(),
            'centers' => \App\Models\Admin\Center::orderBy('name')->get(),
            'categories' => UploadedDocument::CATEGORIES,
            'typeCounts' => $this->unifiedTypeCounts($user, $canDocs, $canArchive),
        ]);
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
        $projects = Project::ordered()->get();
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
            'period' => 'nullable|string|max:40',
            'period_start' => 'nullable|required_with:period_end|date_format:Y-m',
            'period_end' => 'nullable|date_format:Y-m|after_or_equal:period_start',
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
        $periodData = $this->periodData($validated);
        // عدد الصفحات يُقرأ حصراً من القالب — لا يُقبل من نموذج الوثيقة
        $pageCount = max(1, min(60, (int) ($template->default_page_count ?? 1)));

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            ...$periodData,
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
        $periodData = $this->periodData($validated);

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? $template->title_ar,
            'project_id' => $validated['project_id'] ?? null,
            ...$periodData,
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

    /** Store month ranges as indexed dates while retaining the legacy display string. */
    private function periodData(array $input): array
    {
        $startMonth = $input['period_start'] ?? null;
        $endMonth = $input['period_end'] ?? null;

        if (! $startMonth) {
            return [
                'period' => $input['period'] ?? null,
                'period_start' => null,
                'period_end' => null,
            ];
        }

        $endMonth = $endMonth ?: $startMonth;
        $start = new \DateTimeImmutable($startMonth . '-01');
        $end = (new \DateTimeImmutable($endMonth . '-01'))->modify('last day of this month');

        return [
            'period' => $startMonth === $endMonth ? $startMonth : $startMonth . ' — ' . $endMonth,
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
        ];
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
            'period_start' => $document->period_start,
            'period_end' => $document->period_end,
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

        // قائمة المستخدمين لمنسدلة الإحالة (عند الإرسال أو إعادة الإحالة)
        $reviewers = in_array($document->status, ['draft', 'under_review'], true)
            ? User::orderBy('name')->get()
            : collect();

        return view('admin.project-docs.documents.show', compact('document', 'reviewers'));
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

        $documentInput = $request->validate([
            'title' => 'sometimes|nullable|string|max:255',
            'period_start' => 'sometimes|nullable|required_with:period_end|date_format:Y-m',
            'period_end' => 'sometimes|nullable|date_format:Y-m|after_or_equal:period_start',
        ]);

        $user = auth()->user();
        $isCreator = $user->id === (int) $document->created_by;

        $documentChanges = [];
        if (array_key_exists('title', $documentInput)) {
            $documentChanges['title'] = $documentInput['title'];
        }
        if (array_key_exists('period_start', $documentInput) && filled($documentInput['period_start'])) {
            $documentChanges = array_merge($documentChanges, $this->periodData($documentInput));
        } elseif (array_key_exists('period_start', $documentInput) && $document->period_start) {
            $documentChanges = array_merge($documentChanges, [
                'period' => null,
                'period_start' => null,
                'period_end' => null,
            ]);
        }
        if ($documentChanges !== []) {
            $document->update($documentChanges);
        }

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

        // عدد الصفحات غير قابل للتعديل من الوثيقة — يُدار من القالب فقط
        $pageCount = (int) $document->page_count;

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

    public function submit(Request $request, AnnexDocument $document)
    {
        $missing = $document->missingSections();

        if (! empty($missing)) {
            return back()->with('error', 'أقسام ناقصة قبل الإرسال: ' . implode('، ', $missing));
        }

        $validated = $request->validate([
            'reviewer_id' => 'required|exists:users,id',
        ], [
            'reviewer_id.required' => 'اختر المُحال إليه للمراجعة قبل الإرسال.',
        ]);

        $document->blocks()->update(['locked' => true]);
        $document->update([
            'status' => 'under_review',
            'assigned_to' => (int) $validated['reviewer_id'],
        ]);
        $document->logWorkflow('submit', (int) $validated['reviewer_id'], 'أُرسلت الوثيقة للمراجعة', 'under_review');

        return back()->with('success', 'أُرسلت الوثيقة للمراجعة — أقسامها مقفلة، محالة إلى ' . ($document->assignee?->name ?? ''));
    }

    public function reopen(AnnexDocument $document)
    {
        if ($document->status !== 'rejected') {
            return back()->with('error', 'إعادة الفتح متاحة فقط للوثائق المرفوضة');
        }

        $document->blocks()->update(['locked' => false]);
        $document->update(['status' => 'draft', 'signed_at' => null, 'assigned_to' => null]);
        $document->logWorkflow('reopen', null, 'أُعيد فتح الوثيقة للتعديل — تُلغى الإحالة السابقة', 'draft');

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

        // نظام الإحالة: الاعتماد/الرفض للمُحال إليه وحده (super-admin ليس استثناءً)،
        // والتعليق أثناء المراجعة كذلك. خارج المراجعة يبقى التعليق مفتوحاً لأصحاب edit.
        $requiresAssignee = in_array($validated['action'], ['approve', 'reject'], true)
            || ($validated['action'] === 'comment' && $document->status === 'under_review');
        if ($requiresAssignee && (int) $document->assigned_to !== (int) auth()->id()) {
            return back()->with('error', $document->assigned_to
                ? 'هذه الوثيقة مُحال إليها إلى ' . ($document->assignee?->name ?? 'مستخدم آخر') . ' — لا يملك غيره الاعتماد أو التعليق'
                : 'هذه الوثيقة غير مُحال إليها (أُرسلت قبل نظام الإحالة) — لا يمكن اعتمادها قبل إعادة الإحالة');
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

    /*
     * إعادة الإحالة: للمُحال إليه الحالي أو مدير النظام (super-admin) فقط،
     * وتعمل فقط على الوثائق قيد المراجعة — تُستخدم أيضاً لإحالة الوثائق
     * القديمة التي أُرسلت قبل نظام الإحالة (assigned_to = null).
     */
    public function reassign(Request $request, AnnexDocument $document)
    {
        $actor = auth()->user();
        $isCurrentAssignee = $document->assigned_to !== null && (int) $document->assigned_to === (int) $actor->id;

        if (! $isCurrentAssignee && $actor->type !== 'super-admin') {
            return back()->with('error', 'إعادة الإحالة متاحة للمُحال إليه أو مدير النظام فقط');
        }

        if ($document->status !== 'under_review') {
            return back()->with('error', 'إعادة الإحالة متاحة فقط للوثائق قيد المراجعة');
        }

        $validated = $request->validate([
            'reviewer_id' => 'required|exists:users,id',
        ], [
            'reviewer_id.required' => 'اختر المستخدم المُحال إليه.',
        ]);

        $newReviewerId = (int) $validated['reviewer_id'];
        $document->update(['assigned_to' => $newReviewerId]);
        $document->logWorkflow('reassign', $newReviewerId, 'أُعيدت الإحالة إلى ' . ($document->assignee?->name ?? ''), 'under_review');

        return back()->with('success', 'أُعيدت الإحالة إلى ' . ($document->assignee?->name ?? ''));
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
