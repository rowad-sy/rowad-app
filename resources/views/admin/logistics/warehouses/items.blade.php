@extends('admin.logistics.layouts.master')

@section('title', 'محتوى المخزن - ' . $warehouse->name)

@section('logistics-content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-box-seam me-1"></i> {{ $warehouse->name }}</h4>
        <p>
            <a href="{{ route('admin.logistics.warehouses.index') }}" class="text-decoration-none">المخازن</a>
            / {{ $warehouse->name }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.logistics.warehouses.items.deleted', $warehouse) }}" class="btn btn-outline-warning">
            <i class="bi bi-trash me-1"></i> المواد المحذوفة
        </a>
        <a href="{{ route('admin.logistics.warehouses.items.create', $warehouse) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مادة
        </a>
    </div>
</div>

{{-- Active Items --}}
<div class="table-container">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-box me-1"></i> المواد الحالية</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
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
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>
                            @if ($item->status === 'active')
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.logistics.warehouses.items.edit', [$warehouse, $item]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.logistics.warehouses.items.destroy', [$warehouse, $item]) }}" class="d-inline"
                                  onsubmit="return confirmDelete(event, this)">
                                @csrf
                                @method('DELETE')
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
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد مواد في هذا المخزن
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Deleted Items --}}
@if (($deletedItems ?? collect())->isNotEmpty())
<div class="table-container mt-3">
    <div class="p-3 border-bottom">
        <h5 class="mb-0 text-danger"><i class="bi bi-trash me-1"></i> المواد المحذوفة</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
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
                @foreach ($deletedItems as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ Str::limit($item->description, 60) ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ $item->delete_reason ?? '—' }}</td>
                        <td>{{ $item->deleted_at?->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
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
