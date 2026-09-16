<?php

namespace Database\Seeders;

use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use Illuminate\Database\Seeder;

class MonthlyReportTemplatesSeeder extends Seeder
{
    /*
    * Seeder قالب التقارير الشهرية المستقل — من ملف "نموذج تقرير مشروع.docx".
    * يُشغَّل بأمر: php artisan db:seed --class=MonthlyReportTemplatesSeeder
    * إعادة التشغيل تحدّث القالب (updateOrCreate) دون حذف التقارير الموجودة.
    */
    public function run(): void
    {
        $templates = $this->templates();

        foreach ($templates as $data) {
            MonthlyReportTemplate::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
            fwrite(STDOUT, "Monthly report template: {$data['key']} ({$data['title_ar']}) — ready.\n");
        }
    }

    private function templates(): array
    {
        return [
            [
                'key' => 'monthly-project-report',
                'title_ar' => 'نموذج تقرير المشروع الشهري',
                'slug' => 'monthly-project-report',
                'version' => 1,
                'is_active' => true,
                'json_definition' => [
                    'header_meta' => [
                        'تقرير شهري — يُعدّه مدير المشروع ويُراجع من إدارة المشاريع قبل الاعتماد.',
                    ],
                    'sections' => [
                        [
                            'key' => 'exec_summary',
                            'title' => '1 - ملخص تنفيذي ومؤشرات شهرية',
                            'type' => 'paragraph',
                            'assignee_role' => 'مدير المشروع',
                        ],
                        [
                            'key' => 'monthly_indicators',
                            'title' => 'المؤشرات الشهرية',
                            'type' => 'table',
                            'columns' => ['المؤشر', 'الرقم', 'اسم المكتب', 'تعريف مختصر / ملاحظة'],
                        ],
                        [
                            'key' => 'achievements',
                            'title' => '2 - أبرز الإنجازات والتحديات',
                            'type' => 'table',
                            'columns' => ['البند', 'ملخص لا يتجاوز 3 أسطر'],
                        ],
                        [
                            'key' => 'challenges_support',
                            'title' => 'الدعم المطلوب من الإدارة',
                            'type' => 'fields',
                            'fields' => ['أبرز التحديات', 'الدعم المطلوب من الإدارة'],
                        ],
                        [
                            'key' => 'locations',
                            'title' => '3 - مواقع التنفيذ',
                            'type' => 'table',
                            'columns' => ['الموقع / المجتمع', 'نوع الخدمة', 'الفئات المستهدفة', 'عدد المستفيدين', 'مخاطر الوصول', 'ملاحظات'],
                        ],
                        [
                            'key' => 'activities',
                            'title' => '4 - عملية التقدم في إنجاز الأنشطة',
                            'type' => 'table',
                            'columns' => ['اسم المكتب', 'اسم النشاط', 'حالة النشاط', 'الفعاليات الموجودة ضمن النشاط', 'نسبة الإنجاز', 'ملاحظات'],
                        ],
                        [
                            'key' => 'activities_summary',
                            'title' => 'ملخص نشاطات الشهر',
                            'type' => 'paragraph',
                        ],
                        [
                            'key' => 'kpis',
                            'title' => '5 - مؤشرات الأداء KPIs',
                            'type' => 'table',
                            'columns' => ['مؤشر المخرجات', 'مؤشر النتائج', 'نسبة تحقيق المستهدف', 'مقارنة بالفترة السابقة', 'تحليل الأداء'],
                        ],
                        [
                            'key' => 'risks',
                            'title' => '6 - إدارة المخاطر',
                            'type' => 'table',
                            'columns' => ['المخاطر الحالية', 'مستوى الخطورة', 'أثرها على التنفيذ', 'إجراءات التخفيف', 'المخاطر الجديدة'],
                        ],
                        [
                            'key' => 'meal',
                            'title' => '7 - المراقبة والتقييم والمساءلة والتعلم MEAL',
                            'type' => 'fields',
                            'fields' => ['نتائج الزيارات الميدانية', 'نتائج التحقق من البيانات', 'مدى الالتزام بالمؤشرات', 'الدروس المستفادة', 'التحسينات المطلوبة'],
                            'assignee_role' => 'مدير المتابعة والتقييم',
                        ],
                        [
                            'key' => 'compliance',
                            'title' => '8 - الامتثال والالتزام',
                            'type' => 'table',
                            'columns' => ['مجال الامتثال', 'الالتزام بخطة المشروع', 'الالتزام بشروط المانحين', 'الالتزام بالسياسات الداخلية', 'نتائج التدقيق والمتابعة'],
                        ],
                        [
                            'key' => 'general_challenges',
                            'title' => '9 - التحديات العامة',
                            'type' => 'fields',
                            'fields' => ['تحديات التنفيذ', 'تحديات الأمن والوصول', 'تحديات الموارد البشرية', 'تحديات المشتريات'],
                        ],
                        [
                            'key' => 'lessons',
                            'title' => '10 - الدروس المستفادة وقصص النجاح والتوصيات والقرارات المطلوبة',
                            'type' => 'list',
                        ],
                        [
                            'key' => 'success_stories',
                            'title' => 'قصص النجاح',
                            'type' => 'paragraph',
                        ],
                        [
                            'key' => 'recommendations',
                            'title' => 'التوصيات',
                            'type' => 'paragraph',
                        ],
                        [
                            'key' => 'decisions',
                            'title' => 'القرارات المطلوبة',
                            'type' => 'paragraph',
                        ],
                        [
                            'key' => 'annexes',
                            'title' => '11 - الملاحق المطلوبة مع التقرير الشهري',
                            'type' => 'list',
                        ],
                        [
                            'key' => 'naming',
                            'title' => '12 - صيغة تسمية التقرير والملفات المرفقة',
                            'type' => 'fields',
                            'fields' => ['صيغة التسمية (YYYY-MM_اسم المشروع_نوع الملحق_النسخة)', 'مثال تسمية التقرير', 'مثال تسمية الملفات المرفقة'],
                        ],
                        [
                            'key' => 'pre_submission',
                            'title' => '13 - المؤشر النهائي قبل الإرسال',
                            'type' => 'table',
                            'columns' => ['البند', 'نعم/لا', 'ملاحظة'],
                        ],
                        [
                            'key' => 'approvals',
                            'title' => '14 - التواقيع المطلوبة',
                            'type' => 'table',
                            'columns' => ['البند', 'الاسم والصفة', 'التاريخ'],
                        ],
                    ],
                ],
            ],
        ];
    }
}