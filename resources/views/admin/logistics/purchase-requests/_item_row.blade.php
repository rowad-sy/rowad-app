@php
    $i = $index ?? 0;
    $val = function (string $key, $default = '') use ($i, $item) {
        $current = is_array($item) ? ($item[$key] ?? $default) : ($item?->{$key} ?? $default);
        return old('items.'.$i.'.'.$key, $current);
    };
    $rowTotal = (float) ($val('quantity', 0) ?: 0) * (float) ($val('unit_price', 0) ?: 0);
@endphp
<div class="pr-item-card border rounded p-3 mb-3 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;left:8px"
            onclick="removePrItemRow(this)" title="حذف البند">
        <i class="bi bi-x-lg"></i>
    </button>

    @if (!empty($item) && !empty($val('id')))
        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $val('id') }}">
    @endif

    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small">المنتج / ITEM <span class="text-danger">*</span></label>
            <input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm @error('items.'.$i.'.description') is-invalid @enderror" required
                   value="{{ $val('description') }}" placeholder="اسم الصنف ووصفه">
            @error('items.'.$i.'.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small">الكمية <span class="text-danger">*</span></label>
            <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm @error('items.'.$i.'.quantity') is-invalid @enderror" data-qty min="1" step="1" required
                   value="{{ $val('quantity', 1) }}">
            @error('items.'.$i.'.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small">الوحدة <span class="text-danger">*</span></label>
            <select name="items[{{ $i }}][unit]" class="form-select form-select-sm @error('items.'.$i.'.unit') is-invalid @enderror" required>
                <option value="">— اختر —</option>
                @foreach (\App\Models\Admin\Logistics\PurchaseRequestItem::UNITS as $u)
                    <option value="{{ $u }}" {{ $val('unit') === $u ? 'selected' : '' }}>{{ $u }}</option>
                @endforeach
            </select>
            @error('items.'.$i.'.unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small">العملة <span class="text-danger">*</span></label>
            <select name="items[{{ $i }}][currency]" class="form-select form-select-sm" data-currency required>
                <option value="USD" {{ $val('currency', 'USD') === 'USD' ? 'selected' : '' }}>$ دولار</option>
                <option value="SYP" {{ $val('currency', 'USD') === 'SYP' ? 'selected' : '' }}>SYP ليرة</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">تكلفة الوحدة <span class="text-danger">*</span></label>
            <input type="number" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm @error('items.'.$i.'.unit_price') is-invalid @enderror" data-price dir="ltr" min="0" step="0.01" required
                   value="{{ $val('unit_price', '') }}">
            @error('items.'.$i.'.unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-md-4">
            <label class="form-label small">التكلفة الإجمالية (تلقائي)</label>
            <input type="text" class="form-control form-control-sm" data-rowtotal dir="ltr" readonly value="{{ number_format($rowTotal, 2, '.', '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">خط الميزانية / Budget Line <span class="text-danger">*</span></label>
            <input type="text" name="items[{{ $i }}][budget_line]" class="form-control form-control-sm @error('items.'.$i.'.budget_line') is-invalid @enderror" dir="ltr"
                   value="{{ $val('budget_line', '') }}" placeholder="مثال: 3.1.29" required>
            @error('items.'.$i.'.budget_line') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small">ملاحظات البند <span class="text-danger">*</span></label>
            <input type="text" name="items[{{ $i }}][notes]" class="form-control form-control-sm @error('items.'.$i.'.notes') is-invalid @enderror"
                   value="{{ $val('notes') }}" required>
            @error('items.'.$i.'.notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
