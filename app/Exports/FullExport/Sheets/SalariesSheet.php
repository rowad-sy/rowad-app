<?php

namespace App\Exports\FullExport\Sheets;

class SalariesSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'salaries';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'currency', 'salary_unit',
            'base_salary', 'study_allowance', 'marriage_allowance', 'experience_allowance',
            'transport_allowance', 'food_allowance', 'housing_allowance', 'mobile_allowance',
            'risk_allowance', 'overtime_rate', 'deduction', 'total_salary',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->currency, $row->salary_unit,
            $row->base_salary, $row->study_allowance, $row->marriage_allowance, $row->experience_allowance,
            $row->transport_allowance, $row->food_allowance, $row->housing_allowance, $row->mobile_allowance,
            $row->risk_allowance, $row->overtime_rate, $row->deduction, $row->total_salary,
        ];
    }
}
