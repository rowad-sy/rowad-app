<?php

namespace App\Imports\Students;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    private int $nextCode = 0;

    public function collection(Collection $rows): void
    {
        $maxCode = Student::max('student_code');
        if ($maxCode && preg_match('/(\d+)$/', $maxCode, $m)) {
            $this->nextCode = (int) $m[1];
        }

        foreach ($rows as $row) {
            $code = $this->get($row, 'student_code', 'كود الطالب');
            if (!$code) {
                $hasName = $this->get($row, 'first_name_ar', 'الاسم الأول AR')
                    || $this->get($row, 'last_name_ar', 'الاسم الأخير AR')
                    || $this->get($row, 'identity_number', 'رقم الهوية');
                if (!$hasName) {
                    continue;
                }
                $this->nextCode++;
                $code = 'STU-' . str_pad($this->nextCode, 5, '0', STR_PAD_LEFT);
            }

            $data = $this->buildData($row, $code);
            $projectNames = $this->parseProjectNames($row);

            $student = Student::where('student_code', $code)->first();

            if ($student) {
                foreach ($data as $key => $value) {
                    if (!is_null($value) && $value !== '') {
                        $student->$key = $value;
                    }
                }
                $student->save();
            } else {
                $student = Student::create($data);
            }

            if (!empty($projectNames)) {
                $projectIds = Project::whereIn('name', $projectNames)->pluck('id')->toArray();
                if (!empty($projectIds)) {
                    $student->projects()->syncWithoutDetaching($projectIds);
                }
            }
        }
    }

    private function get(Collection $row, string $en, string $ar): ?string
    {
        $slug = Str::slug($ar, '_');
        $val = $row[$en] ?? $row[$ar] ?? $row[$slug] ?? null;
        if (is_null($val)) return null;
        $str = trim((string) $val);
        return $str !== '' && $str !== '0' ? $str : null;
    }

    private function raw(Collection $row, string $en, string $ar): mixed
    {
        $slug = Str::slug($ar, '_');
        return $row[$en] ?? $row[$ar] ?? $row[$slug] ?? null;
    }

    private function parseProjectNames(Collection $row): array
    {
        $projectsRaw = trim($this->get($row, 'project_names', 'المشاريع') ?? $this->get($row, 'project_name', 'المشروع') ?? '');
        if (!$projectsRaw) return [];

        return array_map('trim', explode(',', str_replace(['،', ',,'], ',', $projectsRaw)));
    }

    private function buildData(Collection $row, string $code): array
    {
        $gender = trim($this->get($row, 'gender', 'الجنس') ?? '');
        $status = trim($this->get($row, 'status', 'الحالة') ?? '');
        $centerName = $this->get($row, 'center_name', 'المركز');
        $identityType = $this->get($row, 'identity_type', 'نوع الهوية');

        $center = $centerName ? Center::where('name', $centerName)->first() : null;

        $genderValue = match (true) {
            $gender && (str_contains($gender, 'أنثى') || str_contains($gender, 'انثى')) => 'female',
            $gender && str_contains($gender, 'male') => 'male',
            $gender && str_contains($gender, 'female') => 'female',
            default => $gender ?: 'male',
        };

        return [
            'student_code' => $code,
            'identity_type' => match (true) {
                $identityType && (str_contains($identityType, 'بطاقة') || str_contains($identityType, 'national')) => 'national_id',
                $identityType && (str_contains($identityType, 'جواز') || str_contains($identityType, 'passport')) => 'passport',
                $identityType && (str_contains($identityType, 'إقامة') || str_contains($identityType, 'اقامة') || str_contains($identityType, 'resident')) => 'resident_id',
                $identityType && str_contains($identityType, 'أخرى') => 'other',
                default => $identityType,
            },
            'identity_number' => $this->get($row, 'identity_number', 'رقم الهوية'),
            'first_name_ar' => $this->get($row, 'first_name_ar', 'الاسم الأول AR'),
            'last_name_ar' => $this->get($row, 'last_name_ar', 'الاسم الأخير AR'),
            'first_name_en' => $this->get($row, 'first_name_en', 'الاسم الأول EN'),
            'last_name_en' => $this->get($row, 'last_name_en', 'الاسم الأخير EN'),
            'father_name' => $this->get($row, 'father_name', 'اسم الأب'),
            'mother_name' => $this->get($row, 'mother_name', 'اسم الأم'),
            'gender' => $genderValue,
            'nationality' => $this->get($row, 'nationality', 'الجنسية'),
            'birth_date' => $this->parseDate($this->raw($row, 'birth_date', 'تاريخ الميلاد')),
            'birth_place' => $this->get($row, 'birth_place', 'مكان الميلاد'),
            'phone' => $this->get($row, 'phone', 'الهاتف'),
            'email' => $this->get($row, 'email', 'البريد الإلكتروني'),
            'address' => $this->get($row, 'address', 'العنوان'),
            'center_id' => $center?->id,
            'project_id' => null,
            'status' => match (true) {
                $status && str_contains($status, 'نشط') && !str_contains($status, 'غير') => 'active',
                $status && str_contains($status, 'غير نشط') => 'inactive',
                $status && str_contains($status, 'متخرج') => 'graduated',
                $status && str_contains($status, 'موقوف') => 'suspended',
                $status && str_contains($status, 'active') => 'active',
                $status && str_contains($status, 'inactive') => 'inactive',
                $status && str_contains($status, 'graduated') => 'graduated',
                $status && str_contains($status, 'suspended') => 'suspended',
                default => $status ?: 'active',
            },
            'enrollment_date' => $this->parseDate($this->raw($row, 'enrollment_date', 'تاريخ التسجيل')),
            'notes' => $this->get($row, 'notes', 'ملاحظات'),
        ];
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
                try {
                    return \Carbon\Carbon::create(1899, 12, 30)->addDays(floor($num))->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
            return null;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '0') {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse($str);
            return $parsed->isValid() ? $parsed->format('Y-m-d') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
