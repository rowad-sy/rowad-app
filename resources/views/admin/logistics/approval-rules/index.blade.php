@extends('admin.logistics.layouts.master')

@section('title', 'قواعد الموافقات')

@section('logistics-content')
<x-page-header :title="'قواعد الموافقات'" :description="'إدارة قواعد موافقات طلبات الشراء'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'قواعد الموافقات']]">
    @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'create')
    <div>
        <a href="{{ route('admin.logistics.approval-rules.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة قاعدة
        </a>
    </div>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                                <x-status-badge tone="info">{{ $approver->name }}</x-status-badge>
                            @endforeach
                        </td>
                        <td>
                            @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'edit')
                            <a href="{{ route('admin.logistics.approval-rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Logistics\ApprovalRule'" :model-id="$rule->id" />
                            @canPermission('App\Models\Admin\Logistics\ApprovalRule', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.approval-rules.destroy', $rule) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف قاعدة الموافقة هذه؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" icon="bi-inbox" title="لا توجد قواعد موافقات" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
