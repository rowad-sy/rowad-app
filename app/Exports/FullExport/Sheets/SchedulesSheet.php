<?php

namespace App\Exports\FullExport\Sheets;

class SchedulesSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'work_schedules';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'day_of_week', 'start_time', 'end_time', 'is_day_off',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->day_of_week, $row->start_time, $row->end_time, $row->is_day_off,
        ];
    }
}
