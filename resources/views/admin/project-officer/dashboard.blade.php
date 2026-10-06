@extends('admin.layouts.master')

@section('title', 'لوحة مسؤول المشروع')

@section('content')
<x-page-header title="لوحة مسؤول المشروع" description="متابعة الطلاب والحضور وطلبات الشراء الخاصة بك"
               :breadcrumb="[['label' => 'لوحة مسؤول المشروع']]">
    <x-slot:meta>
        @if ($scopeCenterId || $scopeProjectId || $scopeCohortId)
            <span class="badge-status is-brand mt-2"><i class="bi bi-funnel" aria-hidden="true"></i> {{ trim(collect([
                $scopeCenter?->name ?? null,
                $scopeProject?->name ?? null,
                $scopeCohort?->name ?? null,
            ])->filter()->implode(' — ')) }}</span>
        @endif
    </x-slot:meta>
    @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'create')
    <a href="{{ route('admin.logistics.purchase-requests.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> طلب شراء جديد
    </a>
    @endcanPermission
</x-page-header>

@include('admin.partials._shortcuts', ['hideMovement' => true, 'hideCurriculum' => true])

{{-- Student / Attendance Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="طلاب نطاقي" :value="$studentsCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="حضور اليوم" :value="$todayAttendance" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="طلبات الشراء" :value="$myRequestsCount" tone="brand" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="طلبات معتمدة" :value="$myApproved" tone="success" />
    </div>
</div>

{{-- My Purchase Requests Cycle --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">دورة طلبات الشراء الخاصة بي</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="بانتظار الموافقة" :value="$myPendingPricing" tone="warning" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="وافق الأول" :value="$myPriced" tone="info" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="في دورة الموافقة" :value="$myInCycle" tone="brand" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="مرفوض" :value="$myRejected" tone="danger" />
    </div>
</div>

{{-- My Recent Activities --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">أنشطتي</h2>
    <span class="badge bg-secondary">{{ $myActivitiesCount }}</span>
</div>
<div class="table-container mb-4">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-stars me-1"></i> آخر الأنشطة</h5>
        @canPermission('App\Models\Admin\ProjectActivity', 'create')
        <a href="{{ route('admin.project-activities.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> إضافة نشاط
        </a>
        @endcanPermission
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاريخ</th>
                    <th>المسؤول</th>
                    <th>الجهة المستفيدة</th>
                    <th>ذكور/إناث</th>
                    <th>سير النشاط</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($myRecentActivities ?? [] as $activity)
                    <tr>
                        <td>{{ $activity->activity_date?->format('d/m/Y') }}</td>
                        <td>{{ $activity->responsible ?: '—' }}</td>
                        <td>{{ $activity->beneficiary ?: '—' }}</td>
                        <td class="text-nowrap">
                            <span class="badge bg-primary-subtle text-primary">{{ $activity->male_count }} ذكر</span>
                            <span class="badge bg-info-subtle text-info">{{ $activity->female_count }} أنثى</span>
                        </td>
                        <td class="small">{{ Str::limit($activity->progress, 50) ?: '—' }}</td>
                        <td>
                            @canPermission('App\Models\Admin\ProjectActivity', 'edit')
                            <a href="{{ route('admin.project-activities.edit', $activity) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-stars fs-3 d-block mb-2"></i>
                            لا توجد أنشطة مسجلة بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- My Recent Requests --}}
<x-fold title="آخر طلباتي" icon="bi-clock-history" :count="count($myRecentRequests ?? [])">
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الطلب</th>
                    <th>البنود</th>
                    <th>السعر المتوقع</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($myRecentRequests ?? [] as $request)
                    <tr>
                        <td><code>#{{ $request->id }}</code></td>
                        <td>{{ $request->items->take(2)->pluck('description')->join('، ') ?: ($request->specifications ? Str::limit($request->specifications, 50) : '—') }}</td>
                        <td>{{ number_format($request->total_price, 2) }}</td>
                        <td>{{ $request->center?->name ?? '—' }}</td>
                        <td>{{ $request->project?->name ?? '—' }}</td>
                        <td>
                            @php $statusLabel = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$request->status] ?? $request->status; @endphp
                            @if ($request->status === 'review')
                                <span class="badge bg-warning text-dark">{{ $statusLabel }}</span>
                            @elseif ($request->status === 'approved1' || $request->status === 'approved2')
                                <span class="badge bg-info">{{ $statusLabel }}</span>
                            @elseif ($request->status === 'rejected')
                                <span class="badge bg-danger">{{ $statusLabel }}</span>
                            @elseif ($request->status === 'executed')
                                <span class="badge bg-dark">{{ $statusLabel }}</span>
                            @else
                                <span class="badge bg-success">{{ $statusLabel }}</span>
                            @endif
                        </td>
                        <td>{{ $request->created_at?->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.logistics.purchase-requests.show', $request) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد طلبات شراء
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endsection