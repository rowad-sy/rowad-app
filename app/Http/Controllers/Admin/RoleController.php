<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Cohort;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
 * واجهة "الأدوار والنطاقات" (نظام الأدوار §17)
 * ============================================
 * المجموعة = قالب دور (ماذا يعمل: الموديلات والأعلام من شاشة الصلاحيات).
 * هذه الشاشة = منح الدور (لمن؟ وأين يسري: مركز/مشروع/فوج يُخزَّن في عضوية group_user).
 *
 * قواعد الأمان المطبقة هنا:
 *  - الوصول لهذه الشاشة محكوم بصلاحية Group (view للاستعراض، edit للمنح والتعديل).
 *  - لا يستطيع غير السوبر-أدن منح دور لنفسه (منع التصعيد الذاتي — W4).
 *  - حسابات super-admin لا تُمنح أدوارًا (تتجاوز النظام أصلًا).
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Group,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Group,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Group,edit')->only(['assign', 'storeAssignment', 'editAssignment', 'updateAssignment', 'destroyAssignment', 'edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Group,delete')->only(['destroy']);
    }

    /*
     * --------------------------------------------
     * تعريف الأدوار (kind=role) — التسمية والوصف فقط.
     * «ماذا يعمل الدور» يُعرَّف من شاشة الصلاحيات (المصفوفة).
     * --------------------------------------------
     */

    public function create()
    {
        return view('admin.roles.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')],
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'هذا الاسم مستخدم في النظام (دور أو مجموعة) — الأسماء فريدة على مستوى النظام.',
        ]);

        $role = Group::create($validated + ['kind' => Group::KIND_ROLE]);

        return redirect()->route('admin.roles.index')
            ->with('success', "تم إنشاء الدور {$role->name} — عرّف صلاحياته من شاشة الصلاحيات ثم أسنده بالنطاق");
    }

    public function edit(Group $role)
    {
        $this->assertRole($role);

        return view('admin.roles.form', compact('role'));
    }

    public function update(Request $request, Group $role)
    {
        $this->assertRole($role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')->ignore($role->id)],
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'هذا الاسم مستخدم في النظام (دور أو مجموعة) — الأسماء فريدة على مستوى النظام.',
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'تم تحديث تعريف الدور');
    }

    public function destroy(Group $role)
    {
        $this->assertRole($role);

        $role->users()->detach();
        Permission::where('group_id', $role->id)->delete();
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'تم حذف الدور وكل إسناداته وصلاحياته');
    }

    private function assertRole(Group $group): void
    {
        abort_unless($group->isRole(), 404, 'هذا الكيان ليس دوراً.');
    }

    /*
     * --------------------------------------------
     * إسناد الأدوار بالنطاق
     * --------------------------------------------
     */

    public function index()
    {
        $roles = Group::roles()->withCount('users')
            ->with(['users' => fn($q) => $q->orderBy('users.name'), 'permissions'])
            ->orderBy('name')
            ->get();

        $assignments = $roles->flatMap(function ($role) {
            return $role->users->map(fn($user) => (object) [
                'role' => $role,
                'user' => $user,
                'pivot' => $user->pivot,
            ]);
        })->sortBy(fn($a) => [$a->role->name, $a->user->name])->values();

        return view('admin.roles.index', [
            'roles' => $roles,
            'assignments' => $assignments,
            'centerNames' => Center::pluck('name', 'id'),
            'projectNames' => Project::pluck('name', 'id'),
            'cohortNames' => Cohort::pluck('name', 'id'),
        ]);
    }

    public function assign()
    {
        return view('admin.roles.assign', $this->formData());
    }

    public function storeAssignment(Request $request)
    {
        $validated = $request->validate([
            'group_id' => ['required', Rule::exists('groups', 'id')->where('kind', Group::KIND_ROLE)],
            'user_id' => 'required|exists:users,id',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'cohort_id' => 'nullable|exists:cohorts,id',
        ]);

        $this->assertGrantAllowed((int) $validated['user_id']);

        $group = Group::findOrFail($validated['group_id']);
        $user = User::findOrFail($validated['user_id']);

        if ($group->users()->where('users.id', $user->id)->exists()) {
            return redirect()->route('admin.roles.assign')
                ->with('error', 'هذا المستخدم عضو بالفعل في هذا الدور — عدّل نطاقه من قائمة الإسنادات.')
                ->withInput();
        }

        $group->users()->attach($user->id, [
            'center_id' => $validated['center_id'] ?: null,
            'project_id' => $validated['project_id'] ?: null,
            'cohort_id' => $validated['cohort_id'] ?: null,
        ]);

        AuditLogger::recordEvent(
            modelClass: Group::class,
            modelId: $group->id,
            event: 'role_assigned',
            description: "منح دور {$group->name} للمستخدم {$user->name}",
            oldValues: null,
            newValues: $validated,
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "تم منح الدور {$group->name} للمستخدم {$user->name} بنجاح");
    }

    public function editAssignment(Group $group, User $user)
    {
        $this->assertRole($group);

        $membership = $this->findMembership($group, $user);

        $data = $this->formData();
        $data['group'] = $group;
        $data['user'] = $user;
        $data['pivot'] = $membership->pivot;

        return view('admin.roles.edit', $data);
    }

    public function updateAssignment(Request $request, Group $group, User $user)
    {
        $this->assertRole($group);

        $validated = $request->validate([
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'cohort_id' => 'nullable|exists:cohorts,id',
        ]);

        $membership = $this->findMembership($group, $user);

        $old = [
            'center_id' => $membership->pivot->center_id,
            'project_id' => $membership->pivot->project_id,
            'cohort_id' => $membership->pivot->cohort_id,
        ];

        $group->users()->updateExistingPivot($user->id, [
            'center_id' => $validated['center_id'] ?: null,
            'project_id' => $validated['project_id'] ?: null,
            'cohort_id' => $validated['cohort_id'] ?: null,
        ]);

        AuditLogger::recordEvent(
            modelClass: Group::class,
            modelId: $group->id,
            event: 'role_scope_updated',
            description: "تحديث نطاق دور {$group->name} للمستخدم {$user->name}",
            oldValues: $old,
            newValues: $validated,
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'تم تحديث نطاق الدور بنجاح');
    }

    public function destroyAssignment(Group $group, User $user)
    {
        $this->assertRole($group);

        $this->findMembership($group, $user);

        $group->users()->detach($user->id);

        AuditLogger::recordEvent(
            modelClass: Group::class,
            modelId: $group->id,
            event: 'role_revoked',
            description: "سحب دور {$group->name} من المستخدم {$user->name}",
            oldValues: null,
            newValues: ['user_id' => $user->id],
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'تم سحب الدور بنجاح');
    }

    /*
     * ضوابط المنح: لا منح للذات لغير السوبر-أدن، ولا أدوار لحسابات السوبر-أدن.
     */
    private function assertGrantAllowed(int $targetUserId): void
    {
        $actor = auth()->user();

        if ($actor->type !== 'super-admin' && $targetUserId === (int) $actor->id) {
            abort(403, 'لا يمكنك منح دور لنفسك — اطلب من مشرف أعلى.');
        }

        if (User::find($targetUserId)?->type === 'super-admin') {
            abort(403, 'حسابات السوبر-أدن تتجاوز نظام الصلاحيات ولا تحتاج منح أدوار.');
        }
    }

    private function findMembership(Group $group, User $user)
    {
        $membership = $group->users()->where('users.id', $user->id)->first();

        abort_if(!$membership, 404, 'هذه العضوية غير موجودة.');

        return $membership;
    }

    private function formData(): array
    {
        return [
            'roles' => Group::roles()->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'cohorts' => Cohort::with('project')->orderBy('name')->get(),
        ];
    }
}
