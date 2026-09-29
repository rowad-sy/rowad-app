@extends('admin.logistics.layouts.master')

@section('title', 'طلبات الشراء')

@section('logistics-content')
<x-page-header :title="'طلبات الشراء'" :description="'إدارة طلبات الشراء والتوريد'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'طلبات الشراء']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.logistics.purchase-requests.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'create')
        <a href="{{ route('admin.logistics.purchase-requests.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة طلب شراء
        </a>
        @endcanPermission
        <a href="{{ route('admin.logistics.export.purchase-requests') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> تصدير
        </a>
        <form method="POST" action="{{ route('admin.logistics.import.purchase-requests') }}" enctype="multipart/form-data" class="d-inline">
            @csrf
            <label class="btn btn-outline-secondary mb-0">
                <i class="bi bi-upload me-1"></i> استيراد
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="d-none" onchange="this.form.submit()">
            </label>
        </form>
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    @foreach ($statuses ?? [] as $statusKey)
                        <option value="{{ $statusKey }}" {{ ($status ?? '') === $statusKey ? 'selected' : '' }}>
                            {{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$statusKey] ?? $statusKey }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers ?? [] as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects ?? [] as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>الوصف</th>
                    <th>عدد البنود</th>
                    <th>السعر الإجمالي</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseRequests ?? [] as $request)
                    <tr>
                        <td><code>#{{ $request->id }}</code></td>
                        <td>{{ Str::limit($request->items->first()?->description ?? $request->specifications, 50) }}</td>
                        <td>{{ $request->items_count ?? $request->items->count() }}</td>
                        <td>{{ number_format($request->total_price, 2) }}</td>
                        <td>{{ $request->center?->name ?? '—' }}</td>
                        <td>{{ $request->project?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusLabel = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$request->status] ?? $request->status;
                                $statusColors = [
                                    'pending' => 'warning text-dark', 'priced' => 'info', 'pm_approved' => 'primary',
                                    'pm2_approved' => 'primary', 'approved' => 'success', 'rejected' => 'danger', 'executed' => 'dark',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$request->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $request->created_at?->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.logistics.purchase-requests.show', $request) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <x-audit-history :model="'App\Models\Admin\Logistics\PurchaseRequest'" :model-id="$request->id" />
                            @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.purchase-requests.destroy', $request) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف طلب الشراء هذا؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا توجد طلبات شراء" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $purchaseRequests->total() ?? 0 }} طلب
        </div>
        <div>
            {{ ($purchaseRequests ?? collect())->links() }}
        </div>
    </div>
</div>
@endsection
