<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $center = Center::first();
        $dept = Department::first();
        $project = Project::first();

        $employees = [
            [
                'employee_code' => 'OND001',
                'first_name_ar' => 'محمد',
                'last_name_ar' => 'أحمد',
                'first_name_en' => 'Mohammed',
                'last_name_en' => 'Ahmed',
                'gender' => 'male',
                'marital_status' => 'married',
                'birth_date' => '1990-05-15',
                'birth_place' => 'دمشق',
                'nationality' => 'سوري',
                'center_id' => $center?->id,
                'department_id' => $dept?->id,
                'project_id' => $project?->id,
            ],
            [
                'employee_code' => 'OND002',
                'first_name_ar' => 'سارة',
                'last_name_ar' => 'خالد',
                'first_name_en' => 'Sara',
                'last_name_en' => 'Khaled',
                'gender' => 'female',
                'marital_status' => 'single',
                'birth_date' => '1995-08-22',
                'birth_place' => 'حلب',
                'nationality' => 'سوري',
                'center_id' => $center?->id,
                'department_id' => $dept?->id,
                'project_id' => $project?->id,
            ],
            [
                'employee_code' => 'OND003',
                'first_name_ar' => 'أحمد',
                'last_name_ar' => 'نور',
                'first_name_en' => 'Ahmad',
                'last_name_en' => 'Nour',
                'gender' => 'male',
                'marital_status' => 'married',
                'children_count' => 3,
                'birth_date' => '1985-11-03',
                'birth_place' => 'حمص',
                'nationality' => 'سوري',
            ],
        ];

        foreach ($employees as $data) {
            Employee::create($data);
        }
    }
}
