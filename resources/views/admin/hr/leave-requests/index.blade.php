@extends('admin.layouts.master')

@section('title', 'طلبات الإجازات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>طلبات الإجازات</h4>
        <p>إدارة طلبات إجازات الموظفين</p>
    </div>
    @canPermission('App\Models\Admin\Hr\LeaveRequest', 'create')
    <a href="{{ route('admin.hr.leave-requests.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> طلب إجازة
    </a>
    @endcanPermission
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <input type="text" name="search" class="form-control" placeholder="بحث باسم الموظف..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>تمت الموافقة</option>
                    <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <button class="btn btn-outline-secondary w-100" type="submit">
                    <i class="bi bi-search"></i> بحث
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
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
                    <td>
                        @if ($item->status === 'pending')
                            <span class="badge bg-warning text-dark">قيد الانتظار</span>
                        @elseif ($item->status === 'approved')
                            <span class="badge bg-success">تمت الموافقة</span>
                        @elseif ($item->status === 'rejected')
                            <span class="badge bg-danger">مرفوض</span>
                        @endif
                    </td>
                    <td>{{ $item->created_at->format('Y-m-d') }}</td>
                    <td>
                        @if ($item->status === 'pending')
                            @canPermission('App\Models\Admin\Hr\LeaveRequest', 'delete')
                            <form action="{{ route('admin.hr.leave-requests.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الطلب؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcanPermission
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">لا توجد طلبات إجازات</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center p-3">
        <div class="text-muted small">إجمالي النتائج: {{ $requests->total() }}</div>
        {{ $requests->links() }}
    </div>
</div>
@endsection
