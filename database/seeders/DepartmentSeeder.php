<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name_ar' => 'الإدارة العامة', 'name_en' => 'General Administration'],
            ['name_ar' => 'إدارة العمليات', 'name_en' => 'Operations Department'],
            ['name_ar' => 'إدارة المشاريع', 'name_en' => 'Project Management'],
            ['name_ar' => 'الموارد البشرية', 'name_en' => 'Human Resources'],
            ['name_ar' => 'الشؤون المالية', 'name_en' => 'Financial Affairs'],
            ['name_ar' => 'تقنية المعلومات', 'name_en' => 'Information Technology'],
            ['name_ar' => 'العلاقات العامة', 'name_en' => 'Public Relations'],
            ['name_ar' => 'الشؤون القانونية', 'name_en' => 'Legal Affairs'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
