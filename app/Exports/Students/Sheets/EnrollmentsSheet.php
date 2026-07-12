<?php

namespace App\Exports\Students\Sheets;

use App\Exports\FullExport\Sheets\BaseSheetExport;

class EnrollmentsSheet extends BaseSheetExport
{
    public function title(): string
    {
        return 'enrollments';
    }

    public function headings(): array
    {
        return [
            'student_code', 'course_name_ar', 'period_name_ar',
            'enrollment_date', 'status', 'grade', 'is_certificate_eligible',
        ];
    }

    public function map($row): array
    {
        $student = $row->student;

        return [
            $student?->student_code,
            $row->course?->name_ar,
            $row->period?->name_ar,
            $row->enrollment_date?->format('Y-m-d'),
            $row->status,
            $row->grade,
            $row->is_certificate_eligible ? 'نعم' : 'لا',
        ];
    }
}
