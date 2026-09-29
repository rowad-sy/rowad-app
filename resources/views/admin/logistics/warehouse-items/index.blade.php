@extends('admin.logistics.layouts.master')

@section('title', 'محتوى المخزن - ' . $warehouse->name)

@section('logistics-content')
<x-page-header :title="$warehouse->name" :breadcrumb="[['label' => 'المخازن', 'url' => route('admin.logistics.warehouses.index')], ['label' => $warehouse->name]]">
    <a href="{{ route('admin.logistics.warehouses.items.create', $warehouse) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> إضافة مادة</a>
    <a href="{{ route('admin.logistics.warehouses.items.deleted', $warehouse) }}" class="btn btn-outline-warning"><i class="bi bi-trash me-1"></i> المواد المحذوفة</a>
</x-page-header>

{{-- Active Items --}}
<div class="table-container">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-box me-1"></i> المواد الحالية</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الوصف</th>
                    <th>الكمية</th>
                    <th>الوحدة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items ?? [] as $item)
                    <tr>
                        <td class="fw-medium">{{ $item->name }}</td>
                        <td>{{ Str::limit($item->description, 60) ?? '—' }}</td>
                        <td dir="ltr" class="text-end">{{ $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>
                            @if ($item->status === 'active')
                                <x-status-badge tone="success">نشط</x-status-badge>
                            @else
                                <x-status-badge>غير نشط</x-status-badge>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.logistics.warehouses.items.edit', [$warehouse, $item]) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            <form method="POST" action="{{ route('admin.logistics.warehouses.items.destroy', [$warehouse, $item]) }}" class="d-inline"
                                  onsubmit="return confirmDelete(event, this)">
                                @csrf
                                <div class="modal fade" id="deleteModal_{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">سبب الحذف</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <textarea name="delete_reason" class="form-control" rows="3" placeholder="أدخل سبب الحذف..." required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-danger">حذف</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف" data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" icon="bi-inbox" title="لا توجد مواد في هذا المخزن" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(event, form) {
    event.preventDefault();
    var reason = form.querySelector('[name="delete_reason"]');
    if (reason && reason.value.trim() === '') {
        alert('يرجى إدخال سبب الحذف');
        return false;
    }
    return confirm('هل أنت متأكد من حذف هذه المادة؟');
}
</script>
@endpush
