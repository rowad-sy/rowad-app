<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Group;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Group,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Group,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Group,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Group,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $groups = Group::withCount('users')
            ->where('kind', Group::KIND_GROUP)
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })->orderBy('name')->paginate(10);

        return view('admin.groups.index', compact('groups', 'search'));
    }

    /*
     * شاشة المجموعات تدير نوع "group" فقط — الأدوار (kind=role) تُدار من شاشة الأدوار.
     */
    private function assertIsGroup(Group $group): void
    {
        abort_unless(! $group->isRole(), 404, 'هذا الكيان "دور" — إدارته من شاشة الأدوار والنطاقات.');
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('admin.groups.form', compact('users'));
    }

    /*
     * منع الالتحاق الذاتي بالمجموعات لغير السوبر-أدن (W4):
     * المجموعة قد تحمل صلاحيات — إضافتك لنفسك إليها = منح صلاحيات لنفسك.
     * المنح المشروع يتم من شاشة "الأدوار والنطاقات" أو شاشة الصلاحيات للمجموعات.
     */
    private function assertNoSelfEnrollment(array $userIds): void
    {
        if (auth()->user()->type !== 'super-admin' && in_array((int) auth()->id(), array_map('intval', $userIds), true)) {
            abort(403, 'لا يمكنك إضافة نفسك إلى مجموعة — اطلب من مشرف أعلى منحك الدور من شاشة الأدوار.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')],
            'description' => 'nullable|string',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ], [
            'name.unique' => 'هذا الاسم مستخدم في النظام (دور أو مجموعة) — الأسماء فريدة على مستوى النظام.',
        ]);

        $this->assertNoSelfEnrollment($validated['users'] ?? []);

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'kind' => Group::KIND_GROUP,
        ]);

        if (!empty($validated['users'])) {
            $oldIds = $group->users()->pluck('users.id')->toArray();
            $group->users()->attach($validated['users']);
            $newIds = $group->users()->pluck('users.id')->toArray();
            AuditLogger::logPivot($group, 'users', $oldIds, $newIds, "إضافة مستخدمين للمجموعة {$group->name}");
        }

        return redirect()->route('admin.groups.index')
            ->with('success', 'تم إضافة المجموعة بنجاح');
    }

    public function edit(Group $group)
    {
        $this->assertIsGroup($group);

        $group->load('users');
        $users = User::orderBy('name')->get();
        return view('admin.groups.form', compact('group', 'users'));
    }

    public function update(Request $request, Group $group)
    {
        $this->assertIsGroup($group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')->ignore($group->id)],
            'description' => 'nullable|string',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ], [
            'name.unique' => 'هذا الاسم مستخدم في النظام (دور أو مجموعة) — الأسماء فريدة على مستوى النظام.',
        ]);

        $newIds = array_map('intval', $validated['users'] ?? []);
        $this->assertNoSelfEnrollment($newIds);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $oldIds = $group->users()->pluck('users.id')->map(fn($id) => (int) $id)->all();

        /*
         * دمج بالفروق (diff) بدل sync() — لأن sync تحذف صفوف الـ pivot وتعيد
         * إنشاءها فيمحو نطاق العضوية (نظام الأدوار §17) المسجل عليها.
         */
        $toDetach = array_values(array_diff($oldIds, $newIds));
        $toAttach = array_values(array_diff($newIds, $oldIds));

        if ($toDetach) {
            $group->users()->detach($toDetach);
        }

        if ($toAttach) {
            $group->users()->attach($toAttach);
        }

        AuditLogger::logPivot($group, 'users', $oldIds, $newIds, "تحديث مستخدمي المجموعة {$group->name}");

        return redirect()->route('admin.groups.index')
            ->with('success', 'تم تحديث المجموعة بنجاح');
    }

    public function destroy(Group $group)
    {
        $oldIds = $group->users()->pluck('users.id')->toArray();
        $group->users()->detach();
        if (!empty($oldIds)) {
            AuditLogger::logPivot($group, 'users', $oldIds, [], "حذف جميع المستخدمين من المجموعة {$group->name}");
        }
        $group->permissions()->delete();
        $group->delete();

        return redirect()->route('admin.groups.index')
            ->with('success', 'تم حذف المجموعة بنجاح');
    }
}
