<?php

namespace App\Http\Controllers\Admin\MonthlyReports;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\MonthlyReports\MonthlyReport;
use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use App\Models\Admin\Project;
use Illuminate\Http\Request;

class MonthlyReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReport,view')->only(['index', 'show', 'printDocument']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReport,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReport,edit')->only(['edit', 'update', 'submit', 'reopen', 'signoff']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReport,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $projectId = $request->input('project_id');

        $scope = PermissionHelper::getEffectiveScope(auth()->user(), MonthlyReport::class);

        $query = MonthlyReport::with(['template', 'project', 'creator', 'blocks']);

        if (! $scope['sees_all']) {
            $query->whereIn('project_id', $scope['project_ids']);
        }

        $reports = $query
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->when($projectId, fn ($q, $v) => $q->where('project_id', $v))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.monthly-reports.index', compact('reports', 'projects', 'status', 'projectId'));
    }

    public function create()
    {
        $templates = MonthlyReportTemplate::where('is_active', true)->orderBy('title_ar')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.monthly-reports.create', compact('templates', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:monthly_report_templates,id',
            'title' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'period' => 'nullable|string|max:20',
        ]);

        $template = MonthlyReportTemplate::findOrFail($validated['template_id']);

        $report = MonthlyReport::create([
            'template_id' => $template->id,
            'template_version' => $template->version,
            'title' => $validated['title'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'status' => 'draft',
            'data' => null,
            'created_by' => auth()->id(),
            'assigned_to' => null,
            'signed_at' => null,
        ]);

        foreach ($template->sections() as $section) {
            $report->blocks()->create([
                'block_key' => $section['key'] ?? null,
                'json_value' => null,
                'updated_by' => null,
                'locked' => false,
            ]);
        }

        $report->logWorkflow('create', null, 'تم إنشاء التقرير من قالب: ' . $template->title_ar, 'draft');

        return redirect()->route('admin.monthly-reports.edit', $report)
            ->with('success', 'تم إنشاء التقرير — ابدأ بتعبئة أقسامه');
    }

    public function show(MonthlyReport $report)
    {
        $report->load(['template', 'project', 'creator', 'assignee', 'blocks.updater', 'signoffs.user', 'workflowActions.fromUser', 'workflowActions.toUser']);

        return view('admin.monthly-reports.show', compact('report'));
    }

    public function edit(MonthlyReport $report)
    {
        if (! in_array($report->status, ['draft', 'rejected'], true)) {
            return redirect()->route('admin.monthly-reports.show', $report)
                ->with('error', 'تعبئة الأقسام متاحة فقط للمسودات أو التقارير المعاد فتحها');
        }

        $report->load(['template', 'project', 'creator', 'blocks']);

        return view('admin.monthly-reports.edit', compact('report'));
    }

    public function update(Request $request, MonthlyReport $report)
    {
        if (in_array($report->status, ['under_review', 'approved'], true)) {
            return back()->with('error', 'التقرير ليس في حالة قابلة للتعديل حالياً');
        }

        $user = auth()->user();
        $isCreator = $user->id === (int) $report->created_by;

        $blocksInput = $request->input('blocks', []);
        $locksInput = $request->input('lock', []);
        $sections = $report->sections();

        foreach ($sections as $section) {
            $key = $section['key'];
            $block = $report->blocks->firstWhere('block_key', $key);

            $editable = ! $block?->locked || $isCreator;
            if (! $editable) {
                continue;
            }

            $value = $this->extractValue($section['type'] ?? 'paragraph', $blocksInput[$key] ?? []);
            $shouldLock = ! empty($locksInput[$key]);

            if ($block) {
                $block->update([
                    'json_value' => $value,
                    'updated_by' => $user->id,
                    'locked' => $shouldLock,
                ]);
            }
        }

        $report->logWorkflow('update', null, 'تحديث محتوى الأقسام');

        return back()->with('success', 'تم حفظ الأقسام');
    }

    public function submit(MonthlyReport $report)
    {
        $missing = $report->missingSections();

        if (! empty($missing)) {
            return back()->with('error', 'أقسام ناقصة قبل الإرسال: ' . implode('، ', $missing));
        }

        $report->blocks()->update(['locked' => true]);
        $report->update(['status' => 'under_review']);
        $report->logWorkflow('submit', null, 'أُرسل التقرير للمراجعة', 'under_review');

        return back()->with('success', 'أُرسل التقرير للمراجعة — أقسامه مقفلة الآن');
    }

    public function reopen(MonthlyReport $report)
    {
        if ($report->status !== 'rejected') {
            return back()->with('error', 'إعادة الفتح متاحة فقط للتقارير المرفوضة');
        }

        $report->blocks()->update(['locked' => false]);
        $report->update(['status' => 'draft', 'signed_at' => null]);
        $report->logWorkflow('reopen', null, 'أُعيد فتح التقرير للتعديل', 'draft');

        return redirect()->route('admin.monthly-reports.edit', $report)
            ->with('success', 'أُعيد فتح التقرير للتعديل');
    }

    public function signoff(Request $request, MonthlyReport $report)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,comment',
            'note' => 'nullable|string|max:2000',
        ]);

        if (in_array($validated['action'], ['approve', 'reject'], true) && $report->status !== 'under_review') {
            return back()->with('error', 'الاعتماد أو الرفض متاح فقط للتقارير قيد المراجعة');
        }

        if ($report->isApproved()) {
            return back()->with('error', 'التقرير معتمد بالفعل');
        }

        $role = auth()->user()->jobTitle?->title_ar;

        $report->signoffs()->create([
            'user_id' => auth()->id(),
            'role_label' => $role,
            'action' => $validated['action'],
            'note' => $validated['note'] ?? null,
        ]);

        if ($validated['action'] === 'approve') {
            $report->blocks()->update(['locked' => true]);
            $report->update(['status' => 'approved', 'signed_at' => now()]);
            $report->logWorkflow('approve', null, $validated['note'] ?? null, 'approved');

            return back()->with('success', 'تم اعتماد التقرير نهائياً');
        }

        if ($validated['action'] === 'reject') {
            $report->update(['status' => 'rejected']);
            $report->logWorkflow('reject', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'تم رفض التقرير — أعد فتحه للتعديل من صفحته');
        }

        $report->logWorkflow('comment', null, $validated['note'] ?? null);

        return back()->with('success', 'تمت إضافة الملاحظة');
    }

    public function printDocument(MonthlyReport $report)
    {
        $report->load(['template', 'project', 'creator', 'signoffs.user', 'blocks.updater']);

        return view('admin.monthly-reports.print', compact('report'));
    }

    public function destroy(MonthlyReport $report)
    {
        $user = auth()->user();

        if ($report->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف تقرير خارج حالة المسودة');
        }

        if ($user->id !== (int) $report->created_by && $user->type !== 'super-admin') {
            return back()->with('error', 'يمكن لمنشئ التقرير فقط حذف المسودة');
        }

        $report->delete();

        return redirect()->route('admin.monthly-reports.index')
            ->with('success', 'تم حذف التقرير');
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