<?php

namespace App\Exports\FullExport\Sheets;

class ContactsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'employee_contacts';
    }

    public function headings(): array
    {
        return [
            'employee_code', 'type', 'value', 'is_primary',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code,
            $row->type, $row->value, $row->is_primary,
        ];
    }
}
