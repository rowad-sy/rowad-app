<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\Warning;
use Illuminate\Support\Collection;

class WarningsSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'warnings';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            Warning::create([
                'employee_id' => $employeeId,
                'date' => $row['date'] ?? null,
                'reason' => $row['reason'] ?? null,
                'level' => $row['level'] ?? 'verbal',
                'is_folded' => filter_var($row['is_folded'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'fold_reason' => $row['fold_reason'] ?? null,
                'folded_at' => $row['folded_at'] ?? null,
            ]);
        }
    }
}
