<?php

namespace Database\Seeders;

use App\Models\Admin\ProjectTask;
use App\Models\User;
use App\Models\Admin\Center;
use Illuminate\Database\Seeder;

class ProjectTaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $centers = Center::all();

        if ($users->isEmpty()) {
            $this->command->warn('لا يوجد مستخدمين، قم بتشغيل UserSeeder أولاً');
            return;
        }

        $tasks = [
            [
                'title' => 'تجهيز قاعة المؤتمر السنوي',
                'purpose' => 'تجهيز القاعة الرئيسية للمؤتمر السنوي للمؤسسة لعام 2026 وتوفير كافة التجهيزات اللازمة',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-15',
                'needs_media_coverage' => true,
                'needs_costs' => true,
                'costs_details' => 'ميزانية تجهيز القاعة: 15,000 ريال (كراسي، طاولات، شاشات عرض، أنظمة صوت)',
                'needs_equipment' => true,
                'equipment_details' => 'شاشة عرض 75 بوصة - 2 جهاز, نظام صوتي متكامل, 50 كرسي قابل للطي',
                'status' => 'in_progress',
                'executed' => null,
                'has_delay' => null,
            ],
            [
                'title' => 'إطلاق منصة التدريب الإلكتروني',
                'purpose' => 'تطوير وإطلاق المنصة الإلكترونية الجديدة للتدريب عن بعد لتشمل جميع الدورات المتاحة',
                'start_date' => '2026-06-01',
                'end_date' => '2026-08-30',
                'needs_media_coverage' => true,
                'needs_costs' => true,
                'costs_details' => 'تكلفة التطوير: 45,000 ريال (استضافة، برمجة، تصميم واجهات)',
                'needs_equipment' => false,
                'status' => 'pending',
            ],
            [
                'title' => 'حملة التوعية بأضرار المخدرات',
                'purpose' => 'تنظيم حملة توعوية في 5 مدارس ثانوية بالتعاون مع إدارة مكافحة المخدرات',
                'start_date' => '2026-05-01',
                'end_date' => '2026-06-15',
                'needs_media_coverage' => true,
                'needs_costs' => true,
                'costs_details' => 'مطبوعات وبروشورات: 8,000 ريال، حافلات نقل الطلاب: 5,000 ريال',
                'needs_equipment' => true,
                'equipment_details' => 'جهاز عرض متنقل، مكبرات صوت محمولة، خيمة 5×5 متر',
                'status' => 'completed',
                'executed' => true,
                'has_delay' => false,
                'media_coverage_done' => true,
            ],
            [
                'title' => 'صيانة مبنى المركز الرئيسي',
                'purpose' => 'إجراء الصيانة الدورية للمبنى الرئيسي بما في ذلك الكهرباء والسباكة ودهان الواجهات',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
                'needs_media_coverage' => false,
                'needs_costs' => true,
                'costs_details' => 'ميزانية الصيانة: 25,000 ريال (مواد بناء، أجور عمال)',
                'needs_equipment' => true,
                'equipment_details' => 'معدات صيانة: سقالات، مضخات مياه، أدوات كهربائية',
                'status' => 'completed',
                'executed' => true,
                'has_delay' => true,
                'delay_reason' => 'تأخر وصول مواد البناء بسبب عطل في الشحن لمدة أسبوع',
                'media_coverage_done' => false,
                'no_media_coverage_reason' => 'لا تتطلب تغطية إعلامية',
            ],
            [
                'title' => 'تحديث نظام إدارة الموارد البشرية',
                'purpose' => 'ترقية نظام HRMS الحالي لإضافة وحدة الإجازات والإجازات المرضية إلكترونياً',
                'start_date' => '2026-05-15',
                'end_date' => '2026-06-30',
                'needs_media_coverage' => false,
                'needs_costs' => false,
                'needs_equipment' => false,
                'status' => 'delayed',
                'executed' => false,
                'not_executed_reason' => 'لم يتم التنفيذ بسبب عدم توفر المبرمج المختص',
                'has_delay' => true,
                'delay_reason' => 'المبرمس المختص في إجازة مرضية وسيعود في 15 يوليو',
            ],
            [
                'title' => 'تنظيم يوم المهنة السنوي',
                'purpose' => 'تنظيم يوم المهنة لطلاب الثانوية العامة بالتعاون مع 10 جهات حكومية وخاصة',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-20',
                'needs_media_coverage' => true,
                'needs_costs' => true,
                'costs_details' => 'شاليهات عرض: 20,000 ريال، ضيافة: 10,000 ريال، هدايا تذكارية: 5,000 ريال',
                'needs_equipment' => true,
                'equipment_details' => '20 طاولة عرض، 40 كرسي، شاشات عرض، نظام صوتي',
                'status' => 'pending',
            ],
            [
                'title' => 'إعداد التقرير الربعي الثالث',
                'purpose' => 'جمع وتحليل بيانات الأداء للربع الثالث من العام وتقديم التوصيات للإدارة العليا',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-15',
                'needs_media_coverage' => false,
                'needs_costs' => false,
                'needs_equipment' => false,
                'status' => 'pending',
            ],
            [
                'title' => 'تنفيذ برنامج التدريب الصيفي للطلاب',
                'purpose' => 'برنامج تدريبي مكثف لمدة 6 أسابيع لـ 50 طالباً في مجالات البرمجة والإدارة',
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-15',
                'needs_media_coverage' => true,
                'needs_costs' => true,
                'costs_details' => 'رواتب المدربين: 30,000 ريال، مواد تدريبية: 10,000 ريال، شهادات: 3,000 ريال',
                'needs_equipment' => true,
                'equipment_details' => '20 حاسوب محمول، سبورة ذكية، طابعة، جهاز عرض',
                'status' => 'in_progress',
                'executed' => null,
                'has_delay' => null,
            ],
            [
                'title' => 'حملة التبرع بالدم',
                'purpose' => 'تنظيم حملة تبرع بالدم بالتعاون مع بنك الدم المركزي تستهدف 200 متبرع',
                'start_date' => '2026-03-15',
                'end_date' => '2026-03-20',
                'needs_media_coverage' => true,
                'needs_costs' => false,
                'needs_equipment' => true,
                'equipment_details' => 'خيمتان طبيتان، أسرة فحص، أجهزة فصيلة دم سريعة',
                'status' => 'cancelled',
                'executed' => false,
                'not_executed_reason' => 'تم إلغاء الحملة بسبب عدم التنسيق مع بنك الدم',
                'has_delay' => null,
                'media_coverage_done' => false,
            ],
            [
                'title' => 'تطوير دليل الإجراءات الإدارية',
                'purpose' => 'إعداد وتحديث دليل شامل لجميع الإجراءات الإدارية والمالية في المؤسسة',
                'start_date' => '2026-02-01',
                'end_date' => '2026-05-30',
                'needs_media_coverage' => false,
                'needs_costs' => false,
                'needs_equipment' => false,
                'status' => 'completed',
                'executed' => true,
                'has_delay' => false,
            ],
        ];

        foreach ($tasks as $i => $data) {
            $creator = $users->random();
            $assignee = $users->random();

            // Ensure creator !== assignee sometimes
            while ($assignee->id === $creator->id && $users->count() > 1) {
                $assignee = $users->random();
            }

            ProjectTask::create(array_merge($data, [
                'assigned_to' => $assignee->id,
                'created_by' => $creator->id,
                'center_id' => $centers->isNotEmpty() ? $centers->random()->id : null,
                'needs_media_coverage' => $data['needs_media_coverage'] ?? false,
                'needs_costs' => $data['needs_costs'] ?? false,
                'needs_equipment' => $data['needs_equipment'] ?? false,
                'executed' => $data['executed'] ?? null,
                'has_delay' => $data['has_delay'] ?? null,
                'media_coverage_done' => $data['media_coverage_done'] ?? null,
            ]));
        }

        $this->command->info('تم إضافة 10 مهام بنجاح');
    }
}
