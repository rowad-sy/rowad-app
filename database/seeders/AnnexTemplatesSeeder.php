<?php

namespace Database\Seeders;

use App\Models\Admin\ProjectDocs\AnnexTemplate;
use Illuminate\Database\Seeder;

class AnnexTemplatesSeeder extends Seeder
{
    /*
    * Seeder قوالب وثائق المشاريع الإنتاجية — من ملفات "ملحقات مشروع" المرجعية.
    * يُشغَّل بأمر: php artisan db:seed --class=AnnexTemplatesSeeder
    * إعادة التشغيل تحدّث القوالب (updateOrCreate) دون حذف الوثائق الموجودة.
    */
    public function run(): void
    {
        $templates = $this->templates();

        foreach ($templates as $data) {
            $key = $data['key'];

            if (isset(self::DEFAULT_PAGES[$key])) {
                $data['default_page_count'] = self::DEFAULT_PAGES[$key];
            }

            if (isset(self::SECTION_PAGES[$key])) {
                $mapping = self::SECTION_PAGES[$key];
                $maxPage = $data['default_page_count'] ?? 1;

                foreach ($data['json_definition']['sections'] as $i => $section) {
                    $section['page'] = $mapping[$section['key']] ?? 1;
                    $section['page'] = max(1, min((int) $section['page'], $maxPage));
                    $data['json_definition']['sections'][$i] = $section;
                }
            }

            AnnexTemplate::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
            fwrite(STDOUT, "Annex template: {$data['key']} ({$data['title_ar']}) — ready.\n");
        }
    }

    /*
     * عدد الصفحات الافتراضي لكل قالب — قابل للتعديل من صفحة القالب أو عند إنشاء الوثيقة.
     * مرجع: project-files/stage 1/project-managment/project-document/عدد صفحات الوثائق.txt
     */
    private const DEFAULT_PAGES = [
        'project-card' => 1,
        'project-idea' => 2,
        'project-preliminary-study' => 6,
        'beneficiary-criteria' => 5,
        'project-monthly-report' => 15,
        'project-final-report' => 19,
    ];

    /*
     * توزيع الأقسام على الصفحات مطابِقاً لملفات الوورد المرجعية.
     */
    private const SECTION_PAGES = [
        'project-card' => [
            'basic' => 1, 'timeline' => 1, 'target' => 1, 'goal' => 1, 'activities' => 1,
        ],
        'project-idea' => [
            'basic' => 1, 'need' => 1, 'background' => 1, 'problem' => 1, 'evidence' => 1, 'target' => 1,
            'goal' => 2, 'indicators' => 2, 'activities_main' => 2, 'activities_secondary' => 2, 'cost' => 2,
            'integration' => 2, 'risks' => 2, 'decisions' => 2, 'sustainability' => 2,
        ],
        'project-preliminary-study' => [
            'summary_basic' => 1, 'summary_need' => 1, 'summary_solution' => 1,
            'summary_target' => 1, 'summary_decision' => 1, 'summary_priority' => 1,
            'context_problem' => 2, 'context_field' => 2, 'context_interviews' => 2, 'context_site' => 2,
            'design_logframe' => 3, 'design_outputs' => 3,
            'activities_main' => 4, 'activities_secondary' => 4, 'beneficiaries_size' => 4,
            'feasibility_team' => 5, 'feasibility_partners' => 5, 'feasibility_permits' => 5,
            'feasibility_alternatives' => 5, 'cost_budget' => 5, 'cost_total' => 5,
            'risks' => 6, 'sustainability' => 6, 'recommendation' => 6, 'verification' => 6,
        ],
        'beneficiary-criteria' => [
            'basic' => 1, 'target_classes' => 1,
            'eligibility_geo' => 2, 'eligibility_demo' => 2, 'eligibility_econ' => 2,
            'eligibility_vuln' => 3, 'eligibility_project' => 3, 'docs' => 3,
            'priority_criteria' => 4, 'exclusion' => 4, 'exclusion_notes' => 4,
            'decision_frame' => 5, 'reserve' => 5, 'comments' => 5,
        ],
        'project-monthly-report' => [
            'exec_summary' => 1, 'monthly_indicators' => 1,
            'achievements' => 2, 'challenges' => 2,
            'locations' => 3,
            'activities' => 4, 'activities_summary' => 4,
            'kpis' => 5,
            'risks' => 6,
            'meal' => 7,
            'compliance' => 8,
            'general_challenges' => 9,
            'lessons' => 10, 'success_stories' => 10,
            'recommendations' => 11, 'decisions' => 11,
            'annexes' => 12,
            'naming' => 13,
            'pre_submission' => 14,
            'approvals' => 15,
        ],
        'project-final-report' => [
            'project_card' => 1,
            'summary' => 2,
            'background' => 3,
            'implementation_map' => 4,
            'achieved' => 5, 'not_achieved' => 5,
            'indicators' => 6,
            'impact' => 7,
            'beneficiaries' => 8,
            'partnerships' => 9,
            'logistics' => 10,
            'it' => 11,
            'staff' => 12, 'hr_gaps' => 12, 'training' => 12,
            'finance' => 13, 'finance_notes' => 13,
            'compliance' => 14,
            'meal' => 15,
            'stories' => 16,
            'lessons' => 17,
            'sustainability' => 18, 'next' => 18, 'closing_decisions' => 18,
            'closing' => 19, 'annexes' => 19,
        ],
    ];

