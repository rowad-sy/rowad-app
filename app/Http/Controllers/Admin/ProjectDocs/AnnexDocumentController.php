<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
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
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,edit')->only(['edit', 'update', 'submit', 'signoff']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexDocument,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $projectId = $request->input('project_id');

        $scope = PermissionHelper::getEffectiveScope(auth()->user(), AnnexDocument::class);

        $query = AnnexDocument::with(['template', 'project', 'center', 'creator', 'blocks']);

        if (! $scope['sees_all']) {
            $query->where(function ($q) use ($scope) {
                if (! empty($scope['center_ids'])) {
                    $q->orWhereIn('center_id', $scope['center_ids']);
                }
                if (! empty($scope['project_ids'])) {
                    $q->orWhereIn('project_id', $scope['project_ids']);
                }
            });
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
        $centers = Center::orderBy('name')->get();

        return view('admin.project-docs.documents.form', compact('templates', 'projects', 'centers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:annex_templates,id',
            'title' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'center_id' => 'nullable|exists:centers,id',
            'period' => 'nullable|string|max:20',
            'page_count' => 'required|integer|min:1|max:20',
        ]);

        $template = AnnexTemplate::findOrFail($validated['template_id']);

        $document = AnnexDocument::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'center_id' => $validated['center_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'status' => 'draft',
            'page_count' => $validated['page_count'],
            'data' => null,
            'created_by' => auth()->id(),
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        foreach ($template->sections() as $section) {
            $document->blocks()->create([
                'block_key' => $section['key'] ?? null,
                'json_value' => null,
                'updated_by' => null,
                'locked' => false,
            ]);
        }

        $document->logWorkflow('create', null, 'تم إنشاء وثيقة من قالب: ' . $template->title_ar, 'draft');

        return redirect()->route('admin.project-docs.documents.edit', $document)
            ->with('success', 'تم إنشاء الوثيقة — ابدأ بتعبئة أقسامها');
    }

    public function show(AnnexDocument $document)
    {
        $document->load(['template', 'project', 'center', 'creator', 'assignee', 'blocks.updater', 'signoffs.user', 'workflowActions.fromUser', 'workflowActions.toUser']);

        return view('admin.project-docs.documents.show', compact('document'));
    }

    public function edit(AnnexDocument $document)
    {
        if (! in_array($document->status, ['draft', 'rejected'], true)) {
            return redirect()->route('admin.project-docs.documents.show', $document)
                ->with('error', 'تعبئة الأقسام متاحة فقط للمسودات أو الوثائق المعاد فتحها');
        }

        $document->load(['template', 'project', 'center', 'creator', 'blocks']);

        return view('admin.project-docs.documents.edit', compact('document'));
    }

    public function update(Request $request, AnnexDocument $document)
    {
        if (in_array($document->status, ['under_review', 'approved'], true)) {
            return back()->with('error', 'الوثيقة ليست في حالة قابلة للتعديل حالياً');
        }

        $user = auth()->user();
        $isCreator = $user->id === (int) $document->created_by;

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
            $pageNumber = max(1, (int) ($locksInput[$key . '_page'] ?? ($block?->page_number ?? 1)));

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
        $document->load(['template', 'project', 'center', 'creator', 'signoffs.user', 'blocks.updater']);

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
            'table' => collect($input['rows'] ?? [])->map(function ($row) {
                return array_values(array_filter($row ?? [], fn ($v) => trim((string) $v) !== ''));
            })->filter(fn ($row) => ! empty($row))->values()->all(),
            'list' => collect($input['items'] ?? [])->filter(fn ($v) => trim((string) $v) !== '')->values()->all(),
            default => trim((string) ($input['paragraph'] ?? '')),
        };
    }
}