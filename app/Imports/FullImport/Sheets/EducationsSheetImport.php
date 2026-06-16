<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\EmployeeEducation;
use Illuminate\Support\Collection;

class EducationsSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'employee_educations';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            EmployeeEducation::create([
                'employee_id' => $employeeId,
                'qualification' => $row['qualification'] ?? null,
                'specialization' => $row['specialization'] ?? null,
                'university' => $row['university'] ?? null,
                'grade' => $row['grade'] ?? null,
                'graduation_year' => $row['graduation_year'] ?? null,
            ]);
        }
    }
}
