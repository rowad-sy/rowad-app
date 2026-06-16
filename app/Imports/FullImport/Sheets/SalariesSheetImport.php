<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\Salary;
use Illuminate\Support\Collection;

class SalariesSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'salaries';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            Salary::create([
                'employee_id' => $employeeId,
                'currency' => $row['currency'] ?? 'SYP',
                'salary_unit' => $row['salary_unit'] ?? null,
                'base_salary' => $row['base_salary'] ?? 0,
                'study_allowance' => $row['study_allowance'] ?? 0,
                'marriage_allowance' => $row['marriage_allowance'] ?? 0,
                'experience_allowance' => $row['experience_allowance'] ?? 0,
                'transport_allowance' => $row['transport_allowance'] ?? 0,
                'food_allowance' => $row['food_allowance'] ?? 0,
                'housing_allowance' => $row['housing_allowance'] ?? 0,
                'mobile_allowance' => $row['mobile_allowance'] ?? 0,
                'risk_allowance' => $row['risk_allowance'] ?? 0,
                'overtime_rate' => $row['overtime_rate'] ?? 0,
                'deduction' => $row['deduction'] ?? 0,
                'total_salary' => $row['total_salary'] ?? 0,
            ]);
        }
    }
}
