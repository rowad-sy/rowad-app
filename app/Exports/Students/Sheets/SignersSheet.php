<?php

namespace App\Exports\Students\Sheets;

use App\Exports\FullExport\Sheets\BaseSheetExport;

class SignersSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'signers';
    }

    public function headings(): array
    {
        return [
            'signer_code', 'name_ar', 'role',
        ];
    }

    public function map($row): array
    {
        return [
            $row->importCode(),
            $row->name_ar,
            $row->role,
        ];
    }
}
