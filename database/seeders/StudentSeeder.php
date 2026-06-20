<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $centers = Center::pluck('id')->toArray();
        $projects = Project::pluck('id')->toArray();

        $students = [
            ['STU001', 'أحمد', 'الخالد', 'male'],
            ['STU002', 'سارة', 'الأسعد', 'female'],
            ['STU003', 'محمود', 'درويش', 'male'],
            ['STU004', 'ليلى', 'مراد', 'female'],
            ['STU005', 'عمر', 'الشريف', 'male'],
            ['STU006', 'نور', 'حسن', 'female'],
            ['STU007', 'خالد', 'البكري', 'male'],
            ['STU008', 'رنا', 'الأحمد', 'female'],
            ['STU009', 'باسم', 'سليمان', 'male'],
            ['STU010', 'هدى', 'ناصر', 'female'],
            ['STU011', 'سامر', 'الخطيب', 'male'],
            ['STU012', 'مريم', 'عبد الله', 'female'],
            ['STU013', 'زياد', 'حجازي', 'male'],
            ['STU014', 'داليا', 'شاهين', 'female'],
            ['STU015', 'رامي', 'صالح', 'male'],
        ];

        $created = 0;
        foreach ($students as [$code, $first, $last, $gender]) {
            Student::firstOrCreate(['student_code' => $code], [
                'first_name_ar' => $first,
                'last_name_ar' => $last,
                'gender' => $gender,
                'birth_date' => now()->subYears(rand(10, 18))->subDays(rand(0, 365))->format('Y-m-d'),
                'nationality' => 'سوري',
                'phone' => '09' . rand(10000000, 99999999),
                'center_id' => $centers ? $centers[array_rand($centers)] : null,
                'project_id' => $projects ? $projects[array_rand($projects)] : null,
                'status' => 'active',
                'enrollment_date' => now()->subMonths(rand(1, 6))->format('Y-m-d'),
            ]) ? $created++ : null;
        }

        $this->command->info('تم إنشاء ' . $created . ' طالب يدوي');

        // Generate ~500 additional students
        $existingCodes = Student::pluck('student_code')->map(fn($c) => ltrim(substr($c, 3), '0'))->filter(fn($c) => is_numeric($c))->toArray();
        $nextNum = $existingCodes ? max($existingCodes) + 1 : 16;
        $factoryCreated = 0;

        $firstNamesMale = ['أحمد', 'محمود', 'عمر', 'خالد', 'باسم', 'سامر', 'زياد', 'رامي', 'محمد', 'علي', 'حسن', 'حسين', 'عبد الله', 'كريم', 'نادر', 'فادي', 'مازن', 'أيمن', 'بسام', 'تامر', 'جمال', 'ياسر', 'هاني', 'عدنان', 'مروان'];
        $firstNamesFemale = ['سارة', 'نور', 'ليلى', 'رنا', 'هدى', 'مريم', 'داليا', 'فاطمة', 'آمنة', 'خديجة', 'عائشة', 'رؤى', 'سلمى', 'لينا', 'ناديا', 'هيا', 'بتول', 'حنان', 'وردة', 'زهراء', 'ندى', 'رنين', 'سوسن', 'غادة'];
        $lastNames = ['الخالد', 'الأسعد', 'درويش', 'مراد', 'الشريف', 'حسن', 'البكري', 'الأحمد', 'سليمان', 'ناصر', 'الخطيب', 'عبد الله', 'حجازي', 'شاهين', 'صالح', 'الأمين', 'الصالح', 'الرفاعي', 'الحلبي', 'الحريري', 'شعبان', 'عباس', 'مصري', 'جابر', 'علي', 'حسين', 'إبراهيم', 'محمد', 'عثمان', 'نعسان'];

        while ($factoryCreated < 500) {
            $gender = rand(0, 1) ? 'male' : 'female';
            $firstPool = $gender === 'male' ? $firstNamesMale : $firstNamesFemale;
            $code = 'STU' . str_pad($nextNum++, 5, '0', STR_PAD_LEFT);

            if (Student::where('student_code', $code)->exists()) continue;

            Student::create([
                'student_code' => $code,
                'first_name_ar' => $firstPool[array_rand($firstPool)],
                'last_name_ar' => $lastNames[array_rand($lastNames)],
                'gender' => $gender,
                'birth_date' => now()->subYears(rand(10, 18))->subDays(rand(0, 365))->format('Y-m-d'),
                'nationality' => 'سوري',
                'phone' => '09' . rand(10000000, 99999999),
                'center_id' => $centers ? $centers[array_rand($centers)] : null,
                'project_id' => $projects ? $projects[array_rand($projects)] : null,
                'status' => 'active',
                'enrollment_date' => now()->subMonths(rand(1, 6))->format('Y-m-d'),
            ]);

            $factoryCreated++;
        }

        $this->command->info('تم إنشاء ' . $factoryCreated . ' طالب إضافي');
    }
}
