<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class JablousStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $excelPath = base_path('project-files/مجلد جديد/الم_information_المطلوبة_للطلاب_-_جرابلس_-_معهد_الرواد_للتدريب_والتأهيل_المهني_.xlsx');

        // Fallback: find the file by searching
        if (!file_exists($excelPath)) {
            $files = glob(base_path('project-files/*/*/*.xlsx'));
            $files2 = glob(base_path('project-files/*/*.xlsx'));
            $all = array_merge($files, $files2);
            foreach ($all as $f) {
                if (mb_strpos(basename($f), 'المعلومات المطلوبة') !== false && mb_strpos(basename(dirname($f)), 'مجلد') !== false) {
                    $excelPath = $f;
                    break;
                }
            }
        }

        if (!file_exists($excelPath)) {
            $this->command?->error('Excel file not found');
            return;
        }

        $collection = Excel::toCollection([], $excelPath, null, \Maatwebsite\Excel\Excel::XLSX)->first();
        $rows = [];
        foreach ($collection as $row) {
            $rows[] = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
        }

        $header = array_shift($rows);
        $header = array_map(fn($h) => trim((string)($h ?? '')), $header);

        // Find or create center
        $center = Center::where('name', 'like', '%جرابلس%')->first();
        if (!$center) {
            $center = Center::create(['name' => 'جرابلس', 'is_active' => true]);
        }

        // Find or create project
        $project = Project::where('name', 'like', '%الرواد%')->first();
        if (!$project) {
            $project = Project::create([
                'name' => 'معهد الرواد للتدريب والتأهيل المهني',
                'description' => 'معهد الرواد للتدريب والتأهيل المهني - جرابلس',
            ]);
        }

        // Collect unique courses and periods from the data
        $courseNames = [];
        $periodNames = [];
        foreach ($rows as $row) {
            $courseName = trim((string)($row[11] ?? ''));
            $periodName = trim((string)($row[12] ?? ''));
            if ($courseName !== '' && $courseName !== 'null') {
                $courseNames[$courseName] = true;
            }
            if ($periodName !== '' && $periodName !== 'null') {
                $periodNames[$periodName] = true;
            }
        }

        // Create courses
        $courses = [];
        foreach (array_keys($courseNames) as $name) {
            $course = Course::firstOrCreate(
                ['name_ar' => $name],
                [
                    'name_en' => $name,
                    'description' => $name,
                    'project_id' => $project->id,
                ]
            );
            $courses[$name] = $course;
        }

        // Create periods
        $periods = [];
        $year = (int) date('Y');
        foreach (array_keys($periodNames) as $name) {
            $period = Period::firstOrCreate(
                ['name_ar' => $name, 'year' => $year],
                [
                    'project_id' => $project->id,
                    'start_date' => now()->startOfYear(),
                    'end_date' => now()->endOfYear(),
                    'is_active' => true,
                ]
            );
            $periods[$name] = $period;
        }

        $this->command?->info("Found " . count($courses) . " courses, " . count($periods) . " periods");

        // Gender mapping
        $genderMap = [
            'انثى' => 'female',
            'أنثى' => 'female',
            'ذكر' => 'male',
            'Female' => 'female',
            'Male' => 'male',
        ];

        // Enrollment status mapping
        $statusMap = [
            'ناجح' => 'passed',
            'غير ناجح' => 'failed',
            'ناجح ' => 'passed',
            'غير ناجح ' => 'failed',
        ];

        $studentCode = 1;
        $imported = 0;

        foreach ($rows as $row) {
            $fullName = trim((string)($row[0] ?? ''));
            if ($fullName === '' || $fullName === 'null') continue;

            // Split name: first word = first_name_ar, rest = last_name_ar
            $nameParts = preg_split('/\s+/', $fullName, 2);
            $firstNameAr = $nameParts[0] ?? '';
            $lastNameAr = $nameParts[1] ?? '';

            $identityType = trim((string)($row[1] ?? ''));
            $identityNumber = trim((string)($row[2] ?? ''));
            $genderRaw = trim((string)($row[3] ?? ''));
            $gender = $genderMap[$genderRaw] ?? 'female';

            // Birth date — Excel may store as serial number or age
            $birthDateRaw = trim((string)($row[4] ?? ''));
            $birthDate = $this->parseBirthDate($birthDateRaw);

            $birthPlace = trim((string)($row[5] ?? ''));
            $nationality = trim((string)($row[6] ?? ''));
            $email = trim((string)($row[7] ?? ''));
            if ($email === 'لا يوجد' || $email === '' || $email === 'null') {
                $email = null;
            }

            // Enrollment date — Excel serial number
            $enrollmentDateRaw = trim((string)($row[10] ?? ''));
            $enrollmentDate = $this->parseExcelDate($enrollmentDateRaw);

            $courseName = trim((string)($row[11] ?? ''));
            $periodName = trim((string)($row[12] ?? ''));
            $passFail = trim((string)($row[13] ?? ''));
            $grade = trim((string)($row[14] ?? ''));
            $trainingHours = trim((string)($row[15] ?? ''));
            $trainerName = trim((string)($row[16] ?? ''));
            $level = trim((string)($row[19] ?? ''));

            $code = 'STU-' . str_pad($studentCode++, 5, '0', STR_PAD_LEFT);

            $student = Student::create([
                'student_code' => $code,
                'identity_type' => $identityType ?: null,
                'identity_number' => $identityNumber ?: null,
                'first_name_ar' => $firstNameAr,
                'last_name_ar' => $lastNameAr,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'birth_place' => $birthPlace ?: null,
                'nationality' => $nationality ?: null,
                'email' => $email,
                'center_id' => $center->id,
                'project_id' => $project->id,
                'status' => 'active',
                'enrollment_date' => $enrollmentDate,
                'notes' => collect([
                    $trainingHours ? "ساعات التدريب: {$trainingHours}" : null,
                    $trainerName ? "المدرب: {$trainerName}" : null,
                    $level ? "المستوى: {$level}" : null,
                    $grade && $grade !== 'لا يوجد' && $grade !== 'لايوجد' ? "التقدير: {$grade}" : null,
                ])->filter()->implode(' | ') ?: null,
            ]);

            // Create enrollment
            $course = $courses[$courseName] ?? null;
            $period = $periods[$periodName] ?? null;
            $enrollmentStatus = $statusMap[$passFail] ?? 'enrolled';

            if ($course && $period) {
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'period_id' => $period->id,
                    'enrollment_date' => $enrollmentDate ?? now(),
                    'status' => $enrollmentStatus,
                    'is_certificate_eligible' => $enrollmentStatus === 'passed',
                ]);
            }

            $imported++;
        }

        $this->command?->info("Imported {$imported} students");
    }

    protected function parseExcelDate(string $value): ?\Illuminate\Support\Carbon
    {
        $value = trim($value);
        if ($value === '' || $value === 'null') return null;

        // If it's a decimal number (Excel serial date)
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            $serial = (float) $value;
            if ($serial > 40000 && $serial < 50000) {
                // Convert Excel serial to date (Excel epoch: 1900-01-01 = 1)
                try {
                    return \Illuminate\Support\Carbon::create(1899, 12, 30)->addDays(floor($serial));
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        // Try standard date formats
        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseBirthDate(string $value): ?\Illuminate\Support\Carbon
    {
        $value = trim($value);
        if ($value === '' || $value === 'null') return null;

        // If it's just a number, it could be age or day
        if (is_numeric($value)) {
            $num = (int) $value;
            // If it looks like a year
            if ($num > 1950 && $num < 2010) {
                return \Illuminate\Support\Carbon::create($num, 1, 1);
            }
            // If it looks like an age (15-80), calculate approximate year
            if ($num >= 15 && $num <= 80) {
                return \Illuminate\Support\Carbon::now()->subYears($num)->startOfYear();
            }
        }

        // If it's an Excel serial date
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            $serial = (float) $value;
            if ($serial > 30000 && $serial < 50000) {
                try {
                    return \Illuminate\Support\Carbon::create(1899, 12, 30)->addDays(floor($serial));
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
