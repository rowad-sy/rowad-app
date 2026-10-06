@extends('admin.layouts.master')

@section('title', 'لوحة مدير المشروع')

@section('content')
<x-page-header title="لوحة مدير المشروع" description="نظرة عامة على الطلاب والخطط والمهام وطلبات الشراء ضمن نطاقك"
               :breadcrumb="[['label' => 'لوحة مدير المشروع']]">
    <x-slot:meta>
        @if ($scopeCenterId || $scopeProjectId || $scopeCohortId)
            <span class="badge-status is-brand mt-2"><i class="bi bi-funnel" aria-hidden="true"></i> {{ trim(collect([
                $scopeCenter?->name ?? null,
                $scopeProject?->name ?? null,
                $scopeCohort?->name ?? null,
            ])->filter()->implode(' — ')) }}</span>
        @endif
    </x-slot:meta>
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-cart me-1" aria-hidden="true"></i> طلبات الشراء
    </a>
</x-page-header>

@if ($awaitingMyEventCards->isNotEmpty() || $awaitingMySignCount > 0)
{{-- تحتاج إجراءً منك: بطاقات فعاليات محالة إليك، وطلبات شراء بانتظار توقيعك (نفس شروط المتحكم) --}}
<section class="dash-section" aria-labelledby="attn-title">
    <h2 class="section-title" id="attn-title">تحتاج إجراءً منك
        <span class="count-pill is-attention">{{ $awaitingMyEventCards->count() + $awaitingMySignCount }}</span>
    </h2>
    @if ($awaitingMySignCount > 0)
        <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="kpi attention-card mb-3">
            <div class="kpi-label"><i class="bi bi-pen" aria-hidden="true"></i> طلبات شراء بانتظار توقيعك</div>
            <div class="kpi-value">{{ $awaitingMySignCount }}</div>
            <div class="kpi-hint">عرض طلبات الشراء</div>
        </a>
    @endif
    @if ($awaitingMyEventCards->isNotEmpty())
    <div class="table-container attention-card">
        <div class="p-3 border-bottom fw-bold">بطاقات فعاليات بانتظار موافقتك</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>اسم الفعالية</th><th>المشروع</th><th>التاريخ</th><th>أنشأها</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($awaitingMyEventCards as $card)
                    <tr>
                        <td class="fw-medium">{{ $card->name }}</td>
                        <td class="text-muted">{{ $card->project?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $card->event_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-muted">{{ $card->creator?->name ?? '—' }}</td>
                        <td><a href="{{ route('admin.event-cards.show', $card) }}" class="btn btn-sm btn-brand"><i class="bi bi-check2-circle me-1"></i>مراجعة</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @endif
</section>
@endif

@include('admin.partials._shortcuts')

{{-- Student / Task / Plan Cards --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">الطلاب والمهام</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="الطلاب" :value="$studentsCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="إجمالي المهام" :value="$tasksCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="مهامي" :value="$myTasksCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="الخطط التدريبية" :value="$trainingPlansCount" />
    </div>
</div>

{{-- Purchase Requests Cycle Cards --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">دورة طلبات الشراء</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="قيد الموافقات" :value="$pendingPricingCount" tone="warning" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="بموجودي للتوقيع" :value="$awaitingMySignCount" tone="info" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="معتمد" :value="$approvedCount" tone="success" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="منفَّذ" :value="$executedCount" />
    </div>
</div>

{{-- Status Distribution --}}
@if ($statusCounts->isNotEmpty())
<x-fold title="توزيع الحالات" icon="bi-bar-chart" :count="count(\App\Models\Admin\Logistics\PurchaseRequest::STATUSES)" open>
    <div class="table-container">
    <div class="p-3">
        @php
            $statusColors = [
                'review' => 'warning', 'approved1' => 'info', 'approved2' => 'primary',
                'approved' => 'success', 'rejected' => 'danger', 'executed' => 'dark',
            ];
        @endphp
        <div class="d-flex flex-wrap gap-2">
            @foreach (\App\Models\Admin\Logistics\PurchaseRequest::STATUSES as $key => $label)
                @if (($statusCounts[$key] ?? 0) > 0)
                    <span class="badge bg-{{ $statusColors[$key] ?? 'secondary' }}">
                        {{ $label }}: {{ $statusCounts[$key] }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
    </div>
</x-fold>
@endif

{{-- Recent Requests --}}
<x-fold title="آخر طلبات الشراء" icon="bi-clock-history" :count="count($recentPurchaseRequests ?? [])">
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
                @forelse ($recentPurchaseRequests ?? [] as $request)
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

{{-- Event Cards --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">بطاقات الفعاليات</h2>
</div>
<div class="table-container mb-4">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-calendar-event me-1"></i> بطاقاتي <span class="badge text-bg-secondary ms-1">{{ $eventCardsCount }}</span></h5>
        @canPermission('App\Models\Admin\EventCard', 'create')
        <a href="{{ route('admin.event-cards.create') }}" class="btn btn-sm btn-brand">
            <i class="bi bi-plus-lg"></i> بطاقة فعالية جديدة
        </a>
        @endcanPermission
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>اسم الفعالية</th>
                    <th>المشروع</th>
                    <th>التاريخ</th>
                    <th>المحال إليه</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentEventCards as $card)
                    <tr>
                        <td class="fw-medium">{{ $card->name }}</td>
                        <td class="text-muted">{{ $card->project?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $card->event_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-muted">{{ $card->referredUser?->name ?? '—' }}</td>
                        <td><span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></td>
                        <td>
                            <a href="{{ route('admin.event-cards.show', $card) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-event fs-3 d-block mb-2"></i>
                            لا توجد بطاقات فعاليات بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


{{-- Recent Activities --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">الأنشطة</h2>
</div>
<div class="table-container">
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
                    <th>المعوقات</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentActivities ?? [] as $activity)
                    <tr>
                        <td>{{ $activity->activity_date?->format('d/m/Y') }}</td>
                        <td>{{ $activity->responsible ?: '—' }}</td>
                        <td>{{ $activity->beneficiary ?: '—' }}</td>
                        <td class="text-nowrap">
                            <span class="badge bg-primary-subtle text-primary">{{ $activity->male_count }} ذكر</span>
                            <span class="badge bg-info-subtle text-info">{{ $activity->female_count }} أنثى</span>
                        </td>
                        <td class="small">{{ Str::limit($activity->progress, 50) ?: '—' }}</td>
                        <td class="small text-muted">{{ Str::limit($activity->obstacles, 40) ?: '—' }}</td>
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
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-stars fs-3 d-block mb-2"></i>
                            لا توجد أنشطة مسجلة بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection