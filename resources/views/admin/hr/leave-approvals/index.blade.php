@extends('admin.layouts.master')

@section('title', 'الموافقات على طلبات الإجازات')

@section('content')
<x-page-header title="الموافقات" description="طلبات الإجازات المستحقة لمراجعتك: وافق عليها أو ارفضها. سجل جميع الطلبات في صفحة «طلبات الإجازات»."
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'الموافقات']]">
    <a href="{{ route('admin.hr.leave-requests.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calendar-check me-1" aria-hidden="true"></i> سجل الطلبات
    </a>
</x-page-header>

<div class="table-container">
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
                <th>السبب</th>
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
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> موافقة
                                </button>
                            </form>
                            <form action="{{ route('admin.hr.leave-approvals.reject', $item) }}" method="POST" onsubmit="return confirm('رفض الطلب؟')">
                                @csrf
                                <button class="btn btn-sm btn-danger" title="رفض">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i> رفض
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="9" icon="bi-check2-all" title="لا توجد طلبات بانتظار مراجعتك" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-center p-3">
        {{ $requests->links() }}
    </div>
</div>
@endsection