    private function templates(): array
    {
        return [

        // ════════════════════════════════════════════════════════════════════
        // بطاقة المشروع — Project Card
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'project-card',
            'title_ar' => 'بطاقة المشروع',
            'slug' => 'project-card',
            'version' => 1,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => [
                    'وثيقة تعريف المشروع — تبقى مرجعاً أثناء التنفيذ وتُرفق في ملف المشروع الرئيسي.',
                ],
                'sections' => [
                    [
                        'key' => 'basic',
                        'title' => 'البيانات الأساسية للمشروع',
                        'type' => 'fields',
                        'fields' => ['كود المشروع', 'اسم المشروع', 'مسار المشروع', 'مدير المشروع', 'مناطق التنفيذ', 'الجهة المنفذة'],
                    ],
                    [
                        'key' => 'timeline',
                        'title' => 'المدة الزمنية',
                        'type' => 'fields',
                        'fields' => ['مدة تنفيذ المشروع', 'بداية المشروع', 'نهاية المشروع'],
                    ],
                    [
                        'key' => 'target',
                        'title' => 'المستفيدون والموازنة',
                        'type' => 'fields',
                        'fields' => ['عدد المستفيدين', 'الفئات المستهدفة', 'الموازنة الإجمالية', 'حالة المشروع'],
                    ],
                    [
                        'key' => 'goal',
                        'title' => 'غاية المشروع',
                        'type' => 'paragraph',
                        'assignee_role' => 'مدير المشروع',
                    ],
                    [
                        'key' => 'activities',
                        'title' => 'أنشطة المشروع',
                        'type' => 'list',
                    ],
                ],
            ],
        ],

