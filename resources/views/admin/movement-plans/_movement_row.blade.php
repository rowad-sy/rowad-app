@php
    $i = $index ?? 0;
    $en = $en ?? null;
    $dateVal = fn ($v) => $v instanceof \Illuminate\Support\Carbon ? $v->format('Y-m-d') : (string) ($v ?? '');
    $timeVal = fn ($v) => $v ? substr((string) $v, 0, 5) : '';
@endphp
<div class="movement-entry-card border rounded p-3 mb-3 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;left:8px"
            onclick="removeMovementRow(this)" title="حذف البند">
        <i class="bi bi-x-lg"></i>
    </button>

    @if ($en && !empty($en->id))
        <input type="hidden" name="entries[{{ $i }}][id]" value="{{ $en->id }}">
    @endif

    <div class="row g-2">
        <div class="col-md-2">
            <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
            <input type="date" name="entries[{{ $i }}][movement_date]" class="form-control form-control-sm"
                   value="{{ old('entries.' . $i . '.movement_date', $dateVal($en?->movement_date)) }}">
            @error('entries.' . $i . '.movement_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small">من</label>
            <input type="text" name="entries[{{ $i }}][from_location]" class="form-control form-control-sm" placeholder="المركز / المدينة"
                   value="{{ old('entries.' . $i . '.from_location', $en?->from_location ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">إلى</label>
            <input type="text" name="entries[{{ $i }}][to_location]" class="form-control form-control-sm" placeholder="المركز / المدينة"
                   value="{{ old('entries.' . $i . '.to_location', $en?->to_location ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">وقت الانطلاق</label>
            <input type="time" name="entries[{{ $i }}][departure_time]" class="form-control form-control-sm"
                   value="{{ old('entries.' . $i . '.departure_time', $timeVal($en?->departure_time)) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">وقت العودة</label>
            <input type="time" name="entries[{{ $i }}][return_time]" class="form-control form-control-sm"
                   value="{{ old('entries.' . $i . '.return_time', $timeVal($en?->return_time)) }}">
            @error('entries.' . $i . '.return_time') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small">الغاية <span class="text-danger">*</span></label>
            <input type="text" name="entries[{{ $i }}][purpose]" class="form-control form-control-sm"
                   value="{{ old('entries.' . $i . '.purpose', $en?->purpose ?? '') }}">
            @error('entries.' . $i . '.purpose') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mt-2">
        <label class="form-label small">ملاحظات البند</label>
        <input type="text" name="entries[{{ $i }}][notes]" class="form-control form-control-sm"
               value="{{ old('entries.' . $i . '.notes', $en?->notes ?? '') }}">
    </div>
</div>
