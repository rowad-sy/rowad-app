@extends('admin.layouts.master')

@section('title', 'خطة الحركة')

@section('content')
@php
    $colors = ['review' => 'bg-warning text-dark', 'approved' => 'bg-info', 'assigned' => 'bg-primary', 'completed' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary'];
@endphp

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>خطة الحركة</h4>
        <p>طلبات الحركة بين المراكز: مدير مشروع → إدارة المشاريع → مسؤول الحركة → المتابِعون</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.movement-plans.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\MovementPlan', 'create')
        <a href="{{ route('admin.movement-plans.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> خطة حركة جديدة
        </a>
        @endcanPermission
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Admin\MovementPlan::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)request('center_id') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الحركة</th>
                    <th>التاريخ</th>
                    <th>المسار</th>
                    <th>الغاية</th>
                    <th>المركز</th>
                    <th>الحالة</th>
                    <th>المتابِعون</th>
                    <th>أنشأها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td><code>{{ $plan->request_number }}</code></td>
                        <td>{{ $plan->movement_date->format('Y-m-d') }}</td>
                        <td>
                            @if ($plan->from_location || $plan->to_location)
                                <small>{{ $plan->from_location ?: '—' }} ← {{ $plan->to_location ?: '—' }}</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ Str::limit($plan->purpose, 50) }}</td>
                        <td>{{ $plan->center?->name ?? '—' }}</td>
                        <td><span class="badge {{ $colors[$plan->status] ?? 'bg-secondary' }}">{{ \App\Models\Admin\MovementPlan::STATUSES[$plan->status] ?? $plan->status }}</span></td>
                        <td>
                            <span class="badge {{ $plan->recipients_count > 0 ? 'bg-primary' : 'bg-secondary' }}">{{ $plan->recipients_count }}</span>
                        </td>
                        <td>{{ $plan->creator?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.movement-plans.show', $plan) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                            <x-audit-history :model="'App\Models\Admin\MovementPlan'" :model-id="$plan->id" />
                            @canPermission('App\Models\Admin\MovementPlan', 'delete')
                            <form method="POST" action="{{ route('admin.movement-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>لا توجد خطط حركة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $plans->total() }} خطة</div>
        <div>{{ $plans->links() }}</div>
    </div>
</div>
@endsection