@extends('admin.layouts.master')

@section('title', 'الموافقات على طلبات الإجازات')

@section('content')
<div class="page-header">
    <h4>الموافقات</h4>
    <p>الموافقة على طلبات الإجازات أو رفضها</p>
</div>

<div class="table-container">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>نوع الإجازة</th>
                <th>من</th>
                <th>إلى</th>
                <th>الأيام</th>
                <th>السبب</th>
                <th>تاريخ الطلب</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $item)
                <tr>
                    <td>{{ $item->id }}</td>
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
                    <td class="text-muted" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $item->reason ?? '—' }}
                    </td>
                    <td>{{ $item->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <x-audit-history :model="'App\Models\Admin\Hr\LeaveRequest'" :model-id="$item->id" />
                            <form action="{{ route('admin.hr.leave-approvals.approve', $item) }}" method="POST" onsubmit="return confirm('الموافقة على الطلب؟')">
                                @csrf
                                <button class="btn btn-sm btn-success" title="موافقة">
                                    <i class="bi bi-check-lg"></i> موافقة
                                </button>
                            </form>
                            <form action="{{ route('admin.hr.leave-approvals.reject', $item) }}" method="POST" onsubmit="return confirm('رفض الطلب؟')">
                                @csrf
                                <button class="btn btn-sm btn-danger" title="رفض">
                                    <i class="bi bi-x-lg"></i> رفض
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">لا توجد طلبات بانتظار الموافقة</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center p-3">
        {{ $requests->links() }}
    </div>
</div>
@endsection
