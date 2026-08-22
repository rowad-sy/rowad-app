<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Permission,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Permission,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Permission,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Permission,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $permissions = Permission::with(['user', 'group', 'center', 'project'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('group', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderBy('id', 'desc')->paginate(10);

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $modelGroups = $this->modelGroups();
        $availableModels = array_merge(...array_map('array_values', array_values($modelGroups)));

        return view('admin.permissions.form', compact('users', 'groups', 'centers', 'projects', 'modelGroups', 'availableModels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assign_to' => 'required|in:user,group',
            'user_id' => 'required_if:assign_to,user|nullable|exists:users,id',
            'group_id' => 'required_if:assign_to,group|nullable|exists:groups,id',
            'model_names' => 'required|array|min:1',
            'model_names.*' => 'required|string|max:255',
            'model_id' => 'nullable|integer',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
        ]);

        Permission::create([
            'user_id' => $validated['assign_to'] === 'user' ? $validated['user_id'] : null,
            'group_id' => $validated['assign_to'] === 'group' ? $validated['group_id'] : null,
            'model_names' => $validated['model_names'],
            'model_id' => $validated['model_id'] ?? null,
            'center_id' => $validated['center_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'can_view' => $validated['can_view'] ?? false,
            'can_create' => $validated['can_create'] ?? false,
            'can_edit' => $validated['can_edit'] ?? false,
            'can_delete' => $validated['can_delete'] ?? false,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم إضافة الصلاحية بنجاح');
    }

    public function edit(Permission $permission)
    {
        $users = User::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $modelGroups = $this->modelGroups();
        $availableModels = array_merge(...array_values($modelGroups));

        return view('admin.permissions.form', compact('permission', 'users', 'groups', 'centers', 'projects', 'modelGroups', 'availableModels'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'assign_to' => 'required|in:user,group',
            'user_id' => 'required_if:assign_to,user|nullable|exists:users,id',
            'group_id' => 'required_if:assign_to,group|nullable|exists:groups,id',
            'model_names' => 'required|array|min:1',
            'model_names.*' => 'required|string|max:255',
            'model_id' => 'nullable|integer',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
        ]);

        $permission->update([
            'user_id' => $validated['assign_to'] === 'user' ? $validated['user_id'] : null,
            'group_id' => $validated['assign_to'] === 'group' ? $validated['group_id'] : null,
            'model_names' => $validated['model_names'],
            'model_id' => $validated['model_id'] ?? null,
            'center_id' => $validated['center_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'can_view' => $validated['can_view'] ?? false,
            'can_create' => $validated['can_create'] ?? false,
            'can_edit' => $validated['can_edit'] ?? false,
            'can_delete' => $validated['can_delete'] ?? false,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم تحديث الصلاحية بنجاح');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }

    private function modelGroups(): array
    {
        return [
            'الإدارة' => [
                'App\Models\Admin\Center' => 'المراكز',
                'App\Models\Admin\Project' => 'المشاريع',
                'App\Models\Admin\Department' => 'الإدارات',
                'App\Models\User' => 'المستخدمين',
                'App\Models\Admin\Group' => 'المجموعات',
                'App\Models\Admin\Permission' => 'الصلاحيات',
            ],
            'الموارد البشرية' => [
                'App\Models\Admin\Hr\Employee' => 'الموظفين',
                'App\Models\Admin\Hr\JobPosition' => 'المناصب الوظيفية',
                'App\Models\Admin\Hr\Warning' => 'التنبيهات',
                'App\Models\Admin\Hr\LeaveType' => 'سياسة الإجازات',
                'App\Models\Admin\Hr\LeaveRequest' => 'طلبات الإجازات',
                'App\Models\Admin\Hr\EmployeeAttendance' => 'دوام الموظفين',
            ],
            'الطلاب' => [
                'App\Models\Admin\Student\Student' => 'الطلاب',
                'App\Models\Admin\Student\Course' => 'الدورات',
                'App\Models\Admin\Student\Period' => 'الفترات',
                'App\Models\Admin\Student\StudentEnrollment' => 'التسجيلات',
                'App\Models\Admin\Student\Attendance' => 'الحضور',
                'App\Models\Admin\Student\Certificate' => 'الشهادات',
                'App\Models\Admin\Student\CertificateDesign' => 'تصاميم الشهادات',
            ],
            'التقنية' => [
                'App\Models\Admin\Tech\TechIssue' => 'التذاكر',
                'App\Models\Admin\Tech\TechEquipment' => 'المعدات',
            ],
            'اللوجستي' => [
                'App\Models\Admin\Logistics\PurchaseRequest' => 'طلبات الشراء',
                'App\Models\Admin\Logistics\ApprovalRule' => 'قواعد الموافقات',
                'App\Models\Admin\Logistics\Warehouse' => 'المخازن',
                'App\Models\Admin\Logistics\Asset' => 'الأصول',
                'App\Models\Admin\Logistics\LogisticsSetting' => 'إعدادات اللوجستي',
            ],
            'إدارة المشاريع' => [
                'App\Models\Admin\ProjectTask' => 'المهام',
            ],
            'النظام والتدقيق' => [
                'App\Models\AuditLog' => 'سجل التدقيق',
            ],
            'الصفحات' => [
                'page:admin.logistics.statistics' => 'إحصائيات اللوجستي',
            ],
        ];
    }
}
