<?php

namespace Database\Seeders;

use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Database\Seeder;

class CoursePeriodSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        if (!$project) return;

        // Periods
        $periods = [
            ['name_ar' => 'الدورة الأولى 2026', 'year' => 2026, 'start_date' => '2026-01-15', 'end_date' => '2026-04-15'],
            ['name_ar' => 'الدورة الثانية 2026', 'year' => 2026, 'start_date' => '2026-05-01', 'end_date' => '2026-08-01'],
            ['name_ar' => 'الدورة الثالثة 2026', 'year' => 2026, 'start_date' => '2026-09-01', 'end_date' => '2026-12-31'],
        ];

        foreach ($periods as $data) {
            Period::firstOrCreate(
                ['project_id' => $project->id, 'name_ar' => $data['name_ar']],
                array_merge($data, ['project_id' => $project->id])
            );
        }

        // Courses
        $courses = [
            ['name_ar' => 'اللغة العربية', 'duration' => 90],
            ['name_ar' => 'الرياضيات', 'duration' => 90],
            ['name_ar' => 'اللغة الإنجليزية', 'duration' => 90],
            ['name_ar' => 'العلوم', 'duration' => 90],
            ['name_ar' => 'الحاسوب', 'duration' => 45],
        ];

        foreach ($courses as $data) {
            Course::firstOrCreate(
                ['project_id' => $project->id, 'name_ar' => $data['name_ar']],
                array_merge($data, ['project_id' => $project->id])
            );
        }

        // Link courses to periods
        $allCourses = Course::all();
        $allPeriods = Period::all();
        foreach ($allPeriods as $period) {
            foreach ($allCourses as $course) {
                if (!$period->courses()->where('course_id', $course->id)->exists()) {
                    $period->courses()->attach($course->id);
                }
            }
        }

        // Enroll students
        $students = Student::all();
        foreach ($students as $i => $student) {
            $course = $allCourses->get($i % $allCourses->count());
            $period = $allPeriods->get(0);
            StudentEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id, 'period_id' => $period->id],
                [
                    'enrollment_date' => $period->start_date,
                    'status' => 'enrolled',
                    'is_certificate_eligible' => false,
                ]
            );
        }

        $this->command->info('تم إنشاء ' . $allPeriods->count() . ' periods و ' . $allCourses->count() . ' courses و ' . StudentEnrollment::count() . ' تسجيلات');
    }
}
