<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Student\Certificate;
use App\Models\Admin\Student\CertificateDesign;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class CertificatesSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected StudentFullImport $parent) {}

    public function title(): string
    {
        return 'certificates';
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $studentCode = $row['student_code'] ?? null;
            if (!$studentCode || !isset($this->parent->studentMap[$studentCode])) continue;

            $design = null;
            if (!empty($row['design_name'])) {
                $design = CertificateDesign::where('name', $row['design_name'])->first();
            }

            Certificate::create([
                'student_id' => $this->parent->studentMap[$studentCode],
                'certificate_number' => $row['certificate_number'] ?? null,
                'design_id' => $design?->id,
                'issue_date' => $row['issue_date'] ?? null,
                'is_verified' => filter_var($row['is_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
