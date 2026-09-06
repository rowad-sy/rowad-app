@extends('admin.layouts.master')

@section('title', 'لوحة مدير المشروع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>لوحة مدير المشروع</h4>
        <p>
            نظرة عامة على الطلاب والخطط والمهام وطلبات الشراء ضمن نطاقك
            @if ($scopeCenterId || $scopeProjectId || $scopeCohortId)
                <span class="badge bg-light text-dark border me-1">
                    <i class="bi bi-funnel"></i> {{ trim(collect([
                        $scopeCenter?->name ?? null,
                        $scopeProject?->name ?? null,
                        $scopeCohort?->name ?? null,
                    ])->filter()->implode(' — ')) }}
                </span>
            @endif
        </p>
    </div>
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-cart me-1"></i> طلبات الشراء
    </a>
</div>

@include('admin.partials._shortcuts')

{{-- Student / Task / Plan Cards --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-primary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">الطلاب والمهام</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-primary">
                <i class="bi bi-mortarboard"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $studentsCount }}</div>
            <small class="text-muted">الطلاب</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-info">
                <i class="bi bi-list-task"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $tasksCount }}</div>
            <small class="text-muted">إجمالي المهام</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-success">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $myTasksCount }}</div>
            <small class="text-muted">مهامي</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-secondary">
                <i class="bi bi-calendar-week"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $trainingPlansCount }}</div>
            <small class="text-muted">الخطط التدريبية</small>
        </div>
    </div>
</div>

{{-- Purchase Requests Cycle Cards --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-success" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">دورة طلبات الشراء</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $pendingPricingCount }}</div>
            <small class="text-muted">بانتظار التسعير</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-info">{{ $awaitingMySignCount }}</div>
            <small class="text-muted">بموجودي للتوقيع</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ $approvedCount }}</div>
            <small class="text-muted">معتمد</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-dark">{{ $executedCount }}</div>
            <small class="text-muted">منفَّذ</small>
        </div>
    </div>
</div>

{{-- Status Distribution --}}
@if ($statusCounts->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-bar-chart me-1"></i> توزيع الحالات</h5>
    </div>
    <div class="p-3">
        @php
            $statusColors = [
                'pending' => 'warning', 'priced' => 'info', 'pm_approved' => 'primary',
                'pm2_approved' => 'primary', 'approved' => 'success', 'rejected' => 'danger', 'executed' => 'dark',
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
@endif

{{-- Recent Requests --}}
<div class="table-container">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> آخر طلبات الشراء</h5>
    </div>
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
                            @if ($request->status === 'pending')
                                <span class="badge bg-warning text-dark">{{ $statusLabel }}</span>
                            @elseif ($request->status === 'priced')
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
@endsection