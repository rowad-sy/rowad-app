<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Cohort;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

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
        $this->middleware('permission:App\Models\Admin\Group,edit')->only(['assign', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $roles = Group::withCount('users')
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
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

    public function edit(Group $group, User $user)
    {
        $membership = $this->findMembership($group, $user);

        $data = $this->formData();
        $data['group'] = $group;
        $data['user'] = $user;
        $data['pivot'] = $membership->pivot;

        return view('admin.roles.edit', $data);
    }

    public function update(Request $request, Group $group, User $user)
    {
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

    public function destroy(Group $group, User $user)
    {
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
            'roles' => Group::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'cohorts' => Cohort::with('project')->orderBy('name')->get(),
        ];
    }
}
