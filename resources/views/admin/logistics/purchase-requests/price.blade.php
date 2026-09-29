@extends('admin.logistics.layouts.master')

@section('title', 'تسعير طلب #' . $purchaseRequest->id)

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: var(--color-surface-muted); border: 1px solid var(--color-border); color: var(--color-text-main); border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: var(--color-text-muted); display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
</style>
@endpush

@section('logistics-content')
<x-page-header :title="'تسعير طلب شراء #' . $purchaseRequest->id"
               :breadcrumb="[['label' => 'طلبات الشراء', 'url' => route('admin.logistics.purchase-requests.index')], ['label' => '#' . $purchaseRequest->id, 'url' => route('admin.logistics.purchase-requests.show', $purchaseRequest)], ['label' => 'تسعير']]" />

<div class="row">
    <div class="col-lg-8">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.logistics.purchase-requests.price', $purchaseRequest) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الميزانية</label>
                    <input type="text" name="budget_number" value="{{ old('budget_number', $purchaseRequest->budget_number) }}"
                           class="form-control @error('budget_number') is-invalid @enderror"
                           maxlength="60" placeholder="مثال: B-2026-0142">
                    @error('budget_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">تسعير البنود <span class="text-danger">*</span></label>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الوصف</th>
                                    <th style="width:10%">الكمية</th>
                                    <th style="width:10%">الوحدة</th>
                                    <th style="width:12%">خط الميزانية</th>
                                    <th style="width:14%">سعر الوحدة</th>
                                    <th style="width:14%">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseRequest->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ $item->description }}
                                            @if ($item->notes)
                                                <small class="text-muted d-block">{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>
                                            <input type="number"
                                                   name="items[{{ $i }}][budget_line]"
                                                   value="{{ old('items.' . $i . '.budget_line', $item->budget_line) }}"
                                                   class="form-control form-control-sm"
                                                   min="0" step="0.01" inputmode="decimal"
                                                   placeholder="خط البند">
                                        </td>
                                        <td>
                                            <input type="number"
                                                   name="items[{{ $i }}][id]"
                                                   value="{{ $item->id }}" hidden>
                                            <input type="number"
                                                   name="items[{{ $i }}][unit_price]"
                                                   value="{{ old('items.' . $i . '.unit_price', $item->unit_price) }}"
                                                   class="form-control form-control-sm unit-price"
                                                   min="0" step="0.01" required
                                                   oninput="calcRow(this)">
                                        </td>
                                        <td class="row-total">{{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="6" class="text-start">الإجمالي الكلي</td>
                                    <td id="grandTotal">{{ number_format($purchaseRequest->total_price, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @error('items') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('items.*.unit_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('items.*.budget_line') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    ستقوم بتحديد الأسعار فقط وسيُحال الطلب تلقائياً إلى
                    <strong>{{ $purchaseRequest->directManager?->name ?? 'المدير المباشر' }}</strong> للتوقيع بعد الحفظ.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ التسعير وإحالة التوقيع
                    </button>
                    <a href="{{ route('admin.logistics.purchase-requests.show', $purchaseRequest) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-receipt me-1"></i> بيانات الطلب</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">المركز</span>
                        <span class="value">{{ $purchaseRequest->center?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المشروع</span>
                        <span class="value">{{ $purchaseRequest->project?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مقدم الطلب</span>
                        <span class="value">{{ $purchaseRequest->user?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">التوقيع القادم</span>
                        <span class="value">{{ $purchaseRequest->directManager?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function calcRow(el) {
        var row = el.closest('tr');
        var qty = parseFloat(row.querySelector('td:nth-child(3)').textContent) || 0;
        var price = parseFloat(el.value) || 0;
        row.querySelector('.row-total').textContent = (qty * price).toFixed(2);
        calcGrandTotal();
    }

    function calcGrandTotal() {
        var total = 0;
        document.querySelectorAll('.unit-price').forEach(function (input) {
            var row = input.closest('tr');
            total += parseFloat(row.querySelector('.row-total').textContent) || 0;
        });
        document.getElementById('grandTotal').textContent = total.toFixed(2);
    }
</script>
@endpush