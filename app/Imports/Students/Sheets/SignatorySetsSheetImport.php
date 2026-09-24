<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Center;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class SignatorySetsSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected StudentFullImport $parent) {}

    public function title(): string
    {
        return 'signatory_sets';
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $data = [
                'course_id' => $this->courseId($row['course_name_ar'] ?? null),
                'period_id' => $this->periodId($row['period_name_ar'] ?? null),
                'center_id' => $this->centerId($row['center_name'] ?? null),
                'instructor_signer_id' => $this->parent->resolveSignerId(
                    $row['instructor_code'] ?? null, $row['instructor_name'] ?? null, 'instructor'
                ),
                'center_manager_signer_id' => $this->parent->resolveSignerId(
                    $row['center_manager_code'] ?? null, $row['center_manager_name'] ?? null, 'center_manager'
                ),
                'project_manager_signer_id' => $this->parent->resolveSignerId(
                    $row['project_manager_code'] ?? null, $row['project_manager_name'] ?? null, 'project_manager'
                ),
            ];

            $set = CertificateSignatorySet::updateOrCreate(['name' => $name], $data);
            $this->parent->registerSet($name, $set->id);
            $this->parent->setsImported++;
        }
    }

    private function courseId(mixed $value): ?int
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        return Course::where('name_ar', $value)->value('id');
    }

    private function periodId(mixed $value): ?int
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        return Period::where('name_ar', $value)->value('id');
    }

    private function centerId(mixed $value): ?int
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        return Center::where('name', $value)->value('id');
    }
}
