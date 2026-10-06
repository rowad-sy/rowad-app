<?php

namespace App\Support;

/*
 * سجل «مساحة العمل» — الصفحة الموحدة التي يراها كل مستخدم عند دخول لوحة التحكم.
 *
 * كل وحدة عمل تعرّف هنا إعلانياً: عنوانها وأيقونتها وأفعالها (روابط مع صلاحية
 * الطلب لكل فعل). الصفحة تعرض الوحدة فقط إذا كان للمستخدم فعل واحد على الأقل
 * مسموح، وتعرض داخلها الأفعال المسموحة فقط — أي أن التوسيع المستقبلي لأي دور
 * يتم بتعديل الصلاحيات من شاشاتها دون أي تغيير هنا.
 *
 * الصلاحيات تُقرأ عبر PermissionHelper::can بنمطين:
 *   - موديل حقيقي: 'App\Models\Admin\MovementPlan' + view/create/edit
 *   - صلاحية صفحة: 'page:admin.project-manager.dashboard' + view
 */
class WorkspaceRegistry
{
    private const MP = 'App\Models\Admin\MovementPlan';
    private const MEDIA = 'App\Models\Admin\MediaPlan';
    private const DOC = 'App\Models\Admin\ProjectDocs\AnnexDocument';
    private const DOC_TPL = 'App\Models\Admin\ProjectDocs\AnnexTemplate';
    private const UDOC = 'App\Models\Admin\ProjectDocs\UploadedDocument';
    private const MR = 'App\Models\Admin\MonthlyReports\MonthlyReport';
    private const MR_TPL = 'App\Models\Admin\MonthlyReports\MonthlyReportTemplate';
    private const CARD = 'App\Models\Admin\EventCard';
    private const TASK = 'App\Models\Admin\ProjectTask';
    private const PROJECT = 'App\Models\Admin\Project';
    private const PATH = 'App\Models\Admin\ProjectPath';
    private const PR = 'App\Models\Admin\Logistics\PurchaseRequest';
    private const WH = 'App\Models\Admin\Logistics\Warehouse';
    private const ASSET = 'App\Models\Admin\Logistics\Asset';
    private const EMP = 'App\Models\Admin\Hr\Employee';
    private const LEAVE = 'App\Models\Admin\Hr\LeaveRequest';
    private const ATT = 'App\Models\Admin\Hr\EmployeeAttendance';
    private const STU = 'App\Models\Admin\Student\Student';
    private const COURSE = 'App\Models\Admin\Student\Course';
    private const CERT = 'App\Models\Admin\Student\Certificate';
    private const ISSUE = 'App\Models\Admin\Tech\TechIssue';
    private const EQUIP = 'App\Models\Admin\Tech\TechEquipment';
    private const PHYSIO = 'App\Models\Admin\Physiotherapy\PhysioPatient';
    private const AD = 'App\Models\Admin\AdDesignRequest';

