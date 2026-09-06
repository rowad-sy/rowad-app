@extends('admin.layouts.master')

@section('title', 'لوحة مسؤول المشروع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>لوحة مسؤول المشروع</h4>
        <p>
            متابعة الطلاب والحضور وطلبات الشراء الخاصة بك
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
    <div class="d-flex gap-2">
        @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'create')
        <a href="{{ route('admin.logistics.purchase-requests.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> طلب شراء جديد
        </a>
        @endcanPermission
    </div>
</div>

@include('admin.partials._shortcuts')

{{-- Student / Attendance Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-primary">
                <i class="bi bi-mortarboard"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $studentsCount }}</div>
            <small class="text-muted">طلاب نطاقي</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-success">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $todayAttendance }}</div>
            <small class="text-muted">حضور اليوم</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $myRequestsCount }}</div>
            <small class="text-muted">طلبات الشراء</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ $myApproved }}</div>
            <small class="text-muted">طلبات معتمدة</small>
        </div>
    </div>
</div>

{{-- My Purchase Requests Cycle --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-success" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">دورة طلبات الشراء الخاصة بي</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $myPendingPricing }}</div>
            <small class="text-muted">بانتظار التسعير</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-info">{{ $myPriced }}</div>
            <small class="text-muted">مُسعَّر</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $myInCycle }}</div>
            <small class="text-muted">في دورة الموافقة</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-danger">{{ $myRejected }}</div>
            <small class="text-muted">مرفوض</small>
        </div>
    </div>
</div>

{{-- My Recent Requests --}}
<div class="table-container">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> آخر طلباتي</h5>
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
                @forelse ($myRecentRequests ?? [] as $request)
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