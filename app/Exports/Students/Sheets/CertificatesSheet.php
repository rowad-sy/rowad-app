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
            'course_name_ar', 'period_name_ar', 'signatory_set_name',
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
            $row->enrollment?->course?->name_ar ?? $row->design?->course?->name_ar,
            $row->enrollment?->period?->name_ar,
            $row->signatorySet?->name,
            $row->issue_date?->format('Y-m-d'),
            $row->is_verified ? 'نعم' : 'لا',
        ];
    }
}
