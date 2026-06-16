<?php

namespace App\Imports\FullImport\Sheets;

use App\Models\Admin\Hr\EmployeeContact;
use Illuminate\Support\Collection;

class ContactsSheetImport extends BaseSheetImport
{
    public function title(): string
    {
        return 'employee_contacts';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $employeeId = $this->parent->employeeMap[$row['employee_code'] ?? null] ?? null;
            if (!$employeeId) continue;

            EmployeeContact::create([
                'employee_id' => $employeeId,
                'type' => $row['type'] ?? null,
                'value' => $row['value'] ?? null,
                'is_primary' => filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
