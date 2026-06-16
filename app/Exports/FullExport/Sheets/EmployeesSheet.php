<?php

namespace App\Exports\FullExport\Sheets;

class EmployeesSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'employees';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en',
            'father_name_ar', 'father_name_en', 'mother_name_ar', 'mother_name_en',
            'status', 'id_number', 'gender', 'nationality', 'birth_date', 'birth_place',
            'marital_status', 'children_count',
            'center_id', 'center_name',
            'department_id', 'department_name_ar',
            'project_id', 'project_name',
            'user_id', 'email',
            'has_photo', 'has_cv', 'has_id_copy', 'has_qualification', 'has_experience_certs',
            'has_offer_letter', 'has_contract_doc', 'has_employee_data', 'has_job_description',
            'has_signature_movements', 'has_security_audit', 'has_reference_audit', 'has_code_of_conduct',
            'has_clearance', 'has_receipt', 'has_resignation',
            'has_verbal_warning_doc', 'has_written_warning_doc', 'has_termination_warning_doc',
            'has_termination_doc', 'has_blacklist_doc',
            'notes', 'created_at',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->first_name_ar, $row->last_name_ar, $row->first_name_en, $row->last_name_en,
            $row->father_name_ar, $row->father_name_en, $row->mother_name_ar, $row->mother_name_en,
            $row->status, $row->id_number, $row->gender, $row->nationality,
            $row->birth_date?->format('Y-m-d'), $row->birth_place,
            $row->marital_status, $row->children_count,
            $row->center_id, $row->center?->name,
            $row->department_id, $row->department?->name_ar,
            $row->project_id, $row->project?->name,
            $row->user_id, $row->user?->email,
            $row->has_photo, $row->has_cv, $row->has_id_copy, $row->has_qualification, $row->has_experience_certs,
            $row->has_offer_letter, $row->has_contract_doc, $row->has_employee_data, $row->has_job_description,
            $row->has_signature_movements, $row->has_security_audit, $row->has_reference_audit, $row->has_code_of_conduct,
            $row->has_clearance, $row->has_receipt, $row->has_resignation,
            $row->has_verbal_warning_doc, $row->has_written_warning_doc, $row->has_termination_warning_doc,
            $row->has_termination_doc, $row->has_blacklist_doc,
            $row->notes, $row->created_at?->format('Y-m-d'),
        ];
    }
}
