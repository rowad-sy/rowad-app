<?php

namespace App\Support;

/*
 * كتالوج الموديلات الذي تُدار عليه الصلاحيات.
 *
 * يستخدم في:
 *  - شاشة إدارة الصلاحيات (لوحة المصفوفة) لبناء الأعمدة مجمعة بفئاتها.
 *  - حفظ/تحديث الصلاحيات لمطابقة المفتاح الرقمي للموديل في حقول النموذج
 *    (الأسماء الحقيقية للموديلات تحتوي على نقاط وخطوط مائلة تُفسد أسماء الحقول).
 *
 * المفاتيح الرقمية ثابتة ومرتبة حسب الترتيب الوارد هنا، لذا لا بدَّ من الحفاظ
 * على ترتيب الفئات والموديلات داخل القوائم عند الإضافة مستقبلاً (أو تعديل
 * الاختبارات التي تعتمد على هذه المفاتيح).
 */
class PermissionModelCatalog
{
    public static function groups(): array
    {
        return [
            'الإدارة' => [
                'App\Models\Admin\Center' => 'المراكز',
                'App\Models\Admin\Project' => 'المشاريع',
                'App\Models\Admin\Cohort' => 'الأفواج',
                'App\Models\Admin\Department' => 'الإدارات',
                'App\Models\User' => 'المستخدمين',
                'App\Models\Admin\Group' => 'المجموعات',
                'App\Models\Admin\Permission' => 'الصلاحيات',
            ],
            'الموارد البشرية' => [
                'App\Models\Admin\Hr\Employee' => 'الموظفين',
                'App\Models\Admin\Hr\JobPosition' => 'المناصب الوظيفية',
                'App\Models\Admin\Hr\Warning' => 'التنبيهات',
                'App\Models\Admin\Hr\LeaveType' => 'سياسة الإجازات',
                'App\Models\Admin\Hr\LeaveRequest' => 'طلبات الإجازات',
                'App\Models\Admin\Hr\EmployeeAttendance' => 'دوام الموظفين',
            ],
            'الطلاب' => [
                'App\Models\Admin\Student\Student' => 'الطلاب',
                'App\Models\Admin\Student\Course' => 'الدورات',
                'App\Models\Admin\Student\Period' => 'الفترات',
                'App\Models\Admin\Student\StudentEnrollment' => 'التسجيلات',
                'App\Models\Admin\Student\Attendance' => 'الحضور',
                'App\Models\Admin\Student\Certificate' => 'الشهادات',
                'App\Models\Admin\Student\CertificateDesign' => 'تصاميم الشهادات',
            ],
            'التقنية' => [
                'App\Models\Admin\Tech\TechIssue' => 'التذاكر',
                'App\Models\Admin\Tech\TechEquipment' => 'المعدات',
            ],
            'اللوجستي' => [
                'App\Models\Admin\Logistics\PurchaseRequest' => 'طلبات الشراء',
                'App\Models\Admin\Logistics\ApprovalRule' => 'قواعد الموافقات',
                'App\Models\Admin\Logistics\Warehouse' => 'المخازن',
                'App\Models\Admin\Logistics\Asset' => 'الأصول',
                'App\Models\Admin\Logistics\LogisticsSetting' => 'إعدادات اللوجستي',
            ],
            'إدارة المشاريع' => [
                'App\Models\Admin\ProjectTask' => 'المهام',
                'App\Models\Admin\MediaPlan' => 'الخطة الإعلامية',
                'App\Models\Admin\MovementPlan' => 'خطة الحركة',
                'App\Models\Admin\ProjectDocs\AnnexTemplate' => 'قوالب وثائق المشروع',
                'App\Models\Admin\ProjectDocs\AnnexDocument' => 'وثائق المشروع',
                'App\Models\Admin\MonthlyReports\MonthlyReport' => 'التقارير الشهرية',
                'App\Models\Admin\MonthlyReports\MonthlyReportTemplate' => 'قوالب التقارير الشهرية',
                'App\Models\Admin\ProjectActivity' => 'الأنشطة',
                'App\Models\Admin\EventCard' => 'بطاقات الفعاليات',
                'App\Models\Admin\ProjectPath' => 'المسارات',
                'page:admin.project-manager.dashboard' => 'لوحة مدير المشروع',
                'page:admin.project-officer.dashboard' => 'لوحة مسؤول المشروع',
            ],
            'العلاج الفيزيائي' => [
                'App\Models\Admin\Physiotherapy\PhysioRoom' => 'غرف العلاج الفيزيائي',
                'App\Models\Admin\Physiotherapy\PhysioPatient' => 'مرضى العلاج الفيزيائي',
                'App\Models\Admin\Physiotherapy\PhysioSession' => 'جلسات العلاج الفيزيائي',
                'page:admin.physiotherapy.followups.index' => 'متابعة المرضى (حسب المعالج)',
                'page:admin.physiotherapy.transfers.index' => 'مرضى النقل',
                'page:admin.physiotherapy.statistics.index' => 'إحصائيات العلاج الفيزيائي',
            ],
            'النظام والتدقيق' => [
                'App\Models\AuditLog' => 'سجل التدقيق',
            ],
            'الصفحات' => [
                'page:admin.logistics.statistics' => 'إحصائيات اللوجستي',
                'page:admin.projects-manager.dashboard' => 'لوحة مديري المشاريع',
                'App\Models\Admin\Logistics\WarehouseItem' => 'أصناف المخزن',
            ],
        ];
    }

    public static function all(): array
    {
        $items = [];

        foreach (self::groups() as $models) {
            foreach ($models as $model => $label) {
                $items[] = ['model' => $model, 'label' => $label];
            }
        }

        return $items;
    }

    public static function keys(): array
    {
        $keys = [];

        foreach (self::all() as $index => $item) {
            $keys[$item['model']] = $index;
        }

        return $keys;
    }

    public static function keyFor(string $model): ?int
    {
        return self::keys()[$model] ?? null;
    }
}