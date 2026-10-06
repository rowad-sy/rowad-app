<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexDocument;
use App\Models\Admin\ProjectDocs\AnnexDocumentBlock;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use Illuminate\Http\Request;

class AnnexDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,view')->only(['index', 'show', 'printDocument']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,create')->only(['create', 'store', 'duplicate']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,edit')->only(['edit', 'update', 'submit', 'reopen', 'signoff']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $projectId = $request->input('project_id');

        $scope = PermissionHelper::getEffectiveScope(auth()->user(), AnnexDocument::class);

        $query = AnnexDocument::with(['template', 'project', 'creator', 'blocks']);

        if (! $scope['sees_all']) {
            $query->whereIn('project_id', $scope['project_ids']);
        }

        $documents = $query
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->when($projectId, fn ($q, $v) => $q->where('project_id', $v))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.project-docs.documents.index', compact('documents', 'projects', 'status', 'projectId'));
    }

    public function help()
    {
        return view('admin.project-docs.documents.help');
    }

    public function create()
    {
        $templates = AnnexTemplate::where('is_active', true)->orderBy('title_ar')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.project-docs.documents.form', compact('templates', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:annex_templates,id',
            'title' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'period' => 'nullable|string|max:20',
            'page_count' => 'required|integer|min:1|max:60',
        ]);

        $template = AnnexTemplate::findOrFail($validated['template_id']);
        $pageCount = (int) $validated['page_count'];

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'status' => 'draft',
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

    public function duplicate(AnnexDocument $document)
    {
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
        $document->load(['template', 'project', 'creator', 'assignee', 'blocks.updater', 'signoffs.user', 'workflowActions.fromUser', 'workflowActions.toUser']);

        return view('admin.project-docs.documents.show', compact('document'));
    }

    public function edit(AnnexDocument $document)
    {
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
        $document->load(['template', 'project', 'creator', 'signoffs.user', 'blocks.updater']);

        return view('admin.project-docs.documents.print', compact('document'));
    }

    public function destroy(AnnexDocument $document)
    {
        $user = auth()->user();

        if ($document->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف وثيقة خارج حالة المسودة');
        }

        if ($user->id !== (int) $document->created_by && $user->type !== 'super-admin') {
            return back()->with('error', 'يمكن لمنشئ الوثيقة فقط حذف المسودة');
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