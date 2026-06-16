<?php

namespace App\Exports\FullExport\Sheets;

class EducationsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'employee_educations';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'qualification', 'specialization', 'university', 'grade', 'graduation_year',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->qualification, $row->specialization, $row->university,
            $row->grade, $row->graduation_year,
        ];
    }
}
