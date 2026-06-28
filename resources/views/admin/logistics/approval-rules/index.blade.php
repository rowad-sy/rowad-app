@extends('admin.logistics.layouts.master')

@section('title', 'قواعد الموافقات')

@section('logistics-content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>قواعد الموافقات</h4>
        <p>إدارة قواعد موافقات طلبات الشراء</p>
    </div>
    @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'create')
    <div>
        <a href="{{ route('admin.logistics.approval-rules.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة قاعدة
        </a>
    </div>
    @endcanPermission
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الاسم</th>
                    <th>الحد الأدنى</th>
                    <th>الحد الأعلى</th>
                    <th>الموافقات المطلوبة</th>
                    <th>المعتمدون</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($approvalRules ?? [] as $rule)
                    <tr>
                        <td class="fw-medium">{{ $rule->name }}</td>
                        <td>{{ number_format($rule->min_amount, 2) }}</td>
                        <td>{{ number_format($rule->max_amount, 2) }}</td>
                        <td>{{ $rule->required_approvals }}</td>
                        <td>
                            @foreach ($rule->approvers ?? [] as $approver)
                                <span class="badge bg-info me-1">{{ $approver->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('admin.logistics.approval-rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.approval-rules.destroy', $rule) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف قاعدة الموافقة هذه؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد قواعد موافقات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
