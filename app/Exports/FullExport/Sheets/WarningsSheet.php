<?php

namespace App\Exports\FullExport\Sheets;

class WarningsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'warnings';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'date', 'reason', 'level', 'is_folded', 'fold_reason', 'folded_at',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->date?->format('Y-m-d'),
            $row->reason, $row->level, $row->is_folded,
            $row->fold_reason, $row->folded_at?->format('Y-m-d H:i:s'),
        ];
    }
}