        // ════════════════════════════════════════════════════════════════════
        // فكرة المشروع — Project Idea
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'project-idea',
            'title_ar' => 'فكرة المشروع',
            'slug' => 'project-idea',
            'version' => 2,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => [
                    'نموذج مقترح مشروع جديد — يُعبأ قبل رفع الوثيقة للاعتماد.',
                ],
                'sections' => [
                    [
                        'key' => 'basic',
                        'title' => 'المعلومات الأساسية',
                        'type' => 'fields',
                        'fields' => ['كود المشروع', 'اسم المشروع', 'المسار', 'مواقع التنفيذ', 'مدة المشروع', 'الجهة المنفذة'],
                    ],
                    [
                        'key' => 'need',
                        'title' => 'مرجعية الاحتياج',
                        'type' => 'fields',
                        'fields' => ['رقم الاحتياج', 'مصدر الاحتياج', 'تاريخ الدراسة', 'درجة الأهمية النسبية', 'قرار الإدارة التنفيذية'],
                    ],
                    [
                        'key' => 'background',
                        'title' => 'خلفية المشروع',
                        'type' => 'paragraph',
                        'assignee_role' => 'مسؤول مشروع',
                    ],
                    [
                        'key' => 'problem',
                        'title' => 'المشكلة والحل بشكل مختصر',
                        'type' => 'paragraph',
                        'assignee_role' => 'مسؤول مشروع',
                    ],
                    [
                        'key' => 'evidence',
                        'title' => 'الدليل الأولي',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'target',
                        'title' => 'عدد المستفيدين التقريبي والفئة المستهدفة',
                        'type' => 'table',
                        'columns' => ['الفئة', 'العدد التقريبي', 'درجة الهشاشة'],
                    ],
                    [
                        'key' => 'goal',
                        'title' => 'الهدف والنتائج',
                        'type' => 'fields',
                        'fields' => ['الهدف', 'النتائج'],
                    ],
                    [
                        'key' => 'indicators',
                        'title' => 'مؤشرات أولية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'activities_main',
                        'title' => 'أنشطة المشروع — الأنشطة الرئيسية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'activities_secondary',
                        'title' => 'أنشطة المشروع — الأنشطة الثانوية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'cost',
                        'title' => 'الكلفة التقديرية الأولية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'integration',
                        'title' => 'التكامل بين المشاريع',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'risks',
                        'title' => 'المخاطر الأولية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'decisions',
                        'title' => 'القرارات المطلوبة والشركاء المحتملون',
                        'type' => 'fields',
                        'fields' => ['القرار المطلوب', 'الشركاء المحتملون'],
                    ],
                    [
                        'key' => 'sustainability',
                        'title' => 'الاستدامة الأولية',
                        'type' => 'paragraph',
                    ],
                ],
            ],
        ],

        // ════════════════════════════════════════════════════════════════════
        // قالب الدراسة الأولية — Preliminary Study
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'project-preliminary-study',
            'title_ar' => 'الدراسة الأولية للمشروع',
            'slug' => 'preliminary-study',
            'version' => 2,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => [
                    'دراسة أولية لمقترح مشروع — تشمل تحليل الاحتياج وتصميم التدخل والجدوى والمخاطر.',
                ],
                'sections' => [
                    [
                        'key' => 'summary_basic',
                        'title' => 'ملخص تنفيذي — معلومات المشروع الأساسية',
                        'type' => 'fields',
                        'fields' => ['كود المشروع', 'اسم المشروع', 'مسار المشروع', 'مدة المشروع', 'مناطق التنفيذ', 'الجهة المنفذة'],
                    ],
                    [
                        'key' => 'summary_need',
                        'title' => 'الاحتياج',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'summary_solution',
                        'title' => 'الحل',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'summary_target',
                        'title' => 'الفئة المستهدفة',
                        'type' => 'table',
                        'columns' => ['الفئة', 'العدد التقريبي', 'درجة الهشاشة'],
                    ],
                    [
                        'key' => 'summary_decision',
                        'title' => 'القرار المطلوب',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'summary_priority',
                        'title' => 'العلاقة بالأولوية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'context_problem',
                        'title' => 'تحليل الاحتياج والسياق — وصف أسباب المشكلة',
                        'type' => 'paragraph',
                        'assignee_role' => 'مسؤول مشروع',
                    ],
                    [
                        'key' => 'context_field',
                        'title' => 'بيانات ميدانية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'context_interviews',
                        'title' => 'مقابلات',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'context_site',
                        'title' => 'واقع الموقع',
                        'type' => 'fields',
                        'fields' => ['الوضع الحالي', 'المشاريع السابقة في الموقع', 'التحديات الحالية', 'الفجوات'],
                    ],
                    [
                        'key' => 'design_logframe',
                        'title' => 'تصميم التدخل — الإطار المنطقي',
                        'type' => 'table',
                        'columns' => ['الغاية / الهدف', 'الوصف', 'مؤشرات الأداء', 'وسائل التحقق', 'الافتراضات والمخاطر'],
                    ],
                    [
                        'key' => 'design_outputs',
                        'title' => 'المخرجات',
                        'type' => 'table',
                        'columns' => ['المخرج', 'المؤشرات الخاصة بالمخرج', 'وسائل التحقق'],
                    ],
                    [
                        'key' => 'activities_main',
                        'title' => 'أنشطة المشروع — الأنشطة الرئيسية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'activities_secondary',
                        'title' => 'أنشطة المشروع — الأنشطة الثانوية',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'beneficiaries_size',
                        'title' => 'حجم المستفيدين',
                        'type' => 'fields',
                        'fields' => ['المباشرون', 'غير المباشرين'],
                    ],
                    [
                        'key' => 'feasibility_team',
                        'title' => 'الجدوى التشغيلية والمالية — فريق العمل',
                        'type' => 'table',
                        'columns' => ['الصفة الوظيفية', 'العدد', 'المسؤوليات الرئيسية'],
                    ],
                    [
                        'key' => 'feasibility_partners',
                        'title' => 'الشركاء — أولويات المانحين',
                        'type' => 'fields',
                        'fields' => ['الشركاء', 'أولويات المانحين'],
                    ],
                    [
                        'key' => 'feasibility_permits',
                        'title' => 'التصاريح',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'feasibility_alternatives',
                        'title' => 'البدائل',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'cost_budget',
                        'title' => 'الموازنة التقديرية الأولية',
                        'type' => 'table',
                        'columns' => ['نوع الكلفة', 'البند', 'الكتلة الشهرية', 'الكلفة السنوية', 'المجموع'],
                    ],
                    [
                        'key' => 'cost_total',
                        'title' => 'الكلفة الإجمالية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'risks',
                        'title' => 'المخاطر',
                        'type' => 'table',
                        'columns' => ['المخاطر', 'الأولوية', 'الاحتمالية', 'التأثير'],
                    ],
                    [
                        'key' => 'sustainability',
                        'title' => 'الاستدامة',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'recommendation',
                        'title' => 'التوصية النهائية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'verification',
                        'title' => 'آلية التحقق',
                        'type' => 'paragraph',
                    ],
                ],
            ],
        ],

        // ════════════════════════════════════════════════════════════════════
        // استمارة تحديد معايير اختيار المستفيدين — Beneficiary Selection Criteria
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'beneficiary-criteria',
            'title_ar' => 'استمارة تحديد معايير اختيار المستفيدين',
            'slug' => 'beneficiary-criteria',
            'version' => 1,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => [
                    'Beneficiary Selection Criteria Form — يرجى من مدير المشروع تعبئة الاستمارة وفق خصوصية مشروعه وإعادتها إلى إدارة المشاريع.',
                ],
                'sections' => [
                    [
                        'key' => 'basic',
                        'title' => 'القسم الأول: المعلومات الأساسية للمشروع',
                        'type' => 'fields',
                        'fields' => ['اسم المشروع', 'رمز / كود المشروع', 'اسم مدير المشروع', 'اسم المسار', 'مناطق المشروع', 'تاريخ الاستمارة'],
                    ],
                    [
                        'key' => 'target_classes',
                        'title' => 'فئة المستفيدين المستهدفة (اختر ما ينطبق)',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'eligibility_geo',
                        'title' => 'المعيار الجغرافي',
                        'type' => 'fields',
                        'fields' => ['نازح/ة في المنطقة', 'ساكن دائم (+6 شهور) داخل منطقة التدخل المحددة', 'مجتمعات حدودية', 'أخرى (حدد)'],
                    ],
                    [
                        'key' => 'eligibility_demo',
                        'title' => 'المعيار السكاني والديموغرافي',
                        'type' => 'fields',
                        'fields' => ['الجنس (ذكر/أنثى/أخرى)', 'الفئة العمرية', 'الحالة الاجتماعية (عازب/متزوج/منفصل)', 'نوع الجنسية'],
                    ],
                    [
                        'key' => 'eligibility_econ',
                        'title' => 'المعيار الاقتصادي / الاجتماعي',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'eligibility_vuln',
                        'title' => 'معيار الضعف والاستضعاف',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'eligibility_project',
                        'title' => 'المعايير الخاصة بالمشروع',
                        'type' => 'fields',
                        'fields' => ['المعيار الأول', 'المعيار الثاني', 'المعيار الثالث', 'المعيار الرابع', 'المعيار الخامس'],
                    ],
                    [
                        'key' => 'docs',
                        'title' => 'الوثائق والمستندات المطلوبة حسب المشروع',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'priority_criteria',
                        'title' => 'القسم الثالث: معايير الأولوية',
                        'type' => 'table',
                        'columns' => ['معيار التقييم', 'المؤشرات', 'الدرجة القصوى', 'الدرجة الممنوحة'],
                    ],
                    [
                        'key' => 'exclusion',
                        'title' => 'القسم الرابع: معايير الاستبعاد',
                        'type' => 'list',
                    ],
                    [
                        'key' => 'exclusion_notes',
                        'title' => 'ملاحظات إضافية حول الاستبعاد',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'decision_frame',
                        'title' => 'إطار القرار (عتبات القبول والرفض)',
                        'type' => 'table',
                        'columns' => ['نطاق الدرجات', 'القرار', 'ملاحظة / إجراء'],
                    ],
                    [
                        'key' => 'reserve',
                        'title' => 'الحصة القصوى للقائمة الاحتياطية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'comments',
                        'title' => 'القسم الخامس: تعليقات وملاحظات إضافية',
                        'type' => 'paragraph',
                        'assignee_role' => 'مدير المشروع',
                    ],
                ],
            ],
        ],

        // ════════════════════════════════════════════════════════════════════
        // نموذج تقرير المشروع الشهري — Monthly Project Report
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'project-monthly-report',
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
                        'title' => 'أبرز الإنجازات الشهرية',
                        'type' => 'table',
                        'columns' => ['البند', 'ملخص (لا يتجاوز 3 أسطر)'],
                    ],
                    [
                        'key' => 'challenges',
                        'title' => 'أبرز التحديات والدعم المطلوب',
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
                        'title' => '10 - الدروس المستفادة',
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

        // ════════════════════════════════════════════════════════════════════
        // تقرير نهاية المشروع — Final Project Report
        // ════════════════════════════════════════════════════════════════════
        [
            'key' => 'project-final-report',
            'title_ar' => 'تقرير نهاية المشروع',
            'slug' => 'final-project-report',
            'version' => 1,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => [
                    'التقرير النهائي للمشروع — يُعدّه مدير المشروع ويُعتمد من إدارة المشاريع والإدارة التنفيذية.',
                ],
                'sections' => [
                    [
                        'key' => 'project_card',
                        'title' => '1 - بطاقة المشروع',
                        'type' => 'fields',
                        'fields' => ['كود المشروع', 'اسم المشروع', 'مسار المشروع', 'مدير المشروع', 'مناطق التنفيذ', 'مدة المشروع', 'خلفية احتياج المشروع', 'عدد المستفيدين', 'الفئات المستهدفة', 'الميزانية', 'حالة المشروع', 'الهدف العام للمشروع', 'الأهداف الخاصة للمشروع', 'مؤشرات النجاح الرئيسية', 'المخاطر الرئيسية', 'الاستدامة'],
                    ],
                    [
                        'key' => 'summary',
                        'title' => '2 - المشروع في سطور',
                        'type' => 'paragraph',
                        'assignee_role' => 'مدير المشروع',
                    ],
                    [
                        'key' => 'background',
                        'title' => '3 - من الفكرة إلى التنفيذ',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'implementation_map',
                        'title' => '4 - خارطة التنفيذ',
                        'type' => 'table',
                        'columns' => ['المنطقة', 'موقع التنفيذ', 'عدد المستفيدين', 'ملاحظات'],
                    ],
                    [
                        'key' => 'achieved',
                        'title' => '5 - الأنشطة المنفذة والمخرجات المحققة',
                        'type' => 'table',
                        'columns' => ['اسم النشاط', 'المنطقة', 'الشريحة المستهدفة', 'نسبة الإنجاز', 'مخرجات النشاط', 'ملاحظات'],
                    ],
                    [
                        'key' => 'not_achieved',
                        'title' => 'الأنشطة غير المنجزة',
                        'type' => 'table',
                        'columns' => ['اسم النشاط', 'سبب عدم الإنجاز'],
                    ],
                    [
                        'key' => 'indicators',
                        'title' => '6 - بالأرقام والنتائج',
                        'type' => 'table',
                        'columns' => ['المؤشر', 'المستهدف', 'الإنجاز الفعلي', 'نسبة الإنجاز', 'الفرق', 'تفسير الانحراف'],
                    ],
                    [
                        'key' => 'impact',
                        'title' => '7 - الأثر الذي صنعه المشروع',
                        'type' => 'table',
                        'columns' => ['قبل المشروع', 'التدخل', 'التغيير/النتيجة', 'الأثر'],
                    ],
                    [
                        'key' => 'beneficiaries',
                        'title' => '8 - المستفيدون في أرقام',
                        'type' => 'table',
                        'columns' => ['المنطقة', 'الفئة العمرية', 'ذكور', 'إناث', 'ذوي احتياج', 'الإجمالي'],
                    ],
                    [
                        'key' => 'partnerships',
                        'title' => '9 - الشراكات والمنح',
                        'type' => 'table',
                        'columns' => ['الجهة/الشريك', 'نوع الشراكة', 'طبيعة الدعم / المساهمة', 'أبرز النتائج'],
                    ],
                    [
                        'key' => 'logistics',
                        'title' => '10 - جاهزية التنفيذ والدعم اللوجستي',
                        'type' => 'table',
                        'columns' => ['البند', 'ما تم توفيره', 'الحالة عند نهاية المشروع', 'ملاحظات'],
                    ],
                    [
                        'key' => 'it',
                        'title' => '11 - التكنولوجيا والتحول الرقمي',
                        'type' => 'table',
                        'columns' => ['النظام/الأداة/الجهاز', 'الاستخدام', 'الحالة', 'ملاحظات/احتياج'],
                    ],
                    [
                        'key' => 'staff',
                        'title' => '12 - الموارد البشرية والكفاءات',
                        'type' => 'table',
                        'columns' => ['المسمى الوظيفي', 'العدد', 'المكان', 'أبرز المهام'],
                    ],
                    [
                        'key' => 'hr_gaps',
                        'title' => 'أبرز الاحتياجات والفجوات البشرية',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'training',
                        'title' => 'أبرز التدريبات وبناء القدرات',
                        'type' => 'table',
                        'columns' => ['اسم التدريب', 'التوصيف الوظيفي للشخص الذي حضر'],
                    ],
                    [
                        'key' => 'finance',
                        'title' => '13 - الكفاءة المالية للمشروع',
                        'type' => 'table',
                        'columns' => ['البند', 'الموازنة المعتمدة', 'الإنفاق الفعلي', 'الفرق', 'نسبة التنفيذ'],
                    ],
                    [
                        'key' => 'finance_notes',
                        'title' => 'أبرز الانحرافات المالية',
                        'type' => 'fields',
                        'fields' => ['أبرز الانحرافات المالية وأسبابها', 'الرصيد المتبقي من الموازنة'],
                    ],
                    [
                        'key' => 'compliance',
                        'title' => '14 - الامتثال والحوكمة',
                        'type' => 'table',
                        'columns' => ['الملاحظة/الحالة', 'الإجراء المتخذ', 'الحالة', 'ملاحظة'],
                    ],
                    [
                        'key' => 'meal',
                        'title' => '15 - الجودة والتحقق والتعلم MEAL',
                        'type' => 'table',
                        'columns' => ['المؤشر/المجال', 'النتيجة', 'ملاحظات'],
                    ],
                    [
                        'key' => 'stories',
                        'title' => '16 - قصص صنعت فرقاً',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'lessons',
                        'title' => '17 - ماذا تعلمنا',
                        'type' => 'fields',
                        'fields' => ['ما الذي نجح', 'ما الذي لم ينجح', 'ماذا يجب أن نكرر في بداية المشروع القادم', 'ماذا يجب أن نتجنب', 'ما التغيير المقترح مستقبلاً ضمن المشروع'],
                    ],
                    [
                        'key' => 'sustainability',
                        'title' => '18 - كيف نضمن الاستمرار',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'next',
                        'title' => '19 - إلى أين من هنا (التوصيات المستقبلية)',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'closing_decisions',
                        'title' => '20 - القرارات المطلوبة',
                        'type' => 'paragraph',
                    ],
                    [
                        'key' => 'closing',
                        'title' => '21 - إغلاق المشروع',
                        'type' => 'table',
                        'columns' => ['بند الإغلاق', 'الحالة', 'الملاحظات', 'الإجراء المتبقي', 'المسؤول'],
                    ],
                    [
                        'key' => 'annexes',
                        'title' => '22 - الملاحق والأدلة',
                        'type' => 'table',
                        'columns' => ['رقم الملحق', 'اسم الوثيقة/الملف', 'الحالة والسبب'],
                    ],
                ],
            ],
        ],

        // ── END TEMPLATES ──
        ];
    }
}