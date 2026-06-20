<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Department;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\EmployeeContact;
use App\Models\Admin\Hr\EmployeeEducation;
use App\Models\Admin\Hr\WorkSchedule;
use App\Models\Admin\Project;
use App\Models\Admin\Hr\JobPosition;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    protected array $maleFirstNames = [
        'محمد', 'أحمد', 'علي', 'عمر', 'خالد', 'حسن', 'حسين', 'عبدالله',
        'محمود', 'إبراهيم', 'يوسف', 'مصطفى', 'عبدالرحمن', 'سامر', 'ناصر',
        'فادي', 'مازن', 'باسل', 'رامي', 'عماد', 'تامر', 'زياد', 'هاني',
        'وائل', 'غسان', 'ربيع', 'رياض', 'أيمن', 'بشار', 'جمال',
    ];

    protected array $femaleFirstNames = [
        'سارة', 'نور', 'مريم', 'فاطمة', 'حنان', 'رنا', 'ليلى', 'هدى',
        'منى', 'نادين', 'ريم', 'داليا', 'سوسن', 'أمل', 'وفاء', 'نوال',
        'سلمى', 'مها', 'أماني', 'رشا', 'سميرة', 'نادية', 'بشرى', 'إيمان',
        'يسرى', 'ناهدة', 'صباح', 'ابتسام', 'سلوى', 'كوثر',
    ];

    protected array $lastNames = [
        'السيد', 'الخطيب', 'الحسن', 'الصالح', 'الشامي', 'الحلبي', 'الدمشقي',
        'النوري', 'القاسم', 'المالكي', 'الكيلاني', 'العسلي', 'الحموي',
        'الحريري', 'الجندي', 'السكري', 'البصري', 'النجار', 'الحداد', 'الكاتب',
        'الخليل', 'الرفاعي', 'البيطار', 'الصباغ', 'المقدسي', 'النحاس',
        'الزيات', 'الحكيم', 'العظم', 'الأحدب',
    ];

    protected array $qualifications = [
        'إجازة', 'ماجستير', 'دكتوراه', 'معهد', 'ثانوية',
    ];

    protected array $specializations = [
        'الهندسة المدنية', 'الهندسة المعلوماتية', 'إدارة الأعمال', 'المحاسبة',
        'الشريعة', 'اللغة العربية', 'اللغة الإنجليزية', 'التاريخ',
        'علم النفس', 'الخدمة الاجتماعية', 'الطب', 'الصيدلة',
        'الاقتصاد', 'الإحصاء', 'التربية', 'القانون',
    ];

    protected array $universities = [
        'جامعة حلب', 'جامعة دمشق', 'جامعة البعث', 'جامعة تشرين',
        'جامعة الفرات', 'الجامعة الافتراضية', 'جامعة حلب - كلية الشريعة',
        'المعهد التقاني', 'كلية المجتمع',
    ];

    public function run(): void
    {
        // ── Clear existing records (cascade deletes contacts, educations, work schedules) ──
        WorkSchedule::query()->delete();
        EmployeeContact::query()->delete();
        EmployeeEducation::query()->delete();
        Employee::query()->delete();

        $centers = Center::all(['id'])->pluck('id')->toArray();
        $projects = Project::all(['id'])->pluck('id')->toArray();
        $departments = Department::all(['id'])->pluck('id')->toArray();
        $employeeCode = 1;

        $this->command->info('جارٍ إنشاء الموظفين...');

        foreach ($centers as $centerId) {
            // 8 employees per center = 80 total
            $employeesPerCenter = 8;

            for ($i = 0; $i < $employeesPerCenter; $i++) {
                $isMale = rand(0, 100) < 60; // 60% male
                $firstName = $isMale
                    ? $this->maleFirstNames[array_rand($this->maleFirstNames)]
                    : $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
                $lastName = $this->lastNames[array_rand($this->lastNames)];

                $code = 'EMP' . str_pad($employeeCode, 5, '0', STR_PAD_LEFT);

                $statuses = ['active', 'active', 'active', 'active', 'active', 'active', 'active', 'inactive'];
                $status = $statuses[array_rand($statuses)];

                $maritalStatuses = ['single', 'married', 'married', 'married', 'divorced', 'widowed'];
                $maritalStatus = $maritalStatuses[array_rand($maritalStatuses)];

                $employee = Employee::create([
                    'employee_code' => $code,
                    'first_name_ar' => $firstName,
                    'last_name_ar' => $lastName,
                    'first_name_en' => null,
                    'last_name_en' => null,
                    'gender' => $isMale ? 'male' : 'female',
                    'status' => $status,
                    'id_number' => rand(100000, 999999) . rand(100000, 999999),
                    'marital_status' => $maritalStatus,
                    'children_count' => $maritalStatus === 'married' ? rand(0, 6) : 0,
                    'birth_date' => now()->subYears(rand(22, 60))->subDays(rand(0, 364))->format('Y-m-d'),
                    'birth_place' => ['دمشق', 'حلب', 'حمص', 'اللاذقية', 'إدلب', 'حماة'][rand(0, 5)],
                    'nationality' => 'سوري',
                    'center_id' => $centerId,
                    'department_id' => $departments[array_rand($departments)],
                    'project_id' => $projects[array_rand($projects)],
                    'has_photo' => rand(0, 100) < 70,
                    'has_cv' => rand(0, 100) < 60,
                    'has_id_copy' => true,
                    'has_qualification' => rand(0, 100) < 80,
                    'has_experience_certs' => rand(0, 100) < 40,
                    'has_offer_letter' => rand(0, 100) < 90,
                    'has_contract_doc' => rand(0, 100) < 85,
                    'has_employee_data' => true,
                    'has_job_description' => rand(0, 100) < 70,
                    'has_signature_movements' => rand(0, 100) < 30,
                    'has_security_audit' => rand(0, 100) < 20,
                    'has_reference_audit' => rand(0, 100) < 25,
                    'has_code_of_conduct' => rand(0, 100) < 60,
                    'has_clearance' => rand(0, 100) < 15,
                    'has_receipt' => rand(0, 100) < 50,
                    'has_resignation' => false,
                    'has_verbal_warning_doc' => rand(0, 100) < 5,
                    'has_written_warning_doc' => rand(0, 100) < 3,
                    'has_termination_warning_doc' => false,
                    'has_termination_doc' => false,
                    'has_blacklist_doc' => false,
                ]);

                // Create contact(s)
                $hasPhone = rand(0, 100) < 95;
                $hasEmail = rand(0, 100) < 60;

                if ($hasPhone) {
                    EmployeeContact::create([
                        'employee_id' => $employee->id,
                        'type' => 'phone',
                        'value' => '09' . rand(10, 99) . '-' . rand(100, 999) . '-' . rand(100, 999),
                        'is_primary' => true,
                    ]);
                }

                if ($hasEmail) {
                    EmployeeContact::create([
                        'employee_id' => $employee->id,
                        'type' => 'email',
                        'value' => $firstName . '.' . $lastName . rand(1, 99) . '@rowad.app',
                        'is_primary' => !$hasPhone,
                    ]);
                }

                // Create education record
                $hasEducation = rand(0, 100) < 85;
                if ($hasEducation) {
                    EmployeeEducation::create([
                        'employee_id' => $employee->id,
                        'qualification' => $this->qualifications[array_rand($this->qualifications)],
                        'specialization' => $this->specializations[array_rand($this->specializations)],
                        'university' => $this->universities[array_rand($this->universities)],
                        'grade' => ['مقبول', 'جيد', 'جيد جداً', 'ممتاز'][rand(0, 3)],
                        'graduation_year' => rand(2000, 2025),
                    ]);
                }

                $employeeCode++;
            }
        }

        // Create one manager/admin employee with known data
        Employee::create([
            'employee_code' => 'ADMIN001',
            'first_name_ar' => 'مدير',
            'last_name_ar' => 'النظام',
            'first_name_en' => 'Admin',
            'last_name_en' => 'System',
            'gender' => 'male',
            'status' => 'active',
            'id_number' => '1000000001',
            'marital_status' => 'married',
            'children_count' => 2,
            'birth_date' => '1985-01-01',
            'birth_place' => 'دمشق',
            'nationality' => 'سوري',
            'center_id' => $centers[0],
            'department_id' => $departments[0],
            'project_id' => $projects[0],
            'has_photo' => true,
            'has_cv' => true,
            'has_id_copy' => true,
            'has_qualification' => true,
            'has_offer_letter' => true,
            'has_contract_doc' => true,
            'has_employee_data' => true,
            'has_job_description' => true,
            'has_code_of_conduct' => true,
        ]);

        $this->command->info('تم إنشاء ' . Employee::count() . ' موظف و ' . EmployeeContact::count() . ' جهة اتصال و ' . EmployeeEducation::count() . ' مؤهل علمي و ' . WorkSchedule::count() . ' جدول دوام');
    }
}
