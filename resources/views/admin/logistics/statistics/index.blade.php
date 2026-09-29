@extends('admin.logistics.layouts.master')

@section('title', 'إحصائيات اللوجستيك')

@section('logistics-content')
<x-page-header title="إحصائيات اللوجستيك" description="نظرة عامة على طلبات الشراء" :breadcrumb="[['label' => 'اللوجستيك'], ['label' => 'الإحصائيات']]" />

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-primary">{{ $totalRequests ?? 0 }}</div>
            <small class="text-muted">إجمالي الطلبات</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-warning">{{ $pendingRequests ?? 0 }}</div>
            <small class="text-muted">قيد الانتظار</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-info">{{ $approvedRequests ?? 0 }}</div>
            <small class="text-muted">تمت الموافقة</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-danger">{{ $rejectedRequests ?? 0 }}</div>
            <small class="text-muted">مرفوض</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-success">{{ $executedRequests ?? 0 }}</div>
            <small class="text-muted">منفذ</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-info">{{ number_format($pendingTotalAmount ?? 0, 2) }}</div>
            <small class="text-muted">إجمالي المبالغ المعلقة</small>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="table-container mb-4">
    <x-filter-bar>
            <div class="col-md-4">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers ?? [] as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects ?? [] as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>
</div>

{{-- Recent Requests Table --}}
<div class="table-container">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> آخر الطلبات</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>المواصفات</th>
                    <th>الكمية</th>
                    <th>السعر الإجمالي</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentRequests ?? [] as $request)
                    <tr>
                        <td><code>#{{ $request->id }}</code></td>
                        <td>{{ Str::limit($request->specifications, 50) }}</td>
                        <td>{{ $request->quantity }}</td>
                        <td>{{ number_format($request->total_price, 2) }}</td>
                        <td>{{ $request->center?->name ?? '—' }}</td>
                        <td>{{ $request->project?->name ?? '—' }}</td>
                        <td>
                            @if ($request->status === 'pending')
                                <x-status-badge tone="warning">قيد الانتظار</x-status-badge>
                            @elseif ($request->status === 'approved')
                                <x-status-badge tone="info">تمت الموافقة</x-status-badge>
                            @elseif ($request->status === 'rejected')
                                <x-status-badge tone="danger">مرفوض</x-status-badge>
                            @elseif ($request->status === 'executed')
                                <x-status-badge tone="success">منفذ</x-status-badge>
                            @else
                                <x-status-badge>{{ $request->status }}</x-status-badge>
                            @endif
                        </td>
                        <td>{{ $request->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="8" icon="bi-inbox" title="لا توجد طلبات شراء" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
