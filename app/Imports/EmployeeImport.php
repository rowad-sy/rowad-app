<?php

namespace App\Imports;

use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeeImport extends BaseImport
{
    public function __construct()
    {
        parent::__construct(Employee::class, [
            'كود الموظف' => 'employee_code',
            'رقم الهوية' => 'id_number',
            'الجنسية' => 'nationality',
            'تاريخ الميلاد' => 'birth_date',
            'مكان الميلاد' => 'birth_place',
            'عدد الأولاد' => 'children_count',
        ], function (Employee $record, Collection $row) {
            $this->applyNames($record, $row);
            $this->applyStatus($record, $row);
            $this->applyGender($record, $row);
            $this->applyMaritalStatus($record, $row);
            $this->applyRelations($record, $row);
            $this->applyUser($record, $row);
            $record->save();
        });
    }

    private function applyNames(Employee $record, Collection $row): void
    {
        $nameAr = $row['الاسم AR'] ?? '';
        $nameEn = $row['الاسم EN'] ?? '';

        $partsAr = explode(' ', trim($nameAr), 2);
        $record->first_name_ar = $partsAr[0] ?? $nameAr;
        $record->last_name_ar = $partsAr[1] ?? '';

        $partsEn = explode(' ', trim($nameEn), 2);
        $record->first_name_en = $partsEn[0] ?? $nameEn;
        $record->last_name_en = $partsEn[1] ?? '';
    }

    private function applyStatus(Employee $record, Collection $row): void
    {
        $status = trim($row['الحالة'] ?? '');
        $record->status = match (true) {
            str_contains($status, 'فعال') => 'active',
            default => 'inactive',
        };
    }

    private function applyGender(Employee $record, Collection $row): void
    {
        $gender = trim($row['الجنس'] ?? '');
        $record->gender = match (true) {
            str_contains($gender, 'ذكر') => 'male',
            str_contains($gender, 'أنثى'), str_contains($gender, 'انثى') => 'female',
            default => 'male',
        };
    }

    private function applyMaritalStatus(Employee $record, Collection $row): void
    {
        $status = trim($row['الحالة الاجتماعية'] ?? '');
        $record->marital_status = match (true) {
            str_contains($status, 'أعزب'), str_contains($status, 'اعزب') => 'single',
            str_contains($status, 'متزوج') => 'married',
            str_contains($status, 'مطلق') => 'divorced',
            str_contains($status, 'أرمل'), str_contains($status, 'ارمل') => 'widowed',
            default => null,
        };
    }

    private function applyRelations(Employee $record, Collection $row): void
    {
        $centerName = trim($row['المركز'] ?? '');
        if ($centerName) {
            $center = Center::where('name', $centerName)->first();
            $record->center_id = $center?->id;
        }

        $deptName = trim($row['الإدارة'] ?? '');
        if ($deptName) {
            $dept = Department::where('name_ar', $deptName)->first();
            $record->department_id = $dept?->id;
        }

        $projectName = trim($row['المشروع'] ?? '');
        if ($projectName) {
            $project = Project::where('name', $projectName)->first();
            $record->project_id = $project?->id;
        }
    }

    private function applyUser(Employee $record, Collection $row): void
    {
        $email = trim($row['البريد الإلكتروني'] ?? '');
        if ($email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $record->first_name_ar . ' ' . $record->last_name_ar,
                    'password' => bcrypt('password123'),
                    'type' => 'employee',
                ]
            );
            $record->user_id = $user->id;
        }
    }
}
