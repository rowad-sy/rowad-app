@extends('admin.layouts.master')

@section('title', $employee->first_name_ar . ' ' . $employee->last_name_ar)

@push('styles')
<style>
    .quick-link-card { transition: all 0.2s; border: 1px solid #e9ecef; cursor: pointer; }
    .quick-link-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-color: #0d6efd; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: #f8f9fa; border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: #6c757d; display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
</style>
@endpush

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ $employee->first_name_ar }} {{ $employee->last_name_ar }}</h4>
        <p class="mb-0">
            <i class="bi bi-person-badge me-1"></i> {{ $employee->employee_code }}
            @if ($employee->status === 'active')
                <span class="badge bg-success ms-2">نشط</span>
            @else
                <span class="badge bg-secondary ms-2">غير نشط</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <x-audit-history :model="'App\Models\Admin\Hr\Employee'" :model-id="$employee->id" />
        @canPermission('App\Models\Admin\Hr\Employee', 'edit')
        <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> تعديل
        </a>
        @endcanPermission
        @canPermission('App\Models\Admin\Hr\Employee', 'view')
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i> الموظفين
        </a>
        @endcanPermission
    </div>
</div>

{{-- Navigation Quick Links --}}
@if ($navLinks->isNotEmpty())
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="bg-primary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
        <h5 class="mb-0 fw-bold">روابط سريعة</h5>
    </div>
    <div class="row g-2">
        @foreach ($navLinks as $link)
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route($link['route']) }}" class="text-decoration-none">
                <div class="card quick-link-card h-100">
                    <div class="card-body text-center py-3">
                        <div class="fs-4 mb-1 text-primary"><i class="bi {{ $link['icon'] }}"></i></div>
                        <small class="text-dark">{{ $link['label'] }}</small>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
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
                        <span class="value"><code>{{ $employee->employee_code }}</code></span>
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
                        <span class="badge bg-success fs-6">مرتبط</span>
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
                                @if ($employee->user->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">موقوف</span>
                                @endif
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
                    $todaySchedule = $employee->workSchedules->where('day_of_week', now()->dayOfWeek)->first();
                @endphp
                @if ($todaySchedule)
                    @if ($todaySchedule->is_day_off)
                        <div class="text-center py-2">
                            <span class="badge bg-info fs-6">إجازة أسبوعية</span>
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
