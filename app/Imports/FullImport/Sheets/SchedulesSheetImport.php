<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\WorkSchedule;
use Illuminate\Support\Collection;

class SchedulesSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'work_schedules';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            WorkSchedule::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'day_of_week' => $row['day_of_week'] ?? null,
                ],
                [
                    'start_time' => $row['start_time'] ?? null,
                    'end_time' => $row['end_time'] ?? null,
                    'is_day_off' => filter_var($row['is_day_off'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }
    }
}
