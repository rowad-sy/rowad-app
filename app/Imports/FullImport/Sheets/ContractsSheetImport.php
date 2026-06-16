<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\Contract;
use App\Models\Admin\Hr\JobPosition;
use Illuminate\Support\Collection;

class ContractsSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'contracts';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            $jobPositionId = null;
            if (!empty($row['job_position_id'])) {
                $jobPositionId = $row['job_position_id'];
            } elseif (!empty($row['job_position_name'])) {
                $jp = JobPosition::where('name_ar', $row['job_position_name'])->first();
                $jobPositionId = $jp?->id;
            }

            Contract::create([
                'employee_id' => $employeeId,
                'contract_type' => $row['contract_type'] ?? null,
                'job_position_id' => $jobPositionId,
                'start_date' => $row['start_date'] ?? null,
                'contract_start' => $row['contract_start'] ?? null,
                'contract_end' => $row['contract_end'] ?? null,
                'leave_date' => $row['leave_date'] ?? null,
            ]);
        }
    }
}
