<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\User,view')->only(['index']);
        $this->middleware('permission:App\Models\User,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\User,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\User,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');
        $perPage = (int) $request->input('per_page', 10);

        $users = User::withCount('groups')
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                return $q->where('is_active', $status === 'active');
            })
            ->when($type && $type !== 'all', function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'type', 'per_page']));

        return view('admin.users.index', compact('users', 'search', 'status', 'type', 'perPage'));
    }

    public function create()
    {
        return view('admin.users.form');
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
        ]);

        $user = User::create($validated);

        $this->linkRecord($user, $validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إضافة المستخدم بنجاح');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
            'type' => 'nullable|string|in:employee,beneficiary,student,super-admin',
            'student_id' => 'nullable|integer|exists:students,id',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Unlink old record if type changed
        $oldType = $user->getOriginal('type');
        if ($oldType !== null && $oldType !== ($validated['type'] ?? $oldType)) {
            $this->unlinkRecord($user, $oldType);
        }

        $user->update($validated);

        $this->linkRecord($user, $validated);

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
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تغيير حالة المستخدم بنجاح');
    }
}
