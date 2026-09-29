<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Cohort;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeAttendance;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Hr\LeaveRequest;
use App\Models\Admin\Hr\LeaveType;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية معزولة لاختبارات المتصفح (tests/e2e). لا تُستدعى من DatabaseSeeder ولا تُستخدم في الإنتاج.
 *
 *   php artisan migrate:fresh --seed --seeder=UiE2ESeeder   (على قاعدة بيانات تجريبية فقط، مثل database/database.sqlite المحلية)
 *
 * الحسابات (كلمة المرور password):
 *   admin@test.local    سوبر أدمن
 *   hr@test.local       موظف: الموظفون + الإجازات (عرض/إضافة/تعديل، بلا حذف)
 *   students@test.local موظف: الطلاب + المقررات (عرض/إضافة/تعديل، بلا حذف)
 *   limited@test.local  موظف بلا أي صلاحية إدارية
 */
class UiE2ESeeder extends Seeder
{
    public function run(): void
    {
        $mk = fn (string $name, string $email, string $type = 'employee') => User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'type' => $type, 'password' => bcrypt('password'), 'must_change_password' => false,
                'is_active' => true, 'email_verified_at' => now()]
        );
        $grant = function (User $u, array $models) {
            Permission::updateOrCreate(['user_id' => $u->id], ['model_names' => $models, 'can_view' => true, 'can_create' => true,
                'can_edit' => true, 'can_delete' => false]);
        };

        $admin = $mk('مدير النظام', 'admin@test.local', 'super-admin');
        $hr = $mk('موظف الموارد البشرية', 'hr@test.local');
        $stu = $mk('موظف الطلاب', 'students@test.local');
        $mk('موظف محدود', 'limited@test.local');
        $grant($hr, ['App\Models\Admin\Hr\Employee', 'App\Models\Admin\Hr\JobPosition', 'App\Models\Admin\Hr\LeaveRequest',
            'App\Models\Admin\Hr\LeaveType', 'App\Models\Admin\Hr\EmployeeAttendance', 'App\Models\Admin\Center', 'App\Models\Admin\Project']);
        $grant($stu, ['App\Models\Admin\Student\Student', 'App\Models\Admin\Student\Course', 'App\Models\Admin\Student\Period',
            'App\Models\Admin\Student\Attendance', 'App\Models\Admin\Center', 'App\Models\Admin\Project', 'App\Models\Admin\Cohort']);

        $c1 = Center::firstOrCreate(['name' => 'مركز الرواد الرئيسي — فرع الشمال الشرقي للتنمية المجتمعية'], ['address' => 'شارع طويل جدًا لاختبار التفاف النص', 'phone' => '+963 11 1234567']);
        $c2 = Center::firstOrCreate(['name' => 'مركز ب'], ['phone' => '+963 21 7654321']);
        $project = Project::firstOrCreate(['name' => 'مشروع التعليم المجتمعي']);
        $dept = Department::firstOrCreate(['name_ar' => 'إدارة البرامج'], ['name_en' => 'Programs', 'is_active' => true]);
        $pos = JobPosition::firstOrCreate(['title_ar' => 'منسق برامج'], ['title_en' => 'Program Coordinator']);
        Cohort::firstOrCreate(['project_id' => $project->id, 'name' => 'فوج صباحي'], ['shift' => 'صباحي', 'code' => 'AM-1', 'is_active' => true]);

        $names = ['ليلى', 'أحمد', 'سارة', 'يوسف', 'مريم', 'خالد', 'هدى', 'عمر', 'رنا', 'كريم', 'دعاء', 'باسل'];
        $employees = [];
        foreach ($names as $i => $n) {
            $code = sprintf('EMP-%03d', $i + 1);
            $employees[] = Employee::where('employee_code', $code)->first() ?? Employee::forceCreate([
                'employee_code' => $code, 'first_name_ar' => $n,
                'last_name_ar' => $i === 0 ? 'العبد الله الحمصي المتوسط الطويل جدًا في الاسم' : 'اختبار'.($i + 1),
                'first_name_en' => 'Emp'.($i + 1), 'last_name_en' => 'Test', 'gender' => $i % 2 ? 'male' : 'female',
                'status' => 'active', 'center_id' => $i % 3 ? $c1->id : $c2->id, 'department_id' => $dept->id,
                'project_id' => $project->id, 'children_count' => 0,
            ]);
        }

        // ربط موظف بحساب hr@test.local ليعمل «طلب إجازة جديد» (يتطلب أن يكون المستخدم موظفًا)
        $employees[1]->forceFill(['user_id' => $hr->id])->save();

        $annual = LeaveType::firstOrCreate(['name_ar' => 'إجازة سنوية'], ['annual_days' => 21, 'requires_approval' => true, 'color' => '#2E7D32', 'icon' => 'bi-sun', 'is_active' => true]);
        $sick = LeaveType::firstOrCreate(['name_ar' => 'إجازة مرضية'], ['annual_days' => 10, 'requires_approval' => true, 'color' => '#C62828', 'icon' => 'bi-heart-pulse', 'is_active' => true]);
        foreach (LeaveRequest::count() ? [] : [[0, $annual, 'pending'], [1, $sick, 'pending'], [2, $annual, 'approved']] as [$idx, $type, $st]) {
            LeaveRequest::forceCreate(['employee_id' => $employees[$idx]->id, 'leave_type_id' => $type->id, 'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(), 'days_count' => 2, 'reason' => 'سبب تجريبي', 'status' => $st]);
        }
        foreach (EmployeeAttendance::count() ? [] : array_slice($employees, 0, 4) as $e) {
            foreach ([0, 1, 2] as $d) {
                EmployeeAttendance::forceCreate(['employee_id' => $e->id, 'date' => now()->startOfMonth()->addDays($d)->toDateString(), 'status' => $d === 1 ? 'absent' : 'present']);
            }
        }

        // بيانات تايم شيت معروفة لآذار 2026 (اختبارات e2e): EMP-002 غياب يوم 2 وعذر يوم 3، وEMP-003 غياب يوم 31
        // (الجدول الافتراضي: الجمعة والسبت عطلة ⇒ 23 يوم عمل في هذا الشهر)
        foreach ([[1, '2026-03-02', 'absent', null], [1, '2026-03-03', 'excused', $annual->id], [2, '2026-03-31', 'absent', null]] as [$i, $d, $st, $lt]) {
            EmployeeAttendance::updateOrCreate(['employee_id' => $employees[$i]->id, 'date' => $d], ['status' => $st, 'leave_type_id' => $lt]);
        }

        $course = Course::firstOrCreate(['project_id' => $project->id, 'name_ar' => 'اللغة الإنكليزية — المستوى الأول']);
        $period = Period::firstOrCreate(['project_id' => $project->id, 'name_ar' => 'الفصل الأول', 'year' => (int) now()->year],
            ['start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'is_active' => true]);
        for ($i = 1; $i <= 14; $i++) {
            $s = Student::where('student_code', sprintf('STU-%03d', $i))->first() ?? Student::forceCreate(['student_code' => sprintf('STU-%03d', $i), 'first_name_ar' => 'طالب', 'last_name_ar' => 'تجريبي '.$i,
                'first_name_en' => 'Student', 'last_name_en' => 'Test '.$i, 'gender' => $i % 2 ? 'male' : 'female', 'status' => 'active',
                'center_id' => $c1->id, 'phone' => '+963 9'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'email' => "student$i@test.local", 'enrollment_date' => now()->toDateString()]);
            if ($i <= 8 && ! StudentEnrollment::where('student_id', $s->id)->exists()) {
                StudentEnrollment::forceCreate(['student_id' => $s->id, 'course_id' => $course->id, 'period_id' => $period->id, 'enrollment_date' => now()->toDateString(), 'status' => 'enrolled']);
            }
        }
    }
}
