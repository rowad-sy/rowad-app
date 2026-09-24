<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Student\Certificate;
use App\Models\Admin\Student\CertificateDesign;
use App\Models\Admin\Student\CertificateNumberSequence;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class CertificatesSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    protected array $studentCenterCache = [];

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
            if (!$studentCode || !isset($this->parent->studentMap[$studentCode])) {
                continue;
            }
            $studentId = $this->parent->studentMap[$studentCode];

            $design = null;
            if (!empty($row['design_name'])) {
                $design = CertificateDesign::where('name', trim((string) $row['design_name']))->first();
            }

            $issueDate = $this->normalizeDate($row['issue_date'] ?? null);
            $courseId = !empty($row['course_name_ar']) ? Course::where('name_ar', trim((string) $row['course_name_ar']))->value('id') : null;
            $periodId = !empty($row['period_name_ar']) ? Period::where('name_ar', trim((string) $row['period_name_ar']))->value('id') : null;

            $enrollment = $this->resolveEnrollment($studentId, $courseId, $periodId);
            $set = $this->resolveSet($row['signatory_set_name'] ?? null, $courseId, $periodId, $studentId);

            $number = !empty($row['certificate_number']) ? trim((string) $row['certificate_number']) : null;

            // للشهادات الموجودة: لا نحدّث إلا ما ورد في الملف فعلياً (حتى لا نمسح قيماً صحيحة)
            $updates = array_filter([
                'design_id' => $design?->id,
                'enrollment_id' => $enrollment?->id,
                'signatory_set_id' => $set?->id,
                'issue_date' => $issueDate,
            ], fn($v) => $v !== null);
            if (isset($row['is_verified']) && $row['is_verified'] !== '') {
                $updates['is_verified'] = $this->parseBool($row['is_verified']);
            }

            $attributes = [
                'student_id' => $studentId,
                'design_id' => $design?->id,
                'enrollment_id' => $enrollment?->id,
                'signatory_set_id' => $set?->id,
                'issue_date' => $issueDate ?? now()->toDateString(),
                'is_verified' => $this->parseBool($row['is_verified'] ?? false),
            ];

            if ($number !== null && $number !== '') {
                $existing = Certificate::where('certificate_number', $number)->first();
                if ($existing) {
                    if ($updates) {
                        $existing->update($updates);
                        $this->parent->certificatesUpdated++;
                    }
                    continue;
                }

                Certificate::create(array_merge($attributes, [
                    'certificate_number' => $number,
                    'barcode_hash' => hash('sha256', $studentId . $number . ($enrollment?->id ?? '') . config('app.key')),
                ]));
                $this->parent->certificatesCreated++;
                continue;
            }

            // لا يوجد رقم في الملف — نولّد رقماً جديداً في السلسلة السنوية
            $year = $issueDate ? (int) substr($issueDate, 0, 4) : (int) date('Y');
            $certNumber = CertificateNumberSequence::nextNumber($year);
            Certificate::create(array_merge($attributes, [
                'certificate_number' => $certNumber,
                'barcode_hash' => hash('sha256', $studentId . $certNumber . ($enrollment?->id ?? '') . config('app.key')),
            ]));
            $this->parent->certificatesCreated++;
        }
    }

    private function resolveEnrollment(int $studentId, ?int $courseId, ?int $periodId): ?StudentEnrollment
    {
        if (!$courseId) {
            return null;
        }

        $query = StudentEnrollment::where('student_id', $studentId)->where('course_id', $courseId);

        return $periodId ? $query->where('period_id', $periodId)->first() : $query->first();
    }

    private function resolveSet(mixed $name, ?int $courseId, ?int $periodId, int $studentId): ?CertificateSignatorySet
    {
        $byName = $this->parent->resolveSetId(is_string($name) ? $name : null);
        if ($byName) {
            return CertificateSignatorySet::find($byName);
        }

        if (!$courseId) {
            return null;
        }

        if (!array_key_exists($studentId, $this->studentCenterCache)) {
            $this->studentCenterCache[$studentId] = Student::where('id', $studentId)->value('center_id');
        }

        return CertificateSignatorySet::resolve($courseId, $periodId, $this->studentCenterCache[$studentId]);
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $str = trim(mb_strtolower((string) $value));

        return in_array($str, ['1', 'true', 'yes', 'on', 'نعم', 'صح', 'تم التحقق'], true);
    }

    private function normalizeDate(mixed $value): ?string
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
