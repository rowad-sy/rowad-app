@extends('admin.logistics.layouts.master')

@section('title', 'المخازن')

@section('logistics-content')
<x-page-header :title="'المخازن'" :description="'إدارة المخازن والمستودعات'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'المخازن']]">
    <div class="d-flex gap-2">
        @canPermission('App\Models\Admin\Logistics\Warehouse', 'create')
        <a href="{{ route('admin.logistics.warehouses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مخزن
        </a>
        @endcanPermission
        <a href="{{ route('admin.logistics.export.warehouses') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> تصدير
        </a>
        <form method="POST" action="{{ route('admin.logistics.import.warehouses') }}" enctype="multipart/form-data" class="d-inline">
            @csrf
            <label class="btn btn-outline-secondary mb-0">
                <i class="bi bi-upload me-1"></i> استيراد
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="d-none" onchange="this.form.submit()">
            </label>
        </form>
    </div>
</x-page-header>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>المركز</th>
                    <th>الملاحظات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($warehouses ?? [] as $warehouse)
                    <tr>
                        <td class="fw-medium">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->center?->name ?? '—' }}</td>
                        <td>{{ Str::limit($warehouse->notes, 60) ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.logistics.warehouses.items', $warehouse) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-box-seam me-1"></i> عرض المحتويات
                            </a>
                            @canPermission('App\Models\Admin\Logistics\Warehouse', 'edit')
                            <a href="{{ route('admin.logistics.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Logistics\Warehouse'" :model-id="$warehouse->id" />
                            @canPermission('App\Models\Admin\Logistics\Warehouse', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.warehouses.destroy', $warehouse) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المخزن؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="4" icon="bi-inbox" title="لا توجد مخازن" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
