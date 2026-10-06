<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdDesignRequest;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\Project;
use App\Models\User;
use App\Support\RoleHoldersLookup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdDesignRequestController extends Controller
{
    public const ROWADUNA_PAGE = 'page:admin.rowaduna.dashboard';

    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\AdDesignRequest,view')->only(['index', 'show', 'help']);
        $this->middleware('permission:App\Models\Admin\AdDesignRequest,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\AdDesignRequest,edit')->only([
            'edit', 'update', 'pm2Decide', 'rowadunaDecide', 'designerSubmit', 'requesterDecide', 'publishFinal', 'refer',
        ]);
        $this->middleware('permission:App\Models\Admin\AdDesignRequest,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = AdDesignRequest::with(['center', 'project', 'creator', 'pm2User', 'rowadunaUser', 'designer', 'publisher']);

        if ($user->type !== 'super-admin') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('refer_to_pm2_id', $user->id)
                    ->orWhere('refer_to_rowaduna_id', $user->id)
                    ->orWhere('refer_to_designer_id', $user->id)
                    ->orWhere('refer_to_publisher_id', $user->id)
                    ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $user->id));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        $adRequests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $centers = Center::orderBy('name')->get();

        return view('admin.ad-design-requests.index', compact('adRequests', 'centers'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $tentativePm2Id = $this->defaultProjectsManagerId();

        return view('admin.ad-design-requests.form', compact('centers', 'projects', 'users', 'tentativePm2Id'));
    }

    public function help()
    {
        return view('admin.ad-design-requests.help');
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $pm2Id = $validated['refer_to_pm2_id'] ?? $this->defaultProjectsManagerId();

        if ($pm2Id === null || (int) $pm2Id === (int) auth()->id()) {
            throw ValidationException::withMessages(['refer_to_pm2_id' => 'اختر مدير المشاريع (لا يمكن أن تكون نفسك).']);
        }

        $ad = AdDesignRequest::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'center_id' => $validated['center_id'] ?? $this->employeeCenterId(),
            'project_id' => $validated['project_id'] ?? $this->employeeProjectId(),
            'created_by' => auth()->id(),
            'due_date' => $validated['due_date'] ?? null,
            'refer_to_pm2_id' => $pm2Id,
            'refer_to_rowaduna_id' => $this->defaultRowadunaId(),
            'status' => 'pm2_review',
        ]);

        $ad->logWorkflow('create', $pm2Id, 'تم إنشاء طلب التصميم وإحالته لمدير المشاريع', 'pm2_review');
        $ad->referTo($pm2Id, 'pm2');

        return redirect()->route('admin.ad-design-requests.show', $ad)
            ->with('success', 'تم إنشاء طلب التصميم بنجاح');
    }

    public function show(AdDesignRequest $ad)
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $ad->isVisibleToUserId($user->id)) {
            abort(403, 'هذا الطلب ليس موجهًا إليك');
        }

        $ad->load([
            'center', 'project', 'creator', 'pm2User', 'rowadunaUser', 'designer', 'publisher',
            'approvedByUser', 'publishedByUser', 'workflowActions.fromUser', 'workflowActions.toUser',
        ]);

        $users = User::orderBy('name')->get();
        $tentativeRowadunaId = $ad->refer_to_rowaduna_id ?? $this->defaultRowadunaId();

        return view('admin.ad-design-requests.show', compact('ad', 'users', 'tentativeRowadunaId'));
    }

    public function edit(AdDesignRequest $ad)
    {
        abort_if($ad->isLocked(), 403, 'الطلب مُقفل بعد موافقة مدير المشاريع');

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $tentativePm2Id = $ad->refer_to_pm2_id;

        return view('admin.ad-design-requests.form', compact('ad', 'centers', 'projects', 'users', 'tentativePm2Id'));
    }

    public function update(Request $request, AdDesignRequest $ad)
    {
        abort_if($ad->isLocked(), 403, 'الطلب مُقفل بعد موافقة مدير المشاريع');
        $validated = $this->validateRequest($request);

        $ad->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'center_id' => $validated['center_id'] ?? $ad->center_id,
            'project_id' => $validated['project_id'] ?? $ad->project_id,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()->route('admin.ad-design-requests.show', $ad)
            ->with('success', 'تم تحديث طلب التصميم');
    }

    public function destroy(AdDesignRequest $ad)
    {
        abort_if($ad->isLocked(), 403, 'الطلب مُقفل بعد موافقة مدير المشاريع — لا يمكن حذفه');

        $ad->delete();

        return redirect()->route('admin.ad-design-requests.index')
            ->with('success', 'تم حذف طلب التصميم');
    }

    /*
     * (1) مدير المشاريع — يعتمد ويحيل لمسؤول روادنا. حصرية صارمة: المحال إليه فقط.
     */
    public function pm2Decide(Request $request, AdDesignRequest $ad)
    {
        $this->authorizeHolder($ad, 'refer_to_pm2_id', 'pm2', 'pm2_review');

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000',
            'refer_to_rowaduna_id' => ['required_if:decision,approve', 'exists:users,id', function ($attr, $value, $fail) {
                if ((int) $value === (int) auth()->id()) {
                    $fail('لا يمكن أن تكون الإحالة إلى نفسك.');
                }
            }],
        ]);

        if ($validated['decision'] === 'reject') {
            $ad->update(['status' => 'rejected', 'reason' => $validated['note'] ?? null]);
            $ad->completeReferral('pm2');
            $ad->cancelActiveReferrals();
            $ad->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'رُفض طلب التصميم من مدير المشاريع.');
        }

        $ad->update(['status' => 'rowaduna_review', 'refer_to_rowaduna_id' => $validated['refer_to_rowaduna_id']]);
        $ad->completeReferral('pm2');
        $ad->referTo((int) $validated['refer_to_rowaduna_id'], 'rowaduna', $validated['note'] ?? null);
        $ad->logWorkflow('pm2_approved', (int) $validated['refer_to_rowaduna_id'], $validated['note'] ?? null, 'rowaduna_review');

        return back()->with('success', 'اعتمد مدير المشاريع الطلب — أُحيل لمسؤول روادنا.');
    }

    /*
     * (2) مسؤول روادنا — يُحيل المصمم المختص.
     */
    public function rowadunaDecide(Request $request, AdDesignRequest $ad)
    {
        $this->authorizeHolder($ad, 'refer_to_rowaduna_id', 'rowaduna', 'rowaduna_review');

        $validated = $request->validate([
            'designer_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $ad->update(['status' => 'designing', 'refer_to_designer_id' => $validated['designer_user_id'], 'revision_note' => null]);
        $ad->completeReferral('rowaduna');
        $ad->referTo((int) $validated['designer_user_id'], 'designer', $validated['note'] ?? null);
        $ad->logWorkflow('assigned_designer', (int) $validated['designer_user_id'],
            'إسناد التصميم لمصمم' . (! empty($validated['note']) ? ': ' . $validated['note'] : ''));

        return back()->with('success', 'أُسند التصميم للمصمم المختص.');
    }

    /*
     * (3) المصمم — يرفع رابط التصميم (جوجل درايف) ويحيل لصاحب الطلب.
     */
    public function designerSubmit(Request $request, AdDesignRequest $ad)
    {
        $this->authorizeHolder($ad, 'refer_to_designer_id', 'designer', 'designing');

        $validated = $request->validate([
            'design_url' => 'required|url|max:2000',
            'design_note' => 'nullable|string|max:2000',
        ]);

        $ad->update([
            'status' => 'ready_for_review',
            'design_url' => $validated['design_url'],
            'design_note' => $validated['design_note'] ?? null,
        ]);

        $ad->completeReferral('designer');
        $ad->referTo((int) $ad->created_by, 'creator', $validated['design_note'] ?? null);
        $ad->logWorkflow('design_submitted', (int) $ad->created_by, 'رفع المصمم رابط التصميم بانتظار مراجعة صاحب الطلب');

        return back()->with('success', 'رُفع رابط التصميم — بانتظار مراجعة صاحب الطلب.');
    }

    /*
     * (4) صاحب الطلب (مدير المشروع) — يعتمد أو يعيد للمصمم مع ملاحظات أو يرفض.
     * الخطوة حصرية له حتى لو كان سوبر ادمن.
     */
    public function requesterDecide(Request $request, AdDesignRequest $ad)
    {
        $user = auth()->user();
        abort_if((int) $ad->created_by !== (int) $user->id, 403, 'هذه الخطوة لصاحب الطلب فقط — حتى السوبر ادمن لا يعتمد.');
        abort_if($ad->status !== 'ready_for_review', 403, 'حالة الطلب لا تسمح بهذه الخطوة.');

        $validated = $request->validate([
            'decision' => 'required|in:approve,return,reject',
            'note' => 'nullable|string|max:2000',
            'publisher_user_id' => 'required_if:decision,approve|nullable|exists:users,id',
        ]);

        if ($validated['decision'] === 'reject') {
            $ad->update(['status' => 'rejected', 'reason' => $validated['note'] ?? null]);
            $ad->cancelActiveReferrals();
            $ad->logWorkflow('rejected', null, $validated['note'] ?? null, 'rejected');

            return back()->with('error', 'رُفض طلب التصميم.');
        }

        if ($validated['decision'] === 'return') {
            if (empty($validated['note'])) {
                return back()->with('error', 'كتابة الملاحظات إلزامية عند إعادة التصميم للمصمم.');
            }

            $ad->update(['status' => 'designing', 'revision_note' => $validated['note']]);
            $ad->cancelActiveReferrals('creator');
            $ad->referTo((int) $ad->refer_to_designer_id, 'designer', $validated['note']);
            $ad->logWorkflow('design_returned', (int) $ad->refer_to_designer_id, 'إعادة التصميم للمصمم: ' . $validated['note']);

            return back()->with('success', 'أُعيد الطلب للمصمم مع الملاحظات.');
        }

        $ad->update([
            'status' => 'to_publish',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'refer_to_publisher_id' => $validated['publisher_user_id'],
            'revision_note' => null,
        ]);

        $ad->completeReferral('creator');
        $ad->referTo((int) $validated['publisher_user_id'], 'publisher', 'التصميم معتمد — يُدخل الناشر روابط النشر');
        $ad->logWorkflow('approved', (int) $validated['publisher_user_id'], 'اعتمد صاحب الطلب التصميم وأحيل للنشر', 'to_publish');

        return back()->with('success', 'عُتمد التصميم — أُحيل لإدخال روابط النشر.');
    }

    /*
     * (5) الناشر — روابط النشر النهائي فيُغلق الطلب.
     */
    public function publishFinal(Request $request, AdDesignRequest $ad)
    {
        $this->authorizeHolder($ad, 'refer_to_publisher_id', 'publisher', 'to_publish');

        $validated = $request->validate([
            'platforms' => 'required|array|min:1',
            'platforms.*.platform' => 'required|in:' . implode(',', array_keys(MediaPlanEvent::PLATFORMS)),
            'platforms.*.url' => 'required|url|max:2000',
        ]);

        $links = collect($validated['platforms'])->filter(fn ($l) => ! empty($l['url']))->values()->all();

        if ($links === []) {
            return back()->with('error', 'أدخل رابطاً واحداً على الأقل.');
        }

        $ad->update([
            'status' => 'published',
            'publish_links' => $links,
            'published_by' => auth()->id(),
            'published_at' => now(),
        ]);

        $ad->completeReferral('publisher');
        $ad->logWorkflow('published', null, 'نُشر التصميم نهائياً على: '
            . implode('، ', array_map(fn ($l) => MediaPlanEvent::PLATFORMS[$l['platform']] ?? $l['platform'], $links)), 'published');

        return back()->with('success', 'نُشر التصميم وتوثقت الروابط وأُغلق الطلب.');
    }

    /*
     * إعادة إحالة خطوة — الحامل الحالي فقط.
     */
    public function refer(Request $request, AdDesignRequest $ad)
    {
        $validated = $request->validate([
            'step' => 'required|in:pm2,rowaduna,designer,publisher',
            'to_user_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $column = match ($validated['step']) {
            'pm2' => 'refer_to_pm2_id',
            'rowaduna' => 'refer_to_rowaduna_id',
            'designer' => 'refer_to_designer_id',
            'publisher' => 'refer_to_publisher_id',
        };

        $this->authorizeHolder($ad, $column, $validated['step']);

        $ad->update([$column => $validated['to_user_id']]);
        $ad->referTo((int) $validated['to_user_id'], $validated['step'], $validated['note'] ?? null);
        $ad->logWorkflow('referred', (int) $validated['to_user_id'], $validated['note'] ?? null);

        return back()->with('success', 'أُعيدت الإحالة.');
    }

    /* ------------------------------------------------------------ guards */

    private function authorizeHolder(AdDesignRequest $ad, string $column, string $step, ?string $expectedStatus = null): void
    {
        $user = auth()->user();

        $isHolder = $ad->isCurrentRecipient($user->id)
            || (int) $ad->{$column} === (int) $user->id;

        abort_if(! $isHolder, 403, 'هذه الخطوة موجهة لشخص آخر — أنت لست صاحبها الحالي.');

        if ($expectedStatus !== null) {
            abort_if($ad->status !== $expectedStatus, 403, 'حالة الطلب لا تسمح بهذه الخطوة.');
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:4000',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'due_date' => 'nullable|date',
            'refer_to_pm2_id' => 'nullable|exists:users,id',
        ]);
    }

    private function defaultProjectsManagerId(): ?int
    {
        return RoleHoldersLookup::first('page:admin.projects-manager.dashboard');
    }

    private function defaultRowadunaId(): ?int
    {
        return RoleHoldersLookup::first(self::ROWADUNA_PAGE);
    }

    private function employeeCenterId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('center_id');
    }

    private function employeeProjectId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('project_id');
    }
}
