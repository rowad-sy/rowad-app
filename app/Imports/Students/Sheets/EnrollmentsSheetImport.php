<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class EnrollmentsSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected StudentFullImport $parent) {}

    public function title(): string
    {
        return 'enrollments';
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

            $course = null;
            if (!empty($row['course_name_ar'])) {
                $course = Course::where('name_ar', $row['course_name_ar'])->first();
            }

            $period = null;
            if (!empty($row['period_name_ar'])) {
                $period = Period::where('name_ar', $row['period_name_ar'])->first();
            }

            // Skip if enrollment already exists for this student + course + period
            $exists = StudentEnrollment::where('student_id', $this->parent->studentMap[$studentCode])
                ->where('course_id', $course?->id)
                ->where('period_id', $period?->id)
                ->exists();

            if (!$exists) {
                StudentEnrollment::create([
                    'student_id' => $this->parent->studentMap[$studentCode],
                    'course_id' => $course?->id,
                    'period_id' => $period?->id,
                    'enrollment_date' => $row['enrollment_date'] ?? null,
                    'status' => $row['status'] ?? 'enrolled',
                    'grade' => $row['grade'] ?? null,
                    'is_certificate_eligible' => filter_var($row['is_certificate_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }
}
