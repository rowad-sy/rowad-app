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

            // course_id هو NOT NULL في الجدول — تخطّي الصف بدل الانكسار إذا كان المقرر غير موجود
            if (!$course) {
                continue;
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
                    'enrollment_date' => $this->resolveDate($row['enrollment_date'] ?? null, $period),
                    'status' => $row['status'] ?? 'enrolled',
                    'grade' => $row['grade'] ?? null,
                    'is_certificate_eligible' => filter_var($row['is_certificate_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }

    /*
     * enrollment_date عمود NOT NULL — نفضّل قيمة الملف، ثم تاريخ بداية الفترة، ثم اليوم الحالي.
     */
    private function resolveDate(mixed $value, ?Period $period): string
    {
        $parsed = $this->parseDate($value);
        if ($parsed) {
            return $parsed;
        }

        if ($period?->start_date) {
            return $period->start_date->format('Y-m-d');
        }

        return now()->toDateString();
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num > 30000 && $num < 60000) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays(floor($num))->format('Y-m-d');
            }
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse(trim((string) $value));
            return $parsed->isValid() ? $parsed->format('Y-m-d') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
