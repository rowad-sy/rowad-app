<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Hr\Employee,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Hr\Employee,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $employees = Employee::with(['center', 'department', 'project'])
            ->when($search, function ($q, $search) {
                return $q->where('first_name_ar', 'like', "%{$search}%")
                    ->orWhere('last_name_ar', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            })->orderBy('id', 'desc')->paginate(10);

        return view('admin.hr.employees.index', compact('employees', 'search'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name_ar')->get();
        $projects = Project::orderBy('name')->get();
        $positions = JobPosition::orderBy('title_ar')->get();

        return view('admin.hr.employees.form', compact('centers', 'departments', 'projects', 'positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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

        return view('admin.hr.employees.form', compact('employee', 'centers', 'departments', 'projects', 'positions'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
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
