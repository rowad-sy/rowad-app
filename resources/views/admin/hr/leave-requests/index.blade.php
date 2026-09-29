@extends('admin.layouts.master')

@section('title', 'طلبات الإجازات')

@section('content')
<x-page-header :title="'طلبات الإجازات'" :description="'إدارة طلبات إجازات الموظفين'"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'طلبات الإجازات']]">
    @canPermission('App\Models\Admin\Hr\LeaveRequest', 'create')
    <a href="{{ route('admin.hr.leave-requests.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> طلب إجازة
    </a>
    @endcanPermission
    @canPermission('App\Models\Admin\Hr\LeaveRequest', 'edit')
    {{-- الطلبات المستحقة لمراجعتك في صفحة مستقلة، وهذه الصفحة سجل عام بالطلبات --}}
    <a href="{{ route('admin.hr.leave-approvals.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-check2-square me-1" aria-hidden="true"></i> الطلبات المستحقة لمراجعتي
    </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <label class="form-label">بحث</label>
                <input type="text" name="search" class="form-control" placeholder="بحث باسم الموظف..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>تمت الموافقة</option>
                    <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>نوع الإجازة</th>
                <th>من</th>
                <th>إلى</th>
                <th>الأيام</th>
                <th>الحالة</th>
                <th>تاريخ الطلب</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $item)
                <tr>
                    <td class="num">{{ $item->id }}</td>
                    <td>{{ $item->employee?->first_name_ar }} {{ $item->employee?->last_name_ar }}</td>
                    <td>
                        <span class="badge" style="background:{{ $item->leaveType?->color ?? '#6c757d' }}">
                            <i class="bi {{ $item->leaveType?->icon ?? 'bi-calendar' }} me-1"></i>
                            {{ $item->leaveType?->name_ar ?? '—' }}
                        </span>
                    </td>
                    <td>{{ $item->start_date->format('Y-m-d') }}</td>
                    <td>{{ $item->end_date->format('Y-m-d') }}</td>
                    <td class="fw-bold">{{ $item->days_count }}</td>
                    <td>
                        @if ($item->status === 'pending')
                            <x-status-badge tone="warning">قيد الانتظار</x-status-badge>
                        @elseif ($item->status === 'approved')
                            <x-status-badge tone="success">تمت الموافقة</x-status-badge>
                        @elseif ($item->status === 'rejected')
                            <x-status-badge tone="danger">مرفوض</x-status-badge>
                        @endif
                    </td>
                    <td>{{ $item->created_at->format('Y-m-d') }}</td>
                    <td>
                        <x-audit-history :model="'App\Models\Admin\Hr\LeaveRequest'" :model-id="$item->id" />
                        @if ($item->status === 'pending')
                            @canPermission('App\Models\Admin\Hr\LeaveRequest', 'delete')
                            <form action="{{ route('admin.hr.leave-requests.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الطلب؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف" aria-label="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="9" icon="bi-calendar-check" title="لا توجد طلبات إجازات" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-center p-3">
        <div class="text-muted small">إجمالي النتائج: {{ $requests->total() }}</div>
        {{ $requests->links() }}
    </div>
</div>
@endsection
