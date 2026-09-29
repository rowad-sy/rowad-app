<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Group;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

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
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.groups.index', compact('groups', 'search'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('admin.groups.form', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
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
        $group->load('users');
        $users = User::orderBy('name')->get();
        return view('admin.groups.form', compact('group', 'users'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $oldIds = $group->users()->pluck('users.id')->toArray();
        $group->users()->sync($validated['users'] ?? []);
        $newIds = $group->users()->pluck('users.id')->toArray();
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
