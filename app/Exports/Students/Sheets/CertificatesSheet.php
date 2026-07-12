<?php

namespace App\Exports\Students\Sheets;

use App\Exports\FullExport\Sheets\BaseSheetExport;

class CertificatesSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'certificates';
    }

    public function headings(): array
    {
        return [
            'student_code', 'certificate_number', 'design_name',
            'issue_date', 'is_verified',
        ];
    }

    public function map($row): array
    {
        $student = $row->student;

        return [
            $student?->student_code,
            $row->certificate_number,
            $row->design?->name,
            $row->issue_date?->format('Y-m-d'),
            $row->is_verified ? 'نعم' : 'لا',
        ];
    }
}
