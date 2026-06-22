<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\Employee,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 10);

        // Default filters to current user's center/project (only when filter not explicitly submitted)
        $userEmployee = Employee::where('user_id', auth()->id())->first();
        $centerId = $request->has('center_id') ? $request->input('center_id') : ($userEmployee?->center_id ?? '');
        $projectId = $request->has('project_id') ? $request->input('project_id') : ($userEmployee?->project_id ?? '');

        $employees = Employee::with(['center', 'department', 'project'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('first_name_ar', 'like', "%{$search}%")
                        ->orWhere('last_name_ar', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%");
                });
            })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($centerId, function ($q, $centerId) {
                return $q->where('center_id', $centerId);
            })
            ->when($projectId, function ($q, $projectId) {
                return $q->where('project_id', $projectId);
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'status', 'center_id', 'project_id', 'per_page']));

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.hr.employees.index', compact('employees', 'search', 'status', 'centerId', 'projectId', 'perPage', 'centers', 'projects'));
    }

    public function show(Employee $employee)
    {
        $user = auth()->user();

        // Allow viewing own profile without explicit permission
        $userEmployee = Employee::where('user_id', $user->id)->first();
        if (!$userEmployee || $userEmployee->id !== $employee->id) {
            if (!\App\Helpers\PermissionHelper::can($user, 'App\Models\Admin\Hr\Employee', 'view')) {
                abort(403);
            }
        }

        $employee->load([
            'center', 'department', 'project', 'user',
            'educations', 'contacts', 'workSchedules', 'contracts',
            'salaries', 'warnings', 'notesRelation.user', 'documents',
        ]);

        $position = \App\Models\Admin\Hr\JobPosition::find($employee->job_position_id);

        // Gather permission-based navigation links
        $navLinks = collect([
            ['route' => 'admin.home', 'label' => 'التطبيقات', 'icon' => 'bi-grid-3x3-gap', 'permission' => true],
            ['route' => 'admin.dashboard', 'label' => 'لوحة التحكم', 'icon' => 'bi-speedometer2', 'permission' => true],
            ['route' => 'admin.centers.index', 'label' => 'المراكز', 'icon' => 'bi-geo-alt', 'model' => 'App\Models\Admin\Center'],
            ['route' => 'admin.projects.index', 'label' => 'المشاريع', 'icon' => 'bi-briefcase', 'model' => 'App\Models\Admin\Project'],
            ['route' => 'admin.departments.index', 'label' => 'الإدارات', 'icon' => 'bi-diagram-3', 'model' => 'App\Models\Admin\Department'],
            ['route' => 'admin.groups.index', 'label' => 'المجموعات', 'icon' => 'bi-people', 'model' => 'App\Models\Admin\Group'],
            ['route' => 'admin.permissions.index', 'label' => 'الصلاحيات', 'icon' => 'bi-shield-check', 'model' => 'App\Models\Admin\Permission'],
            ['route' => 'admin.users.index', 'label' => 'المستخدمين', 'icon' => 'bi-person-badge', 'model' => 'App\Models\User'],
            ['route' => 'admin.hr.employees.index', 'label' => 'الموظفين', 'icon' => 'bi-person-workspace', 'model' => 'App\Models\Admin\Hr\Employee'],
            ['route' => 'admin.hr.job-positions.index', 'label' => 'المناصب الوظيفية', 'icon' => 'bi-badge-tm', 'model' => 'App\Models\Admin\Hr\JobPosition'],
            ['route' => 'admin.students.index', 'label' => 'الطلاب', 'icon' => 'bi-mortarboard', 'model' => 'App\Models\Admin\Student\Student'],
            ['route' => 'admin.students.courses.index', 'label' => 'المقررات', 'icon' => 'bi-book', 'model' => 'App\Models\Admin\Student\Course'],
            ['route' => 'admin.students.periods.index', 'label' => 'الفترات', 'icon' => 'bi-calendar-range', 'model' => 'App\Models\Admin\Student\Period'],
            ['route' => 'admin.students.attendance', 'label' => 'الحضور', 'icon' => 'bi-clipboard-check', 'model' => 'App\Models\Admin\Student\Attendance'],
            ['route' => 'admin.students.statistics', 'label' => 'الإحصائيات', 'icon' => 'bi-bar-chart', 'model' => 'App\Models\Admin\Student\Student'],
            ['route' => 'admin.students.certificates.index', 'label' => 'الشهادات', 'icon' => 'bi-file-earmark-check', 'model' => 'App\Models\Admin\Student\Certificate'],
            ['route' => 'admin.tech.issues.index', 'label' => 'التذاكر الفنية', 'icon' => 'bi-ticket', 'model' => 'App\Models\Admin\Tech\TechIssue'],
            ['route' => 'admin.tech.equipment.index', 'label' => 'المعدات التقنية', 'icon' => 'bi-pc-display', 'model' => 'App\Models\Admin\Tech\TechEquipment'],
        ])->filter(function ($link) use ($user) {
            if (isset($link['model'])) {
                return \App\Helpers\PermissionHelper::can($user, $link['model'], 'view');
            }
            return true;
        });

        return view('admin.hr.employees.show', compact('employee', 'position', 'navLinks'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name_ar')->get();
        $projects = Project::orderBy('name')->get();
        $positions = JobPosition::orderBy('title_ar')->get();
        $users = User::orderBy('name')->get();

        // Default center/project from current user's employee record
        $userEmployee = Employee::where('user_id', auth()->id())->first();
        $defaultCenterId = $userEmployee?->center_id;
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.hr.employees.form', compact(
            'centers', 'departments', 'projects', 'positions', 'users',
            'defaultCenterId', 'defaultProjectId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code',
            'status' => 'required|in:active,inactive',
            'id_number' => 'nullable|string|max:50',
            'first_name_ar' => 'required|string|max:100',
            'last_name_ar' => 'required|string|max:100',
            'first_name_en' => 'nullable|string|max:100',
            'last_name_en' => 'nullable|string|max:100',
            'father_name_ar' => 'nullable|string|max:100',
            'father_name_en' => 'nullable|string|max:100',
            'mother_name_ar' => 'nullable|string|max:100',
            'mother_name_en' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'children_count' => 'nullable|integer|min:0',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'center_id' => 'nullable|exists:centers,id',
            'department_id' => 'nullable|exists:departments,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::create($validated);

        $this->syncRelations($request, $employee);

        return redirect()->route('admin.hr.employees.index')
            ->with('success', 'تم إضافة الموظف بنجاح');
    }

    public function edit(Employee $employee)
    {
        $employee->load(['educations', 'contacts', 'workSchedules', 'contracts', 'salaries', 'warnings', 'notesRelation.user', 'documents']);

        $centers = Center::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name_ar')->get();
        $projects = Project::orderBy('name')->get();
        $positions = JobPosition::orderBy('title_ar')->get();
        $users = User::orderBy('name')->get();

        return view('admin.hr.employees.form', compact('employee', 'centers', 'departments', 'projects', 'positions', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code,' . $employee->id,
            'status' => 'required|in:active,inactive',
            'id_number' => 'nullable|string|max:50',
            'first_name_ar' => 'required|string|max:100',
            'last_name_ar' => 'required|string|max:100',
            'first_name_en' => 'nullable|string|max:100',
            'last_name_en' => 'nullable|string|max:100',
            'father_name_ar' => 'nullable|string|max:100',
            'father_name_en' => 'nullable|string|max:100',
            'mother_name_ar' => 'nullable|string|max:100',
            'mother_name_en' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'children_count' => 'nullable|integer|min:0',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'center_id' => 'nullable|exists:centers,id',
            'department_id' => 'nullable|exists:departments,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
        ]);

        $employee->update($validated);

        $this->syncRelations($request, $employee);

        return redirect()->route('admin.hr.employees.index')
            ->with('success', 'تم تحديث بيانات الموظف بنجاح');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('admin.hr.employees.index')
            ->with('success', 'تم حذف الموظف بنجاح');
    }

    private function syncRelations(Request $request, Employee $employee): void
    {
        // Educations
        $employee->educations()->delete();
        if ($request->has('educations')) {
            foreach ($request->input('educations', []) as $edu) {
                if (!empty($edu['qualification'])) {
                    $employee->educations()->create($edu);
                }
            }
        }

        // Contacts
        $employee->contacts()->delete();
        if ($request->has('contacts')) {
            foreach ($request->input('contacts', []) as $contact) {
                if (!empty($contact['type']) && !empty($contact['value'])) {
                    $employee->contacts()->create($contact);
                }
            }
        }

        // Work Schedules
        $employee->workSchedules()->delete();
        if ($request->has('work_schedules')) {
            foreach ($request->input('work_schedules', []) as $ws) {
                if (isset($ws['day_of_week'])) {
                    $employee->workSchedules()->create([
                        'day_of_week' => $ws['day_of_week'],
                        'start_time' => !empty($ws['is_day_off']) ? null : ($ws['start_time'] ?? null),
                        'end_time' => !empty($ws['is_day_off']) ? null : ($ws['end_time'] ?? null),
                        'is_day_off' => !empty($ws['is_day_off']),
                    ]);
                }
            }
        }

        // Contracts
        $employee->contracts()->delete();
        if ($request->has('contracts')) {
            foreach ($request->input('contracts', []) as $contract) {
                if (!empty($contract['contract_type']) || !empty($contract['start_date'])) {
                    $employee->contracts()->create($contract);
                }
            }
        }

        // Salaries
        $employee->salaries()->delete();
        if ($request->has('salaries')) {
            foreach ($request->input('salaries', []) as $salary) {
                if (!empty($salary['base_salary']) || !empty($salary['total_salary'])) {
                    $employee->salaries()->create($salary);
                }
            }
        }

        // Documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $docType => $file) {
                if ($file) {
                    $path = $file->store("hr/employees/{$employee->employee_code}/{$docType}", 'public');
                    $employee->documents()->create([
                        'document_type' => $docType,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }
        }

        // Document checkboxes
        $docFlags = [
            'has_photo', 'has_cv', 'has_id_copy', 'has_qualification', 'has_experience_certs',
            'has_offer_letter', 'has_contract_doc', 'has_employee_data', 'has_job_description',
            'has_signature_movements', 'has_security_audit', 'has_reference_audit', 'has_code_of_conduct',
            'has_clearance', 'has_receipt', 'has_resignation',
            'has_verbal_warning_doc', 'has_written_warning_doc', 'has_termination_warning_doc',
            'has_termination_doc', 'has_blacklist_doc',
        ];
        foreach ($docFlags as $flag) {
            $employee->update([$flag => $request->boolean($flag)]);
        }

        // Warnings
        if ($request->has('warnings')) {
            foreach ($request->input('warnings', []) as $warning) {
                if (!empty($warning['date']) && !empty($warning['reason'])) {
                    $employee->warnings()->create([
                        'date' => $warning['date'],
                        'reason' => $warning['reason'],
                        'level' => $warning['level'] ?? 'verbal',
                    ]);
                }
            }
        }

        // Notes
        if ($request->has('notes_list')) {
            foreach ($request->input('notes_list', []) as $note) {
                if (!empty($note['note'])) {
                    $employee->notesRelation()->create([
                        'user_id' => auth()->id(),
                        'note' => $note['note'],
                    ]);
                }
            }
        }
    }
}
