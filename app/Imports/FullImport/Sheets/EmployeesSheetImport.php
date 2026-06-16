<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeesSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'employees';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = $row['employee_code'] ?? null;
            if (!$code) continue;

            $data = $this->buildData($row);
            $employee = Employee::updateOrCreate(['employee_code' => $code], $data);

            $this->parent->employeeMap[$code] = $employee->id;
        }
    }

    private function buildData(Collection $row): array
    {
        $data = [
            'first_name_ar' => $row['first_name_ar'] ?? null,
            'last_name_ar' => $row['last_name_ar'] ?? null,
            'first_name_en' => $row['first_name_en'] ?? null,
            'last_name_en' => $row['last_name_en'] ?? null,
            'father_name_ar' => $row['father_name_ar'] ?? null,
            'father_name_en' => $row['father_name_en'] ?? null,
            'mother_name_ar' => $row['mother_name_ar'] ?? null,
            'mother_name_en' => $row['mother_name_en'] ?? null,
            'status' => $row['status'] ?? 'active',
            'id_number' => $row['id_number'] ?? null,
            'gender' => $row['gender'] ?? 'male',
            'nationality' => $row['nationality'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'birth_place' => $row['birth_place'] ?? null,
            'marital_status' => $row['marital_status'] ?? null,
            'children_count' => $row['children_count'] ?? 0,
            'notes' => $row['notes'] ?? null,
        ];

        foreach ([
            'has_photo', 'has_cv', 'has_id_copy', 'has_qualification', 'has_experience_certs',
            'has_offer_letter', 'has_contract_doc', 'has_employee_data', 'has_job_description',
            'has_signature_movements', 'has_security_audit', 'has_reference_audit', 'has_code_of_conduct',
            'has_clearance', 'has_receipt', 'has_resignation',
            'has_verbal_warning_doc', 'has_written_warning_doc', 'has_termination_warning_doc',
            'has_termination_doc', 'has_blacklist_doc',
        ] as $field) {
            $data[$field] = filter_var($row[$field] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        // FK lookups by ID, fallback to name
        $center = null;
        if (!empty($row['center_id'])) {
            $center = Center::find($row['center_id']);
        }
        if (!$center && !empty($row['center_name'])) {
            $center = Center::where('name', $row['center_name'])->first();
        }
        $data['center_id'] = $center?->id;

        $dept = null;
        if (!empty($row['department_id'])) {
            $dept = Department::find($row['department_id']);
        }
        if (!$dept && !empty($row['department_name_ar'])) {
            $dept = Department::where('name_ar', $row['department_name_ar'])->first();
        }
        $data['department_id'] = $dept?->id;

        $project = null;
        if (!empty($row['project_id'])) {
            $project = Project::find($row['project_id']);
        }
        if (!$project && !empty($row['project_name'])) {
            $project = Project::where('name', $row['project_name'])->first();
        }
        $data['project_id'] = $project?->id;

        // User by ID or email
        if (!empty($row['user_id'])) {
            $data['user_id'] = $row['user_id'];
        } elseif (!empty($row['email'])) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => ($row['first_name_ar'] ?? '') . ' ' . ($row['last_name_ar'] ?? ''),
                    'password' => bcrypt('password123'),
                    'type' => 'employee',
                ]
            );
            $data['user_id'] = $user->id;
        }

        return $data;
    }
}
