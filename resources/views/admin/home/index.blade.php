@extends('admin.layouts.master')

@section('title', 'الرئيسية')

@section('content')
<div class="page-header">
    <h4>مؤسسة الرواد للتعاون والتنمية</h4>
    <p>اختر أحد التطبيقات للبدء</p>
</div>

<div class="row g-4">
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">
            <div class="card app-card app-card-admin">
                <div class="card-body text-center">
                    <div class="app-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h6 class="app-title">مسؤول الموقع</h6>
                </div>
            </div>
        </a>
    </div>

    @canPermission('App\Models\Admin\Hr\Employee', 'view')
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="{{ route('admin.hr.employees.index') }}" class="text-decoration-none">
            <div class="card app-card app-card-hr">
                <div class="card-body text-center">
                    <div class="app-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h6 class="app-title">الموارد البشرية</h6>
                </div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Student\Student', 'view')
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="{{ route('admin.students.index') }}" class="text-decoration-none">
            <div class="card app-card app-card-students">
                <div class="card-body text-center">
                    <div class="app-icon">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <h6 class="app-title">الطلاب</h6>
                </div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Tech\TechIssue', 'view')
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="{{ route('admin.tech.issues.index') }}" class="text-decoration-none">
            <div class="card app-card app-card-tech">
                <div class="card-body text-center">
                    <div class="app-icon">
                        <i class="bi bi-gear"></i>
                    </div>
                    <h6 class="app-title">التقنية</h6>
                </div>
            </div>
        </a>
    </div>
    @endcanPermission

    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="#" class="text-decoration-none">
            <div class="card app-card app-card-health">
                <div class="card-body text-center">
                    <div class="app-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h6 class="app-title">الرعاية الصحية</h6>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
