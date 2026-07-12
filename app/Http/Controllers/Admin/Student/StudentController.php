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
        $this->middleware('permission:App\Models\Admin\Student\Student,create')->only(['create', 'store', 'createUser', 'addToProjects']);
        $this->middleware('permission:App\Models\Admin\Student\Student,view')->only(['checkIdentity']);
        $this->middleware('permission:App\Models\Admin\Student\Student,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Student,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $gender = $request->input('gender');
        $perPage = (int) $request->input('per_page', 10);

        // Default scope from user's permission
        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Student\Student');
        $centerId = $request->filled('center_id') ? $request->input('center_id') : (count($scope['center_ids']) === 1 ? $scope['center_ids'][0] : '');
        $projectId = $request->filled('project_id') ? $request->input('project_id') : (count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : '');

        $students = Student::with(['center', 'projects'])
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
                return $q->where(function ($q) use ($projectId) {
                    $q->where('project_id', $projectId)
                      ->orWhereHas('projects', function ($q) use ($projectId) {
                          $q->where('projects.id', $projectId);
                      });
                });
            })
            // Force scope from permission if user didn't explicitly override
            ->unless($scope['sees_all'] || $request->filled('center_id'), function ($q) use ($scope) {
                if (!empty($scope['center_ids'])) {
                    $q->whereIn('center_id', $scope['center_ids']);
                }
            })
            ->unless($scope['sees_all'] || $request->filled('project_id'), function ($q) use ($scope) {
                if (!empty($scope['project_ids'])) {
                    $q->where(function ($q) use ($scope) {
                        $q->whereIn('project_id', $scope['project_ids'])
                          ->orWhereHas('projects', function ($q) use ($scope) {
                              $q->whereIn('projects.id', $scope['project_ids']);
                          });
                    });
                }
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
        $courses = \App\Models\Admin\Student\Course::with('project')->orderBy('name_ar')->get();
        $periods = \App\Models\Admin\Student\Period::orderBy('name_ar')->get();

        // Default center/project from current user's employee record
        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $defaultCenterId = $userEmployee?->center_id;
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.students.form', compact('centers', 'projects', 'users', 'courses', 'periods', 'defaultCenterId', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'student_code' => 'required|string|max:20|unique:students,student_code',
            'identity_type' => 'nullable|string|max:50',
            'identity_number' => 'nullable|string|max:50',
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
            'sync_project_ids' => 'nullable|in:1',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'status' => 'required|in:active,inactive,graduated,suspended',
            'enrollment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'enrollments' => 'nullable|array',
            'enrollments.*.course_id' => 'required|exists:courses,id',
            'enrollments.*.period_id' => 'required|exists:periods,id',
            'enrollments.*.enrollment_date' => 'nullable|date',
            'enrollments.*.status' => 'nullable|in:enrolled,completed,dropped',
            'enrollments.*.grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $student = Student::create($validated);

        if ($request->has('sync_project_ids')) {
            $student->projects()->sync($request->project_ids ?? []);
        }

        if ($request->has('enrollments')) {
            foreach ($request->enrollments as $enrollment) {
                $student->enrollments()->create([
                    'course_id' => $enrollment['course_id'],
                    'period_id' => $enrollment['period_id'],
                    'enrollment_date' => $enrollment['enrollment_date'] ?? now(),
                    'status' => $enrollment['status'] ?? 'enrolled',
                    'grade' => $enrollment['grade'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'تم إضافة الطالب بنجاح');
    }

    public function edit(Student $student)
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $courses = \App\Models\Admin\Student\Course::with('project')->orderBy('name_ar')->get();
        $periods = \App\Models\Admin\Student\Period::orderBy('name_ar')->get();

        $student->load('enrollments', 'projects');

        return view('admin.students.form', compact('student', 'centers', 'projects', 'users', 'courses', 'periods'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'student_code' => 'required|string|max:20|unique:students,student_code,' . $student->id,
            'identity_type' => 'nullable|string|max:50',
            'identity_number' => 'nullable|string|max:50',
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
            'sync_project_ids' => 'nullable|in:1',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'status' => 'required|in:active,inactive,graduated,suspended',
            'enrollment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'enrollments' => 'nullable|array',
            'enrollments.*.course_id' => 'required|exists:courses,id',
            'enrollments.*.period_id' => 'required|exists:periods,id',
            'enrollments.*.enrollment_date' => 'nullable|date',
            'enrollments.*.status' => 'nullable|in:enrolled,completed,dropped',
            'enrollments.*.grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $student->update($validated);

        if ($request->has('sync_project_ids')) {
            $student->projects()->sync($request->project_ids ?? []);
        }

        if ($request->has('enrollments')) {
            foreach ($request->enrollments as $enrollment) {
                $exists = $student->enrollments()
                    ->where('course_id', $enrollment['course_id'])
                    ->where('period_id', $enrollment['period_id'])
                    ->exists();

                if (!$exists) {
                    $student->enrollments()->create([
                        'course_id' => $enrollment['course_id'],
                        'period_id' => $enrollment['period_id'],
                        'enrollment_date' => $enrollment['enrollment_date'] ?? now(),
                        'status' => $enrollment['status'] ?? 'enrolled',
                        'grade' => $enrollment['grade'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'تم تحديث بيانات الطالب بنجاح');
    }

    public function show(Student $student)
    {
        // Allow if user has permission OR is the student owner
        if (!\App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Student\Student', 'view') && auth()->id() !== $student->user_id) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
        }

        $student->load(['center', 'project', 'projects', 'user', 'enrollments.course', 'enrollments.period', 'certificates.design', 'certificates.enrollment.course']);

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

    public function checkIdentity(Request $request)
    {
        $request->validate(['identity_number' => 'required|string|max:50']);

        $student = Student::where('identity_number', $request->identity_number)->with('projects')->first();

        if (!$student) {
            return response()->json([
                'found' => false,
                'redirect' => route('admin.students.create', ['identity_number' => $request->identity_number]),
            ]);
        }

        $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
        $sameCenter = $userEmployee && $userEmployee->center_id && $student->center_id === $userEmployee->center_id;
        $sameProject = $userEmployee && $userEmployee->project_id && $student->projects->contains($userEmployee->project_id);

        if ($sameCenter && $sameProject) {
            return response()->json([
                'found' => true,
                'can_edit' => true,
                'redirect' => route('admin.students.edit', $student),
            ]);
        }

        $availableProjects = Project::whereNotIn('id', $student->projects->pluck('id'))->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'found' => true,
            'can_edit' => false,
            'student' => [
                'id' => $student->id,
                'name' => $student->first_name_ar . ' ' . $student->last_name_ar,
                'code' => $student->student_code,
                'projects' => $student->projects->pluck('name'),
            ],
            'available_projects' => $availableProjects,
        ]);
    }

    public function addToProjects(Request $request, Student $student)
    {
        $request->validate([
            'project_ids' => 'required|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        foreach ($request->project_ids as $projectId) {
            $existing = \DB::table('project_student')
                ->where('project_id', $projectId)
                ->where('student_id', $student->id)
                ->first();

            if ($existing) {
                if ($existing->deleted_at !== null) {
                    \DB::table('project_student')
                        ->where('project_id', $projectId)
                        ->where('student_id', $student->id)
                        ->update(['deleted_at' => null]);
                }
            } else {
                \DB::table('project_student')->insert([
                    'project_id' => $projectId,
                    'student_id' => $student->id,
                ]);
            }
        }

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'تم إضافة الطالب إلى المشاريع المحددة بنجاح');
    }

    public function destroy(Request $request, Student $student)
    {
        $projectId = $request->input('project_id');

        if (!$projectId) {
            $userEmployee = \App\Models\Admin\Hr\Employee::where('user_id', auth()->id())->first();
            $projectId = $userEmployee?->project_id;
        }

        if ($projectId) {
            $student->projects()->updateExistingPivot($projectId, ['deleted_at' => now()]);

            if ($student->project_id == $projectId) {
                $student->update(['project_id' => null]);
            }

            return redirect()->route('admin.students.index')
                ->with('success', 'تم إزالة الطالب من المشروع بنجاح');
        }

        $student->delete();
        return redirect()->route('admin.students.index')
            ->with('success', 'تم حذف الطالب بنجاح');
    }
}
