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
                return $q->where('model_name', 'like', "%{$search}%");
            })->orderBy('id', 'desc')->paginate(10);

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $availableModels = [
            'App\Models\Admin\Center' => 'المراكز',
            'App\Models\Admin\Project' => 'المشاريع',
            'App\Models\User' => 'المستخدمين',
            'App\Models\Admin\Group' => 'المجموعات',
            'App\Models\Admin\Permission' => 'الصلاحيات',
        ];

        return view('admin.permissions.form', compact('users', 'groups', 'centers', 'projects', 'availableModels'));
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

        $base = [
            'user_id' => $validated['assign_to'] === 'user' ? $validated['user_id'] : null,
            'group_id' => $validated['assign_to'] === 'group' ? $validated['group_id'] : null,
            'model_id' => $validated['model_id'] ?? null,
            'center_id' => $validated['center_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'can_view' => $validated['can_view'] ?? false,
            'can_create' => $validated['can_create'] ?? false,
            'can_edit' => $validated['can_edit'] ?? false,
            'can_delete' => $validated['can_delete'] ?? false,
        ];

        foreach ($validated['model_names'] as $model) {
            Permission::create(array_merge($base, ['model_name' => $model]));
        }

        $count = count($validated['model_names']);
        return redirect()->route('admin.permissions.index')
            ->with('success', "تم إضافة {$count} صلاحية بنجاح");
    }

    public function edit(Permission $permission)
    {
        $users = User::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $availableModels = [
            'App\Models\Admin\Center' => 'المراكز',
            'App\Models\Admin\Project' => 'المشاريع',
            'App\Models\User' => 'المستخدمين',
            'App\Models\Admin\Group' => 'المجموعات',
            'App\Models\Admin\Permission' => 'الصلاحيات',
        ];

        return view('admin.permissions.form', compact('permission', 'users', 'groups', 'centers', 'projects', 'availableModels'));
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

        $base = [
            'user_id' => $validated['assign_to'] === 'user' ? $validated['user_id'] : null,
            'group_id' => $validated['assign_to'] === 'group' ? $validated['group_id'] : null,
            'model_id' => $validated['model_id'] ?? null,
            'center_id' => $validated['center_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'can_view' => $validated['can_view'] ?? false,
            'can_create' => $validated['can_create'] ?? false,
            'can_edit' => $validated['can_edit'] ?? false,
            'can_delete' => $validated['can_delete'] ?? false,
        ];

        // تحديث الصلاحية الحالية بأول موديل
        $firstModel = array_shift($validated['model_names']);
        $permission->update(array_merge($base, ['model_name' => $firstModel]));

        // إنشاء صلاحيات جديدة للموديلات الإضافية
        $created = 0;
        foreach ($validated['model_names'] as $model) {
            Permission::create(array_merge($base, ['model_name' => $model]));
            $created++;
        }

        $msg = 'تم تحديث الصلاحية بنجاح';
        if ($created) {
            $msg .= " وتم إضافة {$created} صلاحية جديدة";
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', $msg);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }
}
