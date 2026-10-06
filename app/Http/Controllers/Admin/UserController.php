<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\User,view')->only(['index', 'export']);
        $this->middleware('permission:App\Models\User,create')->only(['create', 'store', 'import']);
        $this->middleware('permission:App\Models\User,edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('permission:App\Models\User,delete')->only(['destroy']);
    }

    /*
     * حارس super-admin (W3): حسابات super-admin وتعيينها لا يلمسها إلا super-admin نفسه.
     */
    private function assertSuperAdminAllowed(User $target = null, ?string $requestedType = null): void
    {
        if (auth()->user()->type === 'super-admin') {
            return;
        }

        if ($target && $target->type === 'super-admin') {
            abort(403, 'لا يمكن تعديل حسابات السوبر-أدن إلا من حساب سوبر-أدن.');
        }

        if ($requestedType === 'super-admin') {
            abort(403, 'لا يمكن تعيين نوع "سوبر-أدن" لأي حساب إلا من حساب سوبر-أدن.');
        }
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');
        $centerId = $request->input('center_id');
        $projectId = $request->input('project_id');
        $jobTitleId = $request->input('job_title_id');
        $perPage = (int) $request->input('per_page', 10);

        $users = User::with(['jobTitle', 'center', 'project'])
            ->withCount('groups')
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('official_email', 'like', "%{$search}%")
                        ->orWhereHas('jobTitle', function ($q) use ($search) {
                            $q->where('title_ar', 'like', "%{$search}%")
                                ->orWhere('title_en', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                return $q->where('is_active', $status === 'active');
            })
            ->when($type && $type !== 'all', function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->when($centerId, function ($q) use ($centerId) {
                return $q->where('center_id', $centerId);
            })
            ->when($projectId, function ($q) use ($projectId) {
                return $q->where('project_id', $projectId);
            })
            ->when($jobTitleId, function ($q) use ($jobTitleId) {
                return $q->where('job_title_id', $jobTitleId);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'type', 'center_id', 'project_id', 'job_title_id', 'per_page']));

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $jobTitles = JobPosition::orderBy('title_ar')->get();

        return view('admin.users.index', compact('users', 'search', 'status', 'type', 'centerId', 'projectId', 'jobTitleId', 'perPage', 'centers', 'projects', 'jobTitles'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $jobTitles = JobPosition::orderBy('title_ar')->get();

        return view('admin.users.form', $this->roleAssignmentData([
            'centers' => $centers,
            'projects' => $projects,
            'jobTitles' => $jobTitles,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'boolean',
            'type' => 'nullable|string|in:employee,beneficiary,student,super-admin',
            'student_id' => 'nullable|integer|exists:students,id',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'job_title_id' => 'nullable|exists:hr_job_positions,id',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // المستخدم الجديد يكون غير نشط بشكل تلقائي ويجب عليه تغيير كلمة المرور
        $validated['is_active'] = false;
        $validated['must_change_password'] = true;

        $this->assertSuperAdminAllowed(null, $validated['type'] ?? null);

        $user = User::create($validated);

        $this->linkRecord($user, $validated);
        $this->syncRoleAssignments($user, $request);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إضافة المستخدم بنجاح. المستخدم غير نشط ويجب عليه تغيير كلمة المرور عند أول تسجيل دخول');
    }

    public function edit(User $user)
    {
        $this->assertSuperAdminAllowed($user);

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $jobTitles = JobPosition::orderBy('title_ar')->get();

        return view('admin.users.form', $this->roleAssignmentData([
            'user' => $user,
            'centers' => $centers,
            'projects' => $projects,
            'jobTitles' => $jobTitles,
        ]));
    }

    /*
     * بيانات كتلة «الأدوار والنطاقات» في شاشة المستخدم (الترتيبة v2):
     * قائمة الأدوار + إسنادات المستخدم الحالي مع نطاقاتها + من يستطيع الإسناد.
     * الإسناد هنا لا يعتمد على أي جدول HR — users + groups + group_user فقط.
     */
    private function roleAssignmentData(array $data): array
    {
        $actor = auth()->user();

        $current = collect();
        if (isset($data['user'])) {
            $current = $data['user']->groups()->where('groups.kind', Group::KIND_ROLE)->get()->keyBy('id');
        }

        return $data + [
            'roles' => Group::roles()->orderBy('name')->get(),
            'roleAssignments' => $current,
            'canAssignRoles' => $actor->type === 'super-admin' || PermissionHelper::can($actor, Group::class, 'edit'),
            'cohorts' => \App\Models\Admin\Cohort::orderBy('name')->get(),
        ];
    }

    /*
     * مزامنة أدوار المستخدم مع نطاقاتها من نفس شاشة حفظ المستخدم.
     * تتجاهل الطلب تماماً إن لم تكن كتلة الأدوار معروضة في النموذج (لا صلاحية إدارة أدوار).
     */
    private function syncRoleAssignments(User $user, Request $request): void
    {
        if (!$request->has('roles')) {
            return;
        }

        $actor = $request->user();

        $canManage = $actor->type === 'super-admin' || PermissionHelper::can($actor, Group::class, 'edit');
        if (!$canManage) {
            return; // الشاشة لم تُظهر الكتلة أصلاً — تجاهل أي تسريب يدوي للـ payload
        }

        if ($actor->type !== 'super-admin' && $user->id === $actor->id) {
            abort(403, 'لا يمكنك تغيير أدوار حسابك من شاشة المستخدمين — اطلب ذلك من مشرف أعلى.');
        }

        if ($user->type === 'super-admin') {
            abort(403, 'حسابات السوبر-أدن تتجاوز نظام الصلاحيات ولا تُسنَد لها أدوار.');
        }

        $request->validate([
            'roles' => 'nullable|array',
            'roles.*.center_id' => 'nullable|integer|exists:centers,id',
            'roles.*.project_id' => 'nullable|integer|exists:projects,id',
            'roles.*.cohort_id' => 'nullable|integer|exists:cohorts,id',
        ]);

        $roleIds = Group::roles()->pluck('id');

        $wanted = collect($request->input('roles', []))
            ->filter(fn($row) => is_array($row) && !empty($row['enabled'] ?? null))
            ->only($roleIds->all());

        $current = $user->groups()->where('groups.kind', Group::KIND_ROLE)->get();

        $summary = [];

        foreach ($current as $role) {
            if (!$wanted->has($role->id)) {
                $user->groups()->detach($role->id);
                $summary[] = "سحب: {$role->name}";
            }
        }

        foreach ($wanted as $roleId => $row) {
            $scope = [
                'center_id' => $row['center_id'] ?: null,
                'project_id' => $row['project_id'] ?: null,
                'cohort_id' => $row['cohort_id'] ?: null,
            ];

            if ($current->contains('id', $roleId)) {
                $user->groups()->updateExistingPivot($roleId, $scope);
            } else {
                $user->groups()->attach($roleId, $scope);
            }

            $summary[] = 'إسناد: ' . ($roleIds->contains($roleId) ? Group::find($roleId)->name : "#$roleId");
        }

        if ($summary) {
            AuditLogger::recordEvent(
                modelClass: User::class,
                modelId: $user->id,
                event: 'roles_synced',
                description: 'تحديث أدوار المستخدم ' . $user->name . ' من شاشة المستخدم: ' . implode(' | ', $summary),
                oldValues: $current->mapWithKeys(fn($r) => [$r->id => [$r->pivot->center_id, $r->pivot->project_id, $r->pivot->cohort_id]])->all(),
                newValues: $wanted->all(),
            );
        }
    }

    public function update(Request $request, User $user)
    {
        $this->assertSuperAdminAllowed($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
            'type' => 'nullable|string|in:employee,beneficiary,student,super-admin',
            'student_id' => 'nullable|integer|exists:students,id',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'job_title_id' => 'nullable|exists:hr_job_positions,id',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            // عند تغيير كلمة المرور من قبل الأدمن، يجب على المستخدم تغييرها مرة أخرى
            $validated['must_change_password'] = true;
            $validated['activation_email_sent_at'] = null;
            $validated['activation_email_count'] = 0;
        }

        $this->assertSuperAdminAllowed(null, $validated['type'] ?? null);

        // Unlink old record if type changed
        $oldType = $user->getOriginal('type');
        if ($oldType !== null && $oldType !== ($validated['type'] ?? $oldType)) {
            $this->unlinkRecord($user, $oldType);
        }

        $user->update($validated);

        $this->linkRecord($user, $validated);
        $this->syncRoleAssignments($user, $request);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث المستخدم بنجاح');
    }

    private function linkRecord(User $user, array $data): void
    {
        if (($data['type'] ?? $user->type) === 'student' && !empty($data['student_id'])) {
            \App\Models\Admin\Student\Student::where('id', $data['student_id'])->update(['user_id' => $user->id]);
        } elseif (($data['type'] ?? $user->type) === 'employee' && !empty($data['employee_id'])) {
            \App\Models\Admin\Hr\Employee::where('id', $data['employee_id'])->update(['user_id' => $user->id]);
        }
    }

    private function unlinkRecord(User $user, string $type): void
    {
        if ($type === 'student') {
            \App\Models\Admin\Student\Student::where('user_id', $user->id)->update(['user_id' => null]);
        } elseif ($type === 'employee') {
            \App\Models\Admin\Hr\Employee::where('user_id', $user->id)->update(['user_id' => null]);
        }
    }

    public function destroy(User $user)
    {
        if ($user->type === 'super-admin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'لا يمكن حذف مستخدم من نوع سوبر أدمن');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function toggleStatus(User $user)
    {
        $this->assertSuperAdminAllowed($user);

        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تغيير حالة المستخدم بنجاح');
    }

    public function export(Request $request)
    {
        return Excel::download(new UserExport($request->query()), 'users.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new UserImport, $request->file('file'));

        return redirect()->route('admin.users.index')
            ->with('success', 'تم استيراد المستخدمين بنجاح');
    }
}