    public static function modules(): array
    {
        return [
            self::mod('movement', 'خطط الحركة', 'bi-truck', 'خطط شهرية بعدة حركات — دورة الاعتماد والتوزيع', [
                self::act('جدول خطط الحركة', 'admin.movement-plans.index', self::MP, 'view', 'bi-table'),
                self::act('خطة حركة جديدة', 'admin.movement-plans.create', self::MP, 'create', 'bi-plus-lg'),
                self::act('تصدير إلى Excel', 'admin.movement-plans.export', self::MP, 'view', 'bi-file-earmark-spreadsheet'),
                self::act('طباعة / حفظ PDF', 'admin.movement-plans.print', self::MP, 'view', 'bi-printer'),
                self::act('معلومات ونصائح', 'admin.movement-plans.help', self::MP, 'view', 'bi-question-circle'),
            ]),
            self::mod('media', 'الخطة الإعلامية', 'bi-megaphone', 'خطة فعاليات شهرية وتغطياتها — دورة روادنا حتى النشر', [
                self::act('الخطط الإعلامية', 'admin.media-plans.index', self::MEDIA, 'view', 'bi-table'),
                self::act('خطة جديدة', 'admin.media-plans.create', self::MEDIA, 'create', 'bi-plus-lg'),
                self::act('طلبات التصميم الإعلاني', 'admin.ad-design-requests.index', self::AD, 'view', 'bi-brush'),
                self::act('طلب تصميم جديد', 'admin.ad-design-requests.create', self::AD, 'create', 'bi-plus-lg'),
                self::act('معلومات ونصائح', 'admin.media-plans.help', self::MEDIA, 'view', 'bi-question-circle'),
            ]),
            self::mod('rowaduna', 'روادنا', 'bi-broadcast', 'لوحة قسم روادنا: الإسناد، التغطيات، المونتاج والنشر', [
                self::act('لوحة روادنا', 'admin.rowaduna.dashboard', 'page:admin.rowaduna.dashboard', 'view', 'bi-broadcast'),
            ]),
            self::mod('docs', 'وثائق المشاريع', 'bi-file-earmark-text', 'بطاقات وفكارس ودراسات — قوالب معتمدة ووثائق بتعبئة', [
                self::act('الوثائق', 'admin.project-docs.documents.index', self::DOC, 'view', 'bi-table'),
                self::act('وثيقة جديدة', 'admin.project-docs.documents.create', self::DOC, 'create', 'bi-plus-lg'),
                self::act('قوالب الوثائق', 'admin.project-docs.templates.index', self::DOC_TPL, 'view', 'bi-collection'),
                self::act('أرشيف الوثائق (PDF)', 'admin.documents-archive.index', self::UDOC, 'view', 'bi-file-earmark-pdf'),
                self::act('رفع وثيقة PDF', 'admin.documents-archive.create', self::UDOC, 'create', 'bi-cloud-upload'),
                self::act('معلومات الوثائق', 'admin.project-docs.documents.help', self::DOC, 'view', 'bi-question-circle'),
            ]),
            self::mod('reports', 'التقارير الشهرية', 'bi-calendar-check', 'تقارير المشاريع الشهرية وقوالبها', [
                self::act('جدول التقارير', 'admin.monthly-reports.index', self::MR, 'view', 'bi-table'),
                self::act('تقرير جديد', 'admin.monthly-reports.create', self::MR, 'create', 'bi-plus-lg'),
                self::act('قوالب التقارير', 'admin.monthly-reports.templates.index', self::MR_TPL, 'view', 'bi-collection'),
            ]),
            self::mod('events', 'بطاقات الفعاليات', 'bi-ticket-detailed', 'دورة إنشاء الفعالية من التأسيس حتى الاعتماد', [
                self::act('البطاقات', 'admin.event-cards.index', self::CARD, 'view', 'bi-table'),
                self::act('بطاقة جديدة', 'admin.event-cards.create', self::CARD, 'create', 'bi-plus-lg'),
            ]),
            self::mod('tasks', 'مهام المشاريع', 'bi-list-check', 'المهام والتقويم والإحصائيات', [
                self::act('المهام', 'admin.projects.tasks.index', self::TASK, 'view', 'bi-table'),
                self::act('مهمة جديدة', 'admin.projects.tasks.create', self::TASK, 'create', 'bi-plus-lg'),
                self::act('التقويم', 'admin.projects.calendar', self::TASK, 'view', 'bi-calendar3'),
                self::act('الإحصائيات', 'admin.projects.statistics', self::TASK, 'view', 'bi-bar-chart'),
            ]),
            self::mod('projects', 'المشاريع والمسارات', 'bi-diagram-3', 'المشاريع وشجرة المسارات والخطط العامة', [
                self::act('المشاريع', 'admin.projects.index', self::PROJECT, 'view', 'bi-collection'),
                self::act('شجرة المسارات', 'admin.paths.tree', self::PATH, 'view', 'bi-diagram-2'),
                self::act('إدارة المسارات', 'admin.paths.index', self::PATH, 'view', 'bi-table'),
            ]),
            self::mod('purchase', 'طلبات الشراء والصيانة', 'bi-bag', 'شراء وصيانة بدورة واحدة: ثلاث موافقات موقعة ثم تنفيذ لوجستي', [
                self::act('طلبات الشراء', 'admin.logistics.purchase-requests.index', self::PR, 'view', 'bi-table', ['type' => 'purchase']),
                self::act('طلب شراء جديد', 'admin.logistics.purchase-requests.create', self::PR, 'create', 'bi-plus-lg', ['type' => 'purchase']),
                self::act('طلبات الصيانة', 'admin.logistics.purchase-requests.index', self::PR, 'view', 'bi-tools', ['type' => 'maintenance']),
                self::act('طلب صيانة جديد', 'admin.logistics.purchase-requests.create', self::PR, 'create', 'bi-plus-lg', ['type' => 'maintenance']),
                self::act('معلومات ونصائح', 'admin.logistics.purchase-requests.help', self::PR, 'view', 'bi-question-circle'),
            ]),
            self::mod('logistics', 'المخازن والأصول', 'bi-box-seam', 'المخازن وأصنافها والأصول وقواعد الموافقات', [
                self::act('المخازن', 'admin.logistics.warehouses.index', self::WH, 'view', 'bi-boxes'),
                self::act('الأصول', 'admin.logistics.assets.index', self::ASSET, 'view', 'bi-pc-display'),
            ]),
            self::mod('hr', 'الموارد البشرية', 'bi-people', 'الموظفون والإجازات والدوام', [
                self::act('الموظفون', 'admin.hr.employees.index', self::EMP, 'view', 'bi-person-badge'),
                self::act('طلبات الإجازات', 'admin.hr.leave-requests.index', self::LEAVE, 'view', 'bi-calendar-x'),
                self::act('دوام الموظفين', 'admin.hr.attendances.index', self::ATT, 'view', 'bi-clock'),
            ]),
            self::mod('students', 'الطلاب', 'bi-mortarboard', 'السجلات والمقررات والشهادات', [
                self::act('الطلاب', 'admin.students.index', self::STU, 'view', 'bi-people'),
                self::act('إدارة المقررات', 'admin.students.courses.index', self::COURSE, 'view', 'bi-journal-text'),
                self::act('الشهادات', 'admin.students.certificates.index', self::CERT, 'view', 'bi-award'),
            ]),
            self::mod('tech', 'الدعم التقني', 'bi-gear', 'التذاكر الفنية والمعدات', [
                self::act('التذاكر', 'admin.tech.issues.index', self::ISSUE, 'view', 'bi-life-preserver'),
                self::act('تذكرة جديدة', 'admin.tech.issues.create', self::ISSUE, 'create', 'bi-plus-lg'),
                self::act('المعدات', 'admin.tech.equipment.index', self::EQUIP, 'view', 'bi-hdd'),
            ]),
            self::mod('physio', 'العلاج الفيزيائي', 'bi-heart-pulse', 'المرضى والجلسات والمتابعة', [
                self::act('المرضى', 'admin.physiotherapy.patients.index', self::PHYSIO, 'view', 'bi-person-plus'),
                self::act('متابعة المرضى', 'admin.physiotherapy.followups.index', 'page:admin.physiotherapy.followups.index', 'view', 'bi-clipboard2-pulse'),
                self::act('مرضى النقل', 'admin.physiotherapy.transfers.index', 'page:admin.physiotherapy.transfers.index', 'view', 'bi-arrow-left-right'),
                self::act('الإحصائيات', 'admin.physiotherapy.statistics.index', 'page:admin.physiotherapy.statistics.index', 'view', 'bi-bar-chart'),
            ]),
            self::mod('dashboards', 'اللوحات الإدارية', 'bi-speedometer2', 'لوحات جاهزة حسب دورك في إدارة المشاريع', [
                self::act('لوحة مدير المشروع', 'admin.project-manager.dashboard', 'page:admin.project-manager.dashboard', 'view', 'bi-person-gear'),
                self::act('لوحة مسؤول المشروع', 'admin.project-officer.dashboard', 'page:admin.project-officer.dashboard', 'view', 'bi-person-check'),
                self::act('لوحة مدير المشاريع', 'admin.projects-manager.dashboard', 'page:admin.projects-manager.dashboard', 'view', 'bi-briefcase'),
            ]),
            self::mod('admin', 'إدارة النظام', 'bi-sliders', 'المراكز والمشاريع والمستخدمون والصلاحيات', [
                self::act('المراكز', 'admin.centers.index', 'App\Models\Admin\Center', 'view', 'bi-geo-alt'),
                self::act('المستخدمون', 'admin.users.index', 'App\Models\User', 'view', 'bi-person-lines-fill'),
                self::act('الأفواج', 'admin.cohorts.index', 'App\Models\Admin\Cohort', 'view', 'bi-briefcase'),
                self::act('الإدارات', 'admin.departments.index', 'App\Models\Admin\Department', 'view', 'bi-building'),
                self::act('الأدوار والنطاقات', 'admin.roles.index', 'App\Models\Admin\Group', 'view', 'bi-shield'),
                self::act('المجموعات', 'admin.groups.index', 'App\Models\Admin\Group', 'view', 'bi-people'),
                self::act('شاشة الصلاحيات', 'admin.permissions.index', 'App\Models\Admin\Permission', 'view', 'bi-key'),
            ]),
            self::mod('audit', 'سجل التدقيق', 'bi-shield-check', 'تتبع كل التغييرات في النظام', [
                self::act('السجل', 'admin.audit-logs.index', 'App\Models\AuditLog', 'view', 'bi-clock-history'),
            ]),
        ];
    }

    private static function mod(string $key, string $title, string $icon, string $desc, array $actions): array
    {
        return compact('key', 'title', 'icon', 'desc', 'actions');
    }

    private static function act(string $label, string $route, string $model, string $action, string $icon, array $params = []): array
    {
        return compact('label', 'route', 'model', 'action', 'icon') + (count($params) ? ['params' => $params] : []);
    }
}
