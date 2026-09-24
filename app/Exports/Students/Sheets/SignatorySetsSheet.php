<?php

namespace App\Exports\Students\Sheets;

use App\Exports\FullExport\Sheets\BaseSheetExport;

class SignatorySetsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'signatory_sets';
    }

    public function headings(): array
    {
        return [
            'name', 'course_name_ar', 'period_name_ar', 'center_name',
            'instructor_code', 'instructor_name',
            'center_manager_code', 'center_manager_name',
            'project_manager_code', 'project_manager_name',
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->course?->name_ar,
            $row->period?->name_ar,
            $row->center?->name,
            $row->instructorSigner?->importCode(),
            $row->instructorSigner?->name_ar,
            $row->centerManagerSigner?->importCode(),
            $row->centerManagerSigner?->name_ar,
            $row->projectManagerSigner?->importCode(),
            $row->projectManagerSigner?->name_ar,
        ];
    }
}
