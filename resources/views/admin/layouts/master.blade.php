<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') | مؤسسة الرواد</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <style>
        .sidebar-section .nav-section { cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none; }
        .sidebar-section .nav-section:hover { background: rgba(255,255,255,0.05); }
        .sidebar-section .section-arrow { transition: transform 0.2s; font-size: 0.75rem; }
        .sidebar-section.collapsed .section-arrow { transform: rotate(-90deg); }
        .sidebar-section.collapsed .section-items { display: none; }
        .sidebar.collapsed .sidebar-section .section-arrow { display: none; }
        .sidebar.collapsed .sidebar-section .section-items { display: none; }
        .sidebar.collapsed .sidebar-section .nav-section { cursor: default; }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-building me-2"></i>
            <span class="brand-text">مؤسسة الرواد</span>
        </div>

        {{-- Employee/Admin Navigation --}}
        @if (auth()->user()->is_active)
        @if (auth()->user()->type === 'employee' || auth()->user()->type === 'super-admin')
        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>الرئيسية</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
            <div class="section-items">
                <a href="{{ route('admin.home') }}" class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i> <span>التطبيقات</span>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>لوحة التحكم</span>
                </a>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>الإدارة</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
            <div class="section-items">
                @canPermission('App\Models\Admin\Center', 'view')
                <a href="{{ route('admin.centers.index') }}" class="nav-link {{ request()->routeIs('admin.centers.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> <span>المراكز</span>
                </a>
                @endcanPermission
                @canPermission('App\Models\Admin\Project', 'view')
                <a href="{{ route('admin.projects.index') }}" class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i> <span>المشاريع</span>
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

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>الموارد البشرية</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>اللوجستي</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>الطلاب</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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
            </div>
        </div>

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>إدارة المشاريع</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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
                @canPermission('App\Models\Admin\ProjectDocs\AnnexTemplate', 'view')
                <a href="{{ route('admin.project-docs.templates.index') }}" class="nav-link {{ request()->routeIs('admin.project-docs.templates.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>قوالب الوثائق</span>
                </a>
                @endcanPermission
            </div>
        </div>

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>التقنية</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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

        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>النظام والتدقيق</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
            <div class="section-items">
                @canPermission('App\Models\AuditLog', 'view')
                <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i> <span>سجل التدقيق</span>
                </a>
                @endcanPermission
            </div>
        </div>
        @endif

        {{-- Student Navigation --}}
        @if (auth()->user()->type === 'student')
        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>حسابي</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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
            <div class="nav-section" onclick="toggleSection(this)">
                <span>الرئيسية</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
            <div class="section-items">
                <a href="{{ route('admin.beneficiary.dashboard') }}" class="nav-link {{ request()->routeIs('admin.beneficiary.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>لوحة المستفيد</span>
                </a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>حسابي</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
            <div class="section-items">
                <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> <span>الملف الشخصي</span>
                </a>
            </div>
        </div>
        @endif
        @else
        <div class="sidebar-section">
            <div class="nav-section" onclick="toggleSection(this)">
                <span>حسابي</span>
                <i class="bi bi-chevron-down section-arrow"></i>
            </div>
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
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar-top d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-link text-dark p-0 sidebar-toggle" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <span class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ now()->locale('ar')->translatedFormat('l d F Y') }}
                </span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-left me-2"></i>تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="page-content">
            @if (!auth()->user()->is_active)
                @if (blank(auth()->user()->email_verified_at))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-envelope-check me-1"></i>
                        حسابك غير مفعّل بعد. يرجى الضغط على رابط التفعيل المرسل إلى بريدك الإلكتروني لتفعيل حسابك،
                        وسيُطلب منك تغيير كلمة المرور حتى تستطيع استخدام النظام.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @else
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-octagon me-1"></i>
                        تم إيقاف حسابك من قبل الإدارة. يرجى التواصل مع الإدارة لمعرفة سبب الإيقاف.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')

    <!-- Audit History Modal -->
    <div class="modal fade" id="auditHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>سجل التغييرات</h5>
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
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            toggleBtn.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
            });

            // Auto-open sections with an active link
            document.querySelectorAll('.sidebar-section').forEach(function (section) {
                var hasActive = section.querySelector('.nav-link.active');
                var key = 'sidebar_section_' + section.querySelector('.nav-section span').textContent.trim();
                var stored = localStorage.getItem(key);

                if (hasActive) {
                    section.classList.remove('collapsed');
                    localStorage.setItem(key, 'open');
                } else if (stored === 'collapsed') {
                    section.classList.add('collapsed');
                }
            });
        });

        function toggleSection(el) {
            var section = el.closest('.sidebar-section');
            var isCollapsed = section.classList.toggle('collapsed');
            var key = 'sidebar_section_' + section.querySelector('.nav-section span').textContent.trim();
            localStorage.setItem(key, isCollapsed ? 'collapsed' : 'open');
        }
    </script>
</body>
</html>
