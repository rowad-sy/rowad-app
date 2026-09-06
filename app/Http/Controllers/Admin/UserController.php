<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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

        return view('admin.users.form', compact('centers', 'projects', 'jobTitles'));
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

        $user = User::create($validated);

        $this->linkRecord($user, $validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إضافة المستخدم بنجاح. المستخدم غير نشط ويجب عليه تغيير كلمة المرور عند أول تسجيل دخول');
    }

    public function edit(User $user)
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $jobTitles = JobPosition::orderBy('title_ar')->get();

        return view('admin.users.form', compact('user', 'centers', 'projects', 'jobTitles'));
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
