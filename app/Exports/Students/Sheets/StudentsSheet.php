<?php

namespace App\Exports\Students\Sheets;

use App\Exports\FullExport\Sheets\BaseSheetExport;

class StudentsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'students';
    }

    public function headings(): array
    {
        return [
            'student_code', 'identity_type', 'identity_number',
            'first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en',
            'father_name', 'mother_name', 'gender', 'birth_date', 'birth_place',
            'nationality', 'phone', 'email', 'address',
            'center_name', 'project_names', 'status', 'enrollment_date', 'notes', 'created_at',
        ];
    }

    public function map($row): array
    {
        return [
            $row->student_code,
            $row->identity_type,
            $row->identity_number,
            $row->first_name_ar,
            $row->last_name_ar,
            $row->first_name_en,
            $row->last_name_en,
            $row->father_name,
            $row->mother_name,
            $row->gender,
            $row->birth_date?->format('Y-m-d'),
            $row->birth_place,
            $row->nationality,
            $row->phone,
            $row->email,
            $row->address,
            $row->center?->name,
            $row->projects->pluck('name')->implode(', '),
            $row->status,
            $row->enrollment_date?->format('Y-m-d'),
            $row->notes,
            $row->created_at?->format('Y-m-d'),
        ];
    }
}
