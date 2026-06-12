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

        <div class="nav-section">الرئيسية</div>
        <a href="{{ route('admin.home') }}" class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap"></i> <span>التطبيقات</span>
        </a>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> <span>لوحة التحكم</span>
        </a>

        <div class="nav-section">الإدارة</div>
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

        <div class="nav-section">الموارد البشرية</div>
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
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')

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
        });
    </script>
</body>
</html>
