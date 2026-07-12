<?php

namespace App\Imports\Students;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = trim($row['كود الطالب'] ?? '');
            if (!$code) continue;

            $data = $this->buildData($row);
            $projectNames = $this->parseProjectNames($row);

            // Find existing by student_code, otherwise create
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

    private function parseProjectNames(Collection $row): array
    {
        $projectsRaw = trim($row['المشاريع'] ?? $row['المشروع'] ?? '');
        if (!$projectsRaw) return [];

        return array_map('trim', explode(',', str_replace(['،', ',,'], ',', $projectsRaw)));
    }

    private function buildData(Collection $row): array
    {
        $gender = trim($row['الجنس'] ?? '');
        $status = trim($row['الحالة'] ?? '');
        $centerName = trim($row['المركز'] ?? '');
        $identityType = trim($row['نوع الهوية'] ?? '');

        $center = $centerName ? Center::where('name', $centerName)->first() : null;

        return [
            'student_code' => $code ?? trim($row['كود الطالب'] ?? ''),
            'identity_type' => match (true) {
                str_contains($identityType, 'بطاقة') => 'national_id',
                str_contains($identityType, 'جواز') => 'passport',
                str_contains($identityType, 'إقامة'), str_contains($identityType, 'اقامة') => 'resident_id',
                default => $identityType ?: null,
            },
            'identity_number' => trim($row['رقم الهوية'] ?? '') ?: null,
            'first_name_ar' => trim($row['الاسم الأول AR'] ?? '') ?: null,
            'last_name_ar' => trim($row['الاسم الأخير AR'] ?? '') ?: null,
            'first_name_en' => trim($row['الاسم الأول EN'] ?? '') ?: null,
            'last_name_en' => trim($row['الاسم الأخير EN'] ?? '') ?: null,
            'gender' => match (true) {
                str_contains($gender, 'أنثى'), str_contains($gender, 'انثى') => 'female',
                default => 'male',
            },
            'nationality' => trim($row['الجنسية'] ?? '') ?: null,
            'birth_date' => trim($row['تاريخ الميلاد'] ?? '') ?: null,
            'phone' => trim($row['الهاتف'] ?? '') ?: null,
            'email' => trim($row['البريد الإلكتروني'] ?? '') ?: null,
            'center_id' => $center?->id,
            'project_id' => null,
            'status' => match (true) {
                str_contains($status, 'نشط') => 'active',
                str_contains($status, 'غير نشط'), str_contains($status, 'غير') => 'inactive',
                str_contains($status, 'متخرج') => 'graduated',
                str_contains($status, 'موقوف') => 'suspended',
                default => 'active',
            },
            'enrollment_date' => trim($row['تاريخ التسجيل'] ?? '') ?: null,
        ];
    }
}
