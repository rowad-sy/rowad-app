@extends('admin.layouts.master')

@section('title', 'خطة الحركة')

@section('content')
@php
    $colors = ['review' => 'bg-warning text-dark', 'approved' => 'bg-info', 'assigned' => 'bg-primary', 'completed' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary'];
@endphp

<x-page-header :title="'خطة الحركة'" :description="'طلبات الحركة بين المراكز: مدير مشروع → إدارة المشاريع → مسؤول الحركة → المتابِعون'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'خطة الحركة']]">
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
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            @if (request('project_id'))
                <input type="hidden" name="project_id" value="{{ request('project_id') }}">
            @endif
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Admin\MovementPlan::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)request('center_id') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                            <a href="{{ route('admin.movement-plans.show', $plan) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <x-audit-history :model="'App\Models\Admin\MovementPlan'" :model-id="$plan->id" />
                            @canPermission('App\Models\Admin\MovementPlan', 'delete')
                            <form method="POST" action="{{ route('admin.movement-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا توجد خطط حركة" />
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