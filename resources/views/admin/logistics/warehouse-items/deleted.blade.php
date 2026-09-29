@extends('admin.logistics.layouts.master')

@section('title', 'المواد المحذوفة - ' . $warehouse->name)

@section('logistics-content')
<x-page-header title="المواد المحذوفة" :description="$warehouse->name"
               :breadcrumb="[['label' => 'المخازن', 'url' => route('admin.logistics.warehouses.index')], ['label' => $warehouse->name, 'url' => route('admin.logistics.warehouses.items.index', $warehouse)], ['label' => 'المواد المحذوفة']]">
    <a href="{{ route('admin.logistics.warehouses.items.index', $warehouse) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة للمخزن</a>
</x-page-header>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الوصف</th>
                    <th>الكمية</th>
                    <th>الوحدة</th>
                    <th>سبب الحذف</th>
                    <th>تاريخ الحذف</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deletedItems as $item)
                    <tr>
                        <td class="fw-medium">{{ $item->item_name }}</td>
                        <td>{{ $item->description ? Str::limit($item->description, 60) : '—' }}</td>
                        <td dir="ltr" class="text-end">{{ $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ $item->delete_reason ?? '—' }}</td>
                        <td dir="ltr" class="text-end">{{ $item->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" icon="bi-trash" title="لا توجد مواد محذوفة في هذا المخزن" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deletedItems->links() }}</div>
@endsection
