<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'لوحة التحكم') | مؤسسة الرواد</title>
    <link rel="preload" href="{{ asset('fonts/tajawal/tajawal-arabic-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/js/app.js'])
    @stack('styles')
</head>
<body>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
            <span class="brand-text">مؤسسة الرواد</span>
        </div>

        {{-- Employee/Admin Navigation --}}
        @if (auth()->user()->is_active)
        @if (auth()->user()->type === 'employee' || auth()->user()->type === 'super-admin')
        @php
            $canAnyPerm = function (array $perms): bool {
                if (!auth()->check()) {
                    return false;
                }
                foreach ($perms as [$model, $action]) {
                    if (\App\Helpers\PermissionHelper::can(auth()->user(), $model, $action)) {
                        return true;
                    }
                }
                return false;
            };
        @endphp
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">الرئيسية</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                <a href="{{ route('admin.home') }}" class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i> <span>التطبيقات</span>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>لوحة التحكم</span>
                </a>
            </div>
        </div>

        @if ($canAnyPerm([
            ['App\Models\Admin\Center', 'view'],
            ['App\Models\Admin\Project', 'view'],
            ['App\Models\Admin\Cohort', 'view'],
            ['App\Models\Admin\Department', 'view'],
            ['App\Models\Admin\Group', 'view'],
            ['App\Models\Admin\Permission', 'view'],
            ['App\Models\User', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">الإدارة</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Center', 'view')
                <a href="{{ route('admin.centers.index') }}" class="nav-link {{ request()->routeIs('admin.centers.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> <span>المراكز</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Cohort', 'view')
                <a href="{{ route('admin.cohorts.index') }}" class="nav-link {{ request()->routeIs('admin.cohorts.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> <span>الأفواج</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Department', 'view')
                <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>الإدارات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Group', 'view')
                <a href="{{ route('admin.groups.index') }}" class="nav-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> <span>المجموعات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Group', 'view')
                <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i> <span>الأدوار والنطاقات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Permission', 'view')
                <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check"></i> <span>الصلاحيات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\User', 'view')
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> <span>المستخدمين</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\Admin\Hr\Employee', 'view'],
            ['App\Models\Admin\Hr\JobPosition', 'view'],
            ['App\Models\Admin\Hr\LeaveRequest', 'view'],
            ['App\Models\Admin\Hr\LeaveRequest', 'edit'],
            ['App\Models\Admin\Hr\LeaveType', 'view'],
            ['App\Models\Admin\Hr\EmployeeAttendance', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">الموارد البشرية</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Hr\Employee', 'view')
                <a href="{{ route('admin.hr.employees.index') }}" class="nav-link {{ request()->routeIs('admin.hr.employees.*') ? 'active' : '' }}">
                    <i class="bi bi-person-workspace"></i> <span>الموظفين</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\JobPosition', 'view')
                <a href="{{ route('admin.hr.job-positions.index') }}" class="nav-link {{ request()->routeIs('admin.hr.job-positions.*') ? 'active' : '' }}">
                    <i class="bi bi-badge-tm"></i> <span>المناصب الوظيفية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\Employee', 'view')
                <a href="{{ route('admin.hr.employees.statistics') }}" class="nav-link {{ request()->routeIs('admin.hr.employees.statistics') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>إحصائيات الموظفين</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\LeaveRequest', 'view')
                <a href="{{ route('admin.hr.leave-requests.index') }}" class="nav-link {{ request()->routeIs('admin.hr.leave-requests.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i> <span>طلبات الإجازات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\LeaveRequest', 'edit')
                <a href="{{ route('admin.hr.leave-approvals.index') }}" class="nav-link {{ request()->routeIs('admin.hr.leave-approvals.*') ? 'active' : '' }}">
                    <i class="bi bi-check2-square"></i> <span>الموافقات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\LeaveType', 'view')
                <a href="{{ route('admin.hr.leave-policies.index') }}" class="nav-link {{ request()->routeIs('admin.hr.leave-policies.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> <span>سياسات الإجازات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\EmployeeAttendance', 'view')
                <a href="{{ route('admin.hr.attendances.index') }}" class="nav-link {{ request()->routeIs('admin.hr.attendances.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data"></i> <span>الحضور والغياب</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Hr\Employee', 'view')
                <a href="{{ route('admin.hr.timesheets.index') }}" class="nav-link {{ request()->routeIs('admin.hr.timesheets.*') ? 'active' : '' }}">
                    <i class="bi bi-table"></i> <span>التايم شيت</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\Admin\Logistics\PurchaseRequest', 'view'],
            ['App\Models\Admin\Logistics\ApprovalRule', 'view'],
            ['App\Models\Admin\Logistics\Warehouse', 'view'],
            ['App\Models\Admin\Logistics\Asset', 'view'],
            ['App\Models\Admin\Logistics\LogisticsSetting', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">اللوجستي</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'view')
                <a href="{{ route('admin.logistics.statistics') }}" class="nav-link {{ request()->routeIs('admin.logistics.statistics') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>الإحصائيات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'view')
                <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="nav-link {{ request()->routeIs('admin.logistics.purchase-requests.*') && !request()->routeIs('admin.logistics.purchase-requests.show') ? 'active' : '' }}">
                    <i class="bi bi-cart"></i> <span>طلبات الشراء</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'view')
                <a href="{{ route('admin.logistics.approval-rules.index') }}" class="nav-link {{ request()->routeIs('admin.logistics.approval-rules.*') ? 'active' : '' }}">
                    <i class="bi bi-check2-square"></i> <span>قواعد الموافقات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Logistics\Warehouse', 'view')
                <a href="{{ route('admin.logistics.warehouses.index') }}" class="nav-link {{ request()->routeIs('admin.logistics.warehouses.*') ? 'active' : '' }}">
                    <i class="bi bi-shop"></i> <span>المخازن</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Logistics\Asset', 'view')
                <a href="{{ route('admin.logistics.assets.index') }}" class="nav-link {{ request()->routeIs('admin.logistics.assets.*') ? 'active' : '' }}">
                    <i class="bi bi-boxes"></i> <span>الأصول</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Logistics\LogisticsSetting', 'view')
                <a href="{{ route('admin.logistics.settings.index') }}" class="nav-link {{ request()->routeIs('admin.logistics.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> <span>الإعدادات</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\Admin\Student\Student', 'view'],
            ['App\Models\Admin\Student\Course', 'view'],
            ['App\Models\Admin\Student\Period', 'view'],
            ['App\Models\Admin\Student\Attendance', 'view'],
            ['App\Models\Admin\Student\Certificate', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">الطلاب</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Student\Student', 'view')
                <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.index') || request()->routeIs('admin.students.create') || request()->routeIs('admin.students.edit') || request()->routeIs('admin.students.show') || request()->routeIs('admin.students.attendance') || request()->routeIs('admin.students.statistics') ? 'active' : '' }}">
                    <i class="bi bi-mortarboard"></i> <span>الطلاب</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Course', 'view')
                <a href="{{ route('admin.students.courses.index') }}" class="nav-link {{ request()->routeIs('admin.students.courses.*') ? 'active' : '' }}">
                    <i class="bi bi-book"></i> <span>المقررات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Period', 'view')
                <a href="{{ route('admin.students.periods.index') }}" class="nav-link {{ request()->routeIs('admin.students.periods.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-range"></i> <span>الفترات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Course', 'view')
                <a href="{{ route('admin.students.levels.index') }}" class="nav-link {{ request()->routeIs('admin.students.levels.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>المستويات والصفوف</span>
                </a>
                <a href="{{ route('admin.students.training-plans.index') }}" class="nav-link {{ request()->routeIs('admin.students.training-plans.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-week"></i> <span>الخطط التدريبية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Attendance', 'view')
                <a href="{{ route('admin.students.attendance') }}" class="nav-link {{ request()->routeIs('admin.students.attendance') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> <span>الحضور</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Student', 'view')
                <a href="{{ route('admin.students.statistics') }}" class="nav-link {{ request()->routeIs('admin.students.statistics') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>الإحصائيات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Certificate', 'view')
                <a href="{{ route('admin.students.certificates.index') }}" class="nav-link {{ request()->routeIs('admin.students.certificates.*') && !request()->routeIs('admin.students.certificates.designs') && !request()->routeIs('admin.students.certificates.designer.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-check"></i> <span>الشهادات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Certificate', 'view')
                <a href="{{ route('admin.students.certificates.designs') }}" class="nav-link {{ request()->routeIs('admin.students.certificates.designs') || request()->routeIs('admin.students.certificates.designer.*') ? 'active' : '' }}">
                    <i class="bi bi-palette"></i> <span>تصاميم الشهادات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Student\Certificate', 'view')
                <a href="{{ route('admin.students.certificates.signers.index') }}" class="nav-link {{ request()->routeIs('admin.students.certificates.signers.*') || request()->routeIs('admin.students.certificates.signatory-sets.*') ? 'active' : '' }}">
                    <i class="bi bi-person-sign"></i> <span>موقعو الشهادات</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['page:admin.project-manager.dashboard', 'view'],
            ['page:admin.projects-manager.dashboard', 'view'],
            ['page:admin.project-officer.dashboard', 'view'],
            ['App\Models\Admin\ProjectTask', 'view'],
            ['App\Models\Admin\MediaPlan', 'view'],
            ['App\Models\Admin\AdDesignRequest', 'view'],
            ['page:admin.rowaduna.dashboard', 'view'],
            ['App\Models\Admin\MovementPlan', 'view'],
            ['App\Models\Admin\ProjectDocs\AnnexDocument', 'view'],
            ['App\Models\Admin\ProjectDocs\UploadedDocument', 'view'],
            ['App\Models\Admin\MonthlyReports\MonthlyReport', 'view'],
            ['App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'create'],
            ['App\Models\Admin\ProjectActivity', 'view'],
            ['App\Models\Admin\EventCard', 'view'],
            ['App\Models\Admin\ProjectPath', 'view'],
            ['App\Models\Admin\ProjectDocs\AnnexTemplate', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">إدارة المشاريع</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('page:admin.project-manager.dashboard', 'view')
                <a href="{{ route('admin.project-manager.dashboard') }}" class="nav-link {{ request()->routeIs('admin.project-manager.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-person-workspace"></i> <span>لوحة مدير المشروع</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.projects-manager.dashboard', 'view')
                <a href="{{ route('admin.projects-manager.dashboard') }}" class="nav-link {{ request()->routeIs('admin.projects-manager.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3-fill"></i> <span>لوحة مدير المشاريع</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.project-officer.dashboard', 'view')
                <a href="{{ route('admin.project-officer.dashboard') }}" class="nav-link {{ request()->routeIs('admin.project-officer.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> <span>لوحة مسؤول المشروع</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Project', 'view')
                <a href="{{ route('admin.projects.index') }}" class="nav-link {{ request()->routeIs('admin.projects.index') || request()->routeIs('admin.projects.create') || request()->routeIs('admin.projects.edit') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i> <span>المشاريع</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectPath', 'view')
                <a href="{{ route('admin.paths.index') }}" class="nav-link {{ request()->routeIs('admin.paths.index') || request()->routeIs('admin.paths.create') || request()->routeIs('admin.paths.edit') ? 'active' : '' }}">
                    <i class="bi bi-signpost-split"></i> <span>المسارات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectPath', 'view')
                <a href="{{ route('admin.paths.tree') }}" class="nav-link {{ request()->routeIs('admin.paths.tree') ? 'active' : '' }}">
                    <i class="bi bi-diagram-2"></i> <span>شجرة المسارات والمشاريع</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectTask', 'view')
                <a href="{{ route('admin.projects.tasks.index') }}" class="nav-link {{ request()->routeIs('admin.projects.tasks.*') ? 'active' : '' }}">
                    <i class="bi bi-list-task"></i> <span>المهام</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\MediaPlan', 'view')
                <a href="{{ route('admin.media-plans.index') }}" class="nav-link {{ request()->routeIs('admin.media-plans.*') ? 'active' : '' }}">
                    <i class="bi bi-megaphone"></i> <span>الخطة الإعلامية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\AdDesignRequest', 'view')
                <a href="{{ route('admin.ad-design-requests.index') }}" class="nav-link {{ request()->routeIs('admin.ad-design-requests.*') ? 'active' : '' }}">
                    <i class="bi bi-brush"></i> <span>طلبات التصميم الإعلاني</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.rowaduna.dashboard', 'view')
                <a href="{{ route('admin.rowaduna.dashboard') }}" class="nav-link {{ request()->routeIs('admin.rowaduna.*') ? 'active' : '' }}">
                    <i class="bi bi-broadcast"></i> <span>روادنا</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\MovementPlan', 'view')
                <a href="{{ route('admin.movement-plans.index') }}" class="nav-link {{ request()->routeIs('admin.movement-plans.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i> <span>خطة الحركة</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectTask', 'view')
                <a href="{{ route('admin.projects.calendar') }}" class="nav-link {{ request()->routeIs('admin.projects.calendar') ? 'active' : '' }}">
                    <i class="bi bi-calendar3"></i> <span>التقويم الزمني</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectTask', 'view')
                <a href="{{ route('admin.projects.statistics') }}" class="nav-link {{ request()->routeIs('admin.projects.statistics') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>الإحصائيات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
                <a href="{{ route('admin.project-docs.documents.index') }}" class="nav-link {{ request()->routeIs('admin.project-docs.documents.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> <span>وثائق المشروع</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'view')
                <a href="{{ route('admin.documents-archive.index') }}" class="nav-link {{ request()->routeIs('admin.documents-archive.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> <span>أرشيف الوثائق (PDF)</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\MonthlyReports\MonthlyReport', 'view')
                <a href="{{ route('admin.monthly-reports.index') }}" class="nav-link {{ request()->routeIs('admin.monthly-reports.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-month"></i> <span>التقارير الشهرية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'create')
                <a href="{{ route('admin.monthly-reports.templates.index') }}" class="nav-link {{ request()->routeIs('admin.monthly-reports.templates.*') ? 'active' : '' }}">
                    <i class="bi bi-collection"></i> <span>قوالب التقارير الشهرية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectActivity', 'view')
                <a href="{{ route('admin.project-activities.index') }}" class="nav-link {{ request()->routeIs('admin.project-activities.*') ? 'active' : '' }}">
                    <i class="bi bi-stars"></i> <span>الأنشطة</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\EventCard', 'view')
                <a href="{{ route('admin.event-cards.index') }}" class="nav-link {{ request()->routeIs('admin.event-cards.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-event"></i> <span>بطاقات الفعاليات</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\ProjectDocs\AnnexTemplate', 'view')
                <a href="{{ route('admin.project-docs.templates.index') }}" class="nav-link {{ request()->routeIs('admin.project-docs.templates.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>قوالب الوثائق</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\Admin\Physiotherapy\PhysioPatient', 'view'],
            ['App\Models\Admin\Physiotherapy\PhysioRoom', 'view'],
            ['page:admin.physiotherapy.followups.index', 'view'],
            ['page:admin.physiotherapy.transfers.index', 'view'],
            ['page:admin.physiotherapy.statistics.index', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">العلاج الفيزيائي</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'view')
                <a href="{{ route('admin.physiotherapy.patients.index') }}" class="nav-link {{ request()->routeIs('admin.physiotherapy.patients.*') ? 'active' : '' }}">
                    <i class="bi bi-person-wheelchair"></i> <span>المرضى</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Physiotherapy\PhysioRoom', 'view')
                <a href="{{ route('admin.physiotherapy.rooms.index') }}" class="nav-link {{ request()->routeIs('admin.physiotherapy.rooms.*') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i> <span>الغرف</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.physiotherapy.followups.index', 'view')
                <a href="{{ route('admin.physiotherapy.followups.index') }}" class="nav-link {{ request()->routeIs('admin.physiotherapy.followups.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-pulse"></i> <span>متابعة المرضى</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.physiotherapy.transfers.index', 'view')
                <a href="{{ route('admin.physiotherapy.transfers.index') }}" class="nav-link {{ request()->routeIs('admin.physiotherapy.transfers.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i> <span>مرضى النقل</span>
                </a>
                @endcanPermission
                @canPermission('page:admin.physiotherapy.statistics.index', 'view')
                <a href="{{ route('admin.physiotherapy.statistics.index') }}" class="nav-link {{ request()->routeIs('admin.physiotherapy.statistics.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>الإحصائيات</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\Admin\Tech\TechIssue', 'view'],
            ['App\Models\Admin\Tech\TechEquipment', 'view'],
            ['App\Models\User', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">التقنية</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\Admin\Tech\TechIssue', 'view')
                <a href="{{ route('admin.tech.issues.index') }}" class="nav-link {{ request()->routeIs('admin.tech.issues.*') ? 'active' : '' }}">
                    <i class="bi bi-ticket"></i> <span>التذاكر الفنية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Tech\TechEquipment', 'view')
                <a href="{{ route('admin.tech.equipment.index') }}" class="nav-link {{ request()->routeIs('admin.tech.equipment.*') ? 'active' : '' }}">
                    <i class="bi bi-pc-display"></i> <span>المعدات التقنية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Tech\TechIssue', 'view')
                <a href="{{ route('admin.tech.statistics') }}" class="nav-link {{ request()->routeIs('admin.tech.statistics') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> <span>إحصائيات التقنية</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\User', 'view')
                <a href="{{ route('admin.tech.emails') }}" class="nav-link {{ request()->routeIs('admin.tech.emails') ? 'active' : '' }}">
                    <i class="bi bi-envelope"></i> <span>البريد الرسمي</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        @if ($canAnyPerm([
            ['App\Models\AuditLog', 'view'],
        ]))
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">النظام والتدقيق</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                @canPermission('App\Models\AuditLog', 'view')
                <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i> <span>سجل التدقيق</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif
        @endif

        {{-- Student Navigation --}}
        @if (auth()->user()->type === 'student')
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">حسابي</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> <span>الملف الشخصي</span>
                </a>
                @php $stu = auth()->user()->student; @endphp
                @if ($stu)
                <a href="{{ route('admin.students.show', $stu) }}" class="nav-link {{ request()->routeIs('admin.students.show') ? 'active' : '' }}">
                    <i class="bi bi-mortarboard"></i> <span>ملفي الدراسي</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Beneficiary Navigation --}}
        @if (auth()->user()->type === 'beneficiary')
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">الرئيسية</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                <a href="{{ route('admin.beneficiary.dashboard') }}" class="nav-link {{ request()->routeIs('admin.beneficiary.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>لوحة المستفيد</span>
                </a>
            </div>
        </div>
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">حسابي</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> <span>الملف الشخصي</span>
                </a>
            </div>
        </div>
        @endif
        @else
        <div class="sidebar-section">
            <button type="button" class="nav-section" aria-expanded="true" onclick="toggleSection(this)">
                <span class="section-label">حسابي</span>
                <i class="bi bi-chevron-down section-arrow" aria-hidden="true"></i>
            </button>
            <div class="section-items">
                <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> <span>الملف الشخصي</span>
                </a>
                <a href="{{ route('admin.password.change') }}" class="nav-link {{ request()->routeIs('admin.password.change') ? 'active' : '' }}">
                    <i class="bi bi-key"></i> <span>تغيير كلمة المرور</span>
                </a>
            </div>
        </div>
        @endif
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <header class="navbar-top d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 topbar-context">
                <button class="btn btn-light topbar-btn sidebar-toggle" id="sidebarToggle" type="button"
                        aria-controls="sidebar" aria-expanded="false" aria-label="فتح القائمة الجانبية">
                    <i class="bi bi-list fs-5" aria-hidden="true"></i>
                </button>
                <span class="fw-bold text-truncate">@yield('title', 'لوحة التحكم')</span>
                <span class="text-muted small topbar-date d-none d-lg-inline">
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ now()->locale('ar')->translatedFormat('l d F Y') }}
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light topbar-btn theme-toggle-btn" type="button" aria-label="تبديل الوضع الليلي/النهاري" title="تبديل الوضع الليلي/النهاري">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i>
                </button>
                <a href="{{ route('admin.portal') }}" class="btn btn-light topbar-btn text-decoration-none" aria-label="الصفحة الرئيسية" title="الصفحة الرئيسية">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="الحساب: {{ auth()->user()->name }}" style="height: var(--control-height-sm); max-width: 12rem;">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span class="text-truncate d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>الملف الشخصي</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-left me-2" aria-hidden="true"></i>تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            @if (!auth()->user()->is_active)
                @if (blank(auth()->user()->email_verified_at))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-envelope-check me-1"></i>
                        حسابك غير مفعّل بعد. يرجى الضغط على رابط التفعيل المرسل إلى بريدك الإلكتروني لتفعيل حسابك،
                        وسيُطلب منك تغيير كلمة المرور حتى تستطيع استخدام النظام.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                    </div>
                @else
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-octagon me-1"></i>
                        تم إيقاف حسابك من قبل الإدارة. يرجى التواصل مع الإدارة لمعرفة سبب الإيقاف.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                    </div>
                @endif
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('preflight_errors'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="bi bi-list-check me-1"></i> مشاكل في ملف الاستيراد:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach (session('preflight_errors') as $preError)
                            <li>{{ $preError }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                    تعذّر الحفظ: يوجد {{ $errors->count() }} {{ $errors->count() === 1 ? 'خطأ' : 'أخطاء' }} في البيانات المُدخلة، راجع الحقول المحددة.
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')

    <!-- Audit History Modal -->
    <div class="modal fade" id="auditHistoryModal" tabindex="-1" aria-labelledby="auditHistoryTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="auditHistoryTitle"><i class="bi bi-clock-history me-2"></i>سجل التغييرات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="auditHistoryContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadAuditHistory(model, modelId) {
            var modal = new bootstrap.Modal(document.getElementById('auditHistoryModal'));
            var content = document.getElementById('auditHistoryContent');
            content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            modal.show();

            fetch('/admin/audit-logs/history?model=' + encodeURIComponent(model) + '&model_id=' + modelId)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    content.innerHTML = html;
                })
                .catch(function() {
                    content.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل سجل التغييرات</div>';
                });
        }
    </script>

    <script>
        (function () {
            var MOBILE = window.matchMedia('(max-width: 768px)');
            var RAIL_KEY = 'rowad-sidebar-rail';
            var sidebar = document.getElementById('sidebar');
            var toggleBtn = document.getElementById('sidebarToggle');
            var overlay = document.getElementById('sidebarOverlay');
            var lastFocus = null;

            function store(fn) { try { return fn(); } catch (e) { return null; } }

            // العناصر القابلة للتركيز فعليًا: ظاهرة وغير معطلة وبلا tabindex="-1"
            function focusables() {
                return Array.prototype.filter.call(
                    sidebar.querySelectorAll('a[href], button, input, select, textarea, [tabindex]'),
                    function (el) {
                        if (el.disabled || el.getAttribute('tabindex') === '-1') return false;
                        if (!el.getClientRects().length) return false;
                        return window.getComputedStyle(el).visibility !== 'hidden';
                    }
                );
            }

            function isOpenMobile() { return sidebar.classList.contains('mobile-open'); }

            function syncToggle() {
                var expanded = MOBILE.matches ? isOpenMobile() : !sidebar.classList.contains('collapsed');
                toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggleBtn.setAttribute('aria-label', MOBILE.matches
                    ? (expanded ? 'إغلاق القائمة الجانبية' : 'فتح القائمة الجانبية')
                    : (expanded ? 'تصغير القائمة الجانبية' : 'توسيع القائمة الجانبية'));
                // على الجوال تُخفى القائمة المغلقة عن لوحة المفاتيح وقارئات الشاشة
                if (MOBILE.matches && !expanded) { sidebar.setAttribute('inert', ''); } else { sidebar.removeAttribute('inert'); }
                var rail = !MOBILE.matches && sidebar.classList.contains('collapsed');
                sidebar.querySelectorAll('.nav-link').forEach(function (a) {
                    var label = a.querySelector('span');
                    if (!label) return;
                    a.setAttribute('aria-label', label.textContent.trim());
                    if (rail) { a.setAttribute('title', label.textContent.trim()); } else { a.removeAttribute('title'); }
                });
                // في شريط الأيقونات تصبح عناوين الأقسام فواصل بصرية فقط: خارج ترتيب Tab وقارئات الشاشة
                sidebar.querySelectorAll('.nav-section').forEach(function (h) {
                    if (rail) { h.setAttribute('tabindex', '-1'); h.setAttribute('aria-hidden', 'true'); }
                    else { h.removeAttribute('tabindex'); h.removeAttribute('aria-hidden'); }
                });
                sidebar.querySelectorAll('.nav-link.active').forEach(function (a) { a.setAttribute('aria-current', 'page'); });
            }

            function openMobile() {
                lastFocus = document.activeElement;
                sidebar.classList.add('mobile-open');
                overlay.classList.add('show');
                document.body.classList.add('sidebar-locked');
                syncToggle();
                var first = sidebar.querySelector('.nav-link.active, .nav-link');
                if (first) first.focus();
            }

            function closeMobile(restoreFocus) {
                if (!isOpenMobile()) return;
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
                document.body.classList.remove('sidebar-locked');
                syncToggle();
                if (restoreFocus !== false) (lastFocus && lastFocus.focus ? lastFocus : toggleBtn).focus();
            }

            // التفضيل المحفوظ يخص سطح المكتب فقط، ولا يؤثر في قائمة الجوال — الافتراضي بلا تفضيل: مطوية (شريط أيقونات)
            if (!MOBILE.matches && store(function () { return localStorage.getItem(RAIL_KEY); }) !== '0') {
                sidebar.classList.add('collapsed');
            }

            toggleBtn.addEventListener('click', function () {
                if (MOBILE.matches) {
                    isOpenMobile() ? closeMobile() : openMobile();
                } else {
                    var rail = sidebar.classList.toggle('collapsed');
                    store(function () { localStorage.setItem(RAIL_KEY, rail ? '1' : '0'); });
                    syncToggle();
                }
            });

            overlay.addEventListener('click', function () { closeMobile(); });

            document.addEventListener('keydown', function (e) {
                if (!isOpenMobile()) return;
                if (e.key === 'Escape') { e.preventDefault(); closeMobile(); return; }
                if (e.key === 'Tab') {
                    // حصر التركيز داخل القائمة: تُعاد الحسبة عند كل ضغطة لتغطية فتح الأقسام وطيها
                    var f = focusables();
                    if (!f.length) { e.preventDefault(); return; }
                    var first = f[0], last = f[f.length - 1], cur = document.activeElement;
                    var idx = f.indexOf(cur);
                    if (idx === -1) { e.preventDefault(); (e.shiftKey ? last : first).focus(); }
                    else if (e.shiftKey && cur === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && cur === last) { e.preventDefault(); first.focus(); }
                }
            });

            // إغلاق قائمة الجوال عند اختيار رابط، وعند تغيّر حجم الشاشة
            sidebar.addEventListener('click', function (e) { if (e.target.closest('a.nav-link')) closeMobile(false); });
            MOBILE.addEventListener('change', function () {
                closeMobile(false);
                if (!MOBILE.matches) {
                    var pref = store(function () { return localStorage.getItem(RAIL_KEY); }) !== '0';
                    sidebar.classList.toggle('collapsed', pref);
                }
                syncToggle();
            });

            // فتح القسم الحالي تلقائيًا وتمييزه، واستعادة حالة الأقسام المطوية
            sidebar.querySelectorAll('.sidebar-section').forEach(function (section) {
                var head = section.querySelector('.nav-section');
                var key = 'sidebar_section_' + head.querySelector('.section-label').textContent.trim();
                var stored = store(function () { return localStorage.getItem(key); });
                var hasActive = !!section.querySelector('.nav-link.active');
                section.classList.toggle('has-active', hasActive);
                var closed = hasActive ? false : stored === 'collapsed';
                section.classList.toggle('is-closed', closed);
                head.setAttribute('aria-expanded', closed ? 'false' : 'true');
                if (hasActive) store(function () { localStorage.setItem(key, 'open'); });
            });

            syncToggle();
        })();

        function toggleSection(el) {
            var section = el.closest('.sidebar-section');
            var closed = section.classList.toggle('is-closed');
            el.setAttribute('aria-expanded', closed ? 'false' : 'true');
            var key = 'sidebar_section_' + el.querySelector('.section-label').textContent.trim();
            try { localStorage.setItem(key, closed ? 'collapsed' : 'open'); } catch (e) {}
        }
    </script>
</body>
</html>
