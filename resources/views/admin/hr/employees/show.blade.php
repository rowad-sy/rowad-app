@extends('admin.layouts.master')

@section('title', $employee->first_name_ar . ' ' . $employee->last_name_ar)

@push('styles')
<style>
    .quick-link-card { border: 1px solid var(--color-border); }
    .quick-link-card:hover { border-color: var(--color-primary); }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: var(--color-surface-muted); border-radius: 6px; min-width: 0; overflow-wrap: anywhere; }
    .info-item .label { font-size: 0.75rem; color: var(--color-text-muted); display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
</style>
@endpush

@section('content')
<x-page-header :title="$employee->first_name_ar . ' ' . $employee->last_name_ar"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'الموظفين', 'url' => route('admin.hr.employees.index')], ['label' => $employee->first_name_ar . ' ' . $employee->last_name_ar]]">
    <x-slot:meta>
        {{-- ملخص الهوية والحالة والمعلومات الرئيسية (بيانات موجودة في الملف نفسه) --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <x-status-badge><i class="bi bi-person-badge" aria-hidden="true"></i> <span class="ltr-cell d-inline-block">{{ $employee->employee_code }}</span></x-status-badge>
            <x-status-badge :tone="$employee->status === 'active' ? 'success' : 'neutral'">{{ $employee->status === 'active' ? 'نشط' : 'غير نشط' }}</x-status-badge>
            @if ($position?->title_ar)<x-status-badge tone="brand">{{ $position->title_ar }}</x-status-badge>@endif
            @if ($employee->center)<x-status-badge><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $employee->center->name }}</x-status-badge>@endif
            @if ($employee->department)<x-status-badge><i class="bi bi-diagram-3" aria-hidden="true"></i> {{ $employee->department->name_ar }}</x-status-badge>@endif
            @if ($employee->project)<x-status-badge><i class="bi bi-briefcase" aria-hidden="true"></i> {{ $employee->project->name }}</x-status-badge>@endif
        </div>
    </x-slot:meta>
    @canPermission('App\Models\Admin\Hr\Employee', 'edit')
    <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-primary">
        <i class="bi bi-pencil me-1" aria-hidden="true"></i> تعديل
    </a>
    @endcanPermission
    <x-audit-history :model="'App\Models\Admin\Hr\Employee'" :model-id="$employee->id" />
    @canPermission('App\Models\Admin\Hr\Employee', 'view')
    <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1" aria-hidden="true"></i> قائمة الموظفين
    </a>
    @endcanPermission
</x-page-header>

{{-- Navigation Quick Links --}}
@if ($navLinks->isNotEmpty())
<x-fold title="روابط سريعة" icon="bi-lightning" :count="$navLinks->count()">
    <div class="p-3">
    <div class="row g-2">
        @foreach ($navLinks as $link)
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route($link['route']) }}" class="text-decoration-none">
                <div class="card quick-link-card h-100">
                    <div class="card-body text-center py-3">
                        <div class="fs-4 mb-1 text-primary"><i class="bi {{ $link['icon'] }}"></i></div>
                        <small>{{ $link['label'] }}</small>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    </div>
</x-fold>
@endif

<div class="row g-3">
    {{-- Employee Info --}}
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-person me-1"></i> البيانات الشخصية</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">كود الموظف</span>
                        <span class="value ltr-cell d-inline-block">{{ $employee->employee_code }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الاسم AR</span>
                        <span class="value">{{ $employee->first_name_ar }} {{ $employee->last_name_ar }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الاسم EN</span>
                        <span class="value">{{ $employee->first_name_en && $employee->last_name_en ? $employee->first_name_en . ' ' . $employee->last_name_en : '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">اسم الأب</span>
                        <span class="value">{{ $employee->father_name_ar ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">اسم الأم</span>
                        <span class="value">{{ $employee->mother_name_ar ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">رقم الهوية</span>
                        <span class="value">{{ $employee->id_number ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الجنس</span>
                        <span class="value">{{ $employee->gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الحالة الاجتماعية</span>
                        <span class="value">{{ $employee->marital_status === 'single' ? 'أعزب' : ($employee->marital_status === 'married' ? 'متزوج' : ($employee->marital_status === 'divorced' ? 'مطلق' : ($employee->marital_status === 'widowed' ? 'أرمل' : '—'))) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">عدد الأطفال</span>
                        <span class="value">{{ $employee->children_count ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">تاريخ الميلاد</span>
                        <span class="value">{{ $employee->birth_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مكان الميلاد</span>
                        <span class="value">{{ $employee->birth_place ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الجنسية</span>
                        <span class="value">{{ $employee->nationality ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Work Info --}}
        <div class="table-container mt-3">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-briefcase me-1"></i> معلومات العمل</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">المركز</span>
                        <span class="value">{{ $employee->center?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الإدارة</span>
                        <span class="value">{{ $employee->department?->name_ar ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المشروع</span>
                        <span class="value">{{ $employee->project?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المنصب</span>
                        <span class="value">{{ $position?->title_ar ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- User Account & Summary --}}
    <div class="col-lg-4">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-person-badge me-1"></i> حساب المستخدم</h5>
            </div>
            <div class="p-3">
                @if ($employee->user)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <x-status-badge tone="success">مرتبط</x-status-badge>
                    </div>
                    <div class="info-grid" style="grid-template-columns:1fr;">
                        <div class="info-item">
                            <span class="label">الاسم</span>
                            <span class="value">{{ $employee->user->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">البريد</span>
                            <span class="value" dir="ltr">{{ $employee->user->email }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">الحالة</span>
                            <span class="value">
                                <x-status-badge :tone="$employee->user->is_active ? 'success' : 'neutral'">{{ $employee->user->is_active ? 'نشط' : 'موقوف' }}</x-status-badge>
                            </span>
                        </div>
                    </div>
                    @canPermission('App\Models\User', 'edit')
                    <a href="{{ route('admin.users.edit', $employee->user) }}" class="btn btn-sm btn-outline-primary mt-2 w-100">
                        <i class="bi bi-pencil me-1"></i> إدارة حساب المستخدم
                    </a>
                    @endcanPermission
                @else
                    <p class="text-muted small mb-0">لا يوجد حساب مستخدم مرتبط.</p>
                @endif
            </div>
        </div>

        {{-- Attendance Today Summary --}}
        <div class="table-container mt-3">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-clock me-1"></i> مواعيد العمل اليوم</h5>
            </div>
            <div class="p-3">
                @php
                    $todaySchedule = $employee->workSchedules->where('day_of_week', \App\Models\Admin\Hr\WorkSchedule::indexForDate(now()))->first();
                @endphp
                @if ($todaySchedule)
                    @if ($todaySchedule->is_day_off)
                        <div class="text-center py-2">
                            <x-status-badge tone="info">إجازة أسبوعية</x-status-badge>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="fs-5 fw-bold text-primary">{{ substr($todaySchedule->start_time, 0, 5) }} - {{ substr($todaySchedule->end_time, 0, 5) }}</div>
                            <small class="text-muted">ساعات العمل ليوم {{ now()->locale('ar')->dayName }}</small>
                        </div>
                    @endif
                @else
                    <p class="text-muted small mb-0 text-center">لا يوجد جدول عمل محدد لهذا اليوم.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
