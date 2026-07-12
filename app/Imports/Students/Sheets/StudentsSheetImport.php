<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentsSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected StudentFullImport $parent) {}

    public function title(): string
    {
        return 'students';
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = $row['student_code'] ?? null;
            if (!$code) continue;

            $data = $this->buildData($row);
            $projectNames = $this->parseProjectNames($row);

            $student = Student::updateOrCreate(['student_code' => $code], $data);

            if (!empty($projectNames)) {
                $projectIds = Project::whereIn('name', $projectNames)->pluck('id')->toArray();
                if (!empty($projectIds)) {
                    $student->projects()->syncWithoutDetaching($projectIds);
                }
            }

            $this->parent->studentMap[$code] = $student->id;
        }
    }

    private function parseProjectNames(Collection $row): array
    {
        $projectsRaw = $row['project_names'] ?? $row['project_name'] ?? '';
        if (empty($projectsRaw)) return [];

        return array_map('trim', explode(',', $projectsRaw));
    }

    private function buildData(Collection $row): array
    {
        $data = [
            'identity_type' => $row['identity_type'] ?? null,
            'identity_number' => $row['identity_number'] ?? null,
            'first_name_ar' => $row['first_name_ar'] ?? null,
            'last_name_ar' => $row['last_name_ar'] ?? null,
            'first_name_en' => $row['first_name_en'] ?? null,
            'last_name_en' => $row['last_name_en'] ?? null,
            'father_name' => $row['father_name'] ?? null,
            'mother_name' => $row['mother_name'] ?? null,
            'gender' => $row['gender'] ?? 'male',
            'birth_date' => $row['birth_date'] ?? null,
            'birth_place' => $row['birth_place'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'status' => $row['status'] ?? 'active',
            'enrollment_date' => $row['enrollment_date'] ?? null,
            'notes' => $row['notes'] ?? null,
        ];

        $center = null;
        if (!empty($row['center_name'])) {
            $center = Center::where('name', $row['center_name'])->first();
        }
        $data['center_id'] = $center?->id;
        $data['project_id'] = null;

        return $data;
    }
}
