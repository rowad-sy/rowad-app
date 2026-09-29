@extends('admin.layouts.master')

@section('title', 'التطبيقات')

@section('content')
<x-page-header title="التطبيقات" description="اختر التطبيق الذي تريد العمل عليه" :breadcrumb="[['label' => 'التطبيقات']]" />

@php
    // الظهور حسب الصلاحيات كما كان سابقًا؛ الأوصاف نصوص ثابتة لا تعرض أعدادًا أو إشعارات
    $apps = [
        ['title' => 'مسؤول الموقع', 'desc' => 'مؤشرات النظام الأساسي والانتقال إلى أقسامه الإدارية.', 'icon' => 'bi-shield-lock', 'url' => route('admin.dashboard'), 'show' => true],
        ['title' => 'الموارد البشرية', 'desc' => 'الموظفون والإجازات والحضور والتايم شيت.', 'icon' => 'bi-people', 'url' => route('admin.hr.employees.index'), 'show' => \App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Hr\Employee', 'view')],
        ['title' => 'الطلاب', 'desc' => 'الطلاب والمقررات والحضور والشهادات.', 'icon' => 'bi-mortarboard', 'url' => route('admin.students.index'), 'show' => \App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Student\Student', 'view')],
        ['title' => 'مدير المشاريع', 'desc' => 'متابعة المشاريع والمهام وخطط الحركة والوثائق.', 'icon' => 'bi-diagram-3-fill', 'url' => route('admin.projects-manager.dashboard'), 'show' => $canProjectsManager],
        ['title' => 'إدارة المشاريع', 'desc' => 'الطلاب والفعاليات والأنشطة وطلبات الشراء ضمن نطاقك.', 'icon' => 'bi-diagram-3', 'url' => $canProjectManager ? route('admin.project-manager.dashboard') : route('admin.project-officer.dashboard'), 'show' => $canProjectManager || $canProjectOfficer],
        ['title' => 'التقنية', 'desc' => 'التذاكر الفنية والمعدات والبريد الرسمي.', 'icon' => 'bi-gear', 'url' => route('admin.tech.issues.index'), 'show' => \App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Tech\TechIssue', 'view')],
    ];
@endphp

<div class="row g-3">
    @foreach ($apps as $app)
        @if ($app['show'])
        <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
            <a href="{{ $app['url'] }}" class="app-tile">
                <div class="app-tile-head">
                    <span class="app-icon"><i class="bi {{ $app['icon'] }}" aria-hidden="true"></i></span>
                    <i class="bi bi-arrow-left app-go" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="app-title">{{ $app['title'] }}</h2>
                    <p class="app-desc">{{ $app['desc'] }}</p>
                </div>
            </a>
        </div>
        @endif
    @endforeach

    {{-- الرعاية الصحية: لا توجد وجهة مطابقة بمعناها وصلاحياتها، فتُعرض غير متاحة بلا رابط --}}
    <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
        <div class="app-tile app-tile-disabled" aria-disabled="true">
            <div class="app-tile-head">
                <span class="app-icon"><i class="bi bi-heart-pulse" aria-hidden="true"></i></span>
                <x-status-badge>غير متاح حاليًا</x-status-badge>
            </div>
            <div>
                <h2 class="app-title">الرعاية الصحية</h2>
                <p class="app-desc">هذا التطبيق غير مفعّل بعد.</p>
            </div>
        </div>
    </div>
</div>
@endsection
