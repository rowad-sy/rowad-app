<?php

namespace App\Exports\FullExport\Sheets;

class ContractsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'contracts';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'contract_type', 'job_position_id', 'job_position_name',
            'start_date', 'contract_start', 'contract_end', 'leave_date',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->contract_type,
            $row->job_position_id,
            $row->jobPosition?->name_ar,
            $row->start_date?->format('Y-m-d'),
            $row->contract_start?->format('Y-m-d'),
            $row->contract_end?->format('Y-m-d'),
            $row->leave_date?->format('Y-m-d'),
        ];
    }
}
