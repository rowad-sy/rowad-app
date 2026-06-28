<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Student,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Student,create')->only(['create', 'store', 'createUser']);
        $this->middleware('permission:App\Models\Admin\Student\Student,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Student,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $gender = $request->input('gender');
        $perPage = (int) $request->input('per_page', 10);

        // Default filters to current user's center/project
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $centerId = $request->has('center_id') ? $request->input('center_id') : ($userEmployee?->center_id ?? '');
        $projectId = $request->has('project_id') ? $request->input('project_id') : ($userEmployee?->project_id ?? '');

        $students = Student::with(['center', 'project'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('first_name_ar', 'like', "%{$search}%")
                        ->orWhere('last_name_ar', 'like', "%{$search}%")
                        ->orWhere('student_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($gender && $gender !== 'all', function ($q) use ($gender) {
                return $q->where('gender', $gender);
            })
            ->when($centerId, function ($q, $centerId) {
                return $q->where('center_id', $centerId);
            })
            ->when($projectId, function ($q, $projectId) {
                return $q->where('project_id', $projectId);
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'center_id', 'project_id', 'gender', 'per_page']));

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'search', 'status', 'centerId', 'projectId', 'gender', 'perPage', 'centers', 'projects'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Default center/project from current user's employee record
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $defaultCenterId = $userEmployee?->center_id;
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.students.form', compact('centers', 'projects', 'users', 'defaultCenterId', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'student_code' => 'required|string|max:20|unique:students,student_code',
            'first_name_ar' => 'required|string|max:100',
            'last_name_ar' => 'required|string|max:100',
            'first_name_en' => 'nullable|string|max:100',
            'last_name_en' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:active,inactive,graduated,suspended',
            'enrollment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        Student::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'تم إضافة الطالب بنجاح');
    }

    public function edit(Student $student)
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.students.form', compact('student', 'centers', 'projects', 'users'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'student_code' => 'required|string|max:20|unique:students,student_code,' . $student->id,
            'first_name_ar' => 'required|string|max:100',
            'last_name_ar' => 'required|string|max:100',
            'first_name_en' => 'nullable|string|max:100',
            'last_name_en' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:active,inactive,graduated,suspended',
            'enrollment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $student->update($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'تم تحديث بيانات الطالب بنجاح');
    }

    public function show(Student $student)
    {
        // Allow if user has permission OR is the student owner
        if (!\App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Student\Student', 'view') && auth()->id() !== $student->user_id) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
        }

        $student->load(['center', 'project', 'user', 'enrollments.course', 'enrollments.period', 'certificates.design', 'certificates.enrollment.course']);

        $attendanceSummary = $student->attendance()
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentAttendance = $student->attendance()
            ->with('createdBy')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return view('admin.students.profile', compact('student', 'attendanceSummary', 'recentAttendance'));
    }

    public function createUser(Student $student)
    {
        if ($student->user_id) {
            return redirect()->route('admin.students.show', $student)
                ->with('error', 'الطالب لديه حساب مستخدم بالفعل');
        }

        $email = $student->email ?? $student->student_code . '@student.rowad.app';
        $password = 'student123';

        $user = User::create([
            'name' => $student->first_name_ar . ' ' . $student->last_name_ar,
            'email' => $email,
            'password' => bcrypt($password),
            'type' => 'student',
            'is_active' => true,
        ]);

        $student->update(['user_id' => $user->id]);

        return redirect()->route('admin.students.show', $student)
            ->with('success', "تم إنشاء حساب المستخدم بنجاح. البريد: {$user->email} | كلمة المرور: {$password}");
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')
            ->with('success', 'تم حذف الطالب بنجاح');
    }
}
