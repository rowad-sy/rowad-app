@php
    $i = $index ?? 0;
    $ev = $ev ?? null;
@endphp
<div class="event-card border rounded p-3 mb-3 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;left:8px"
            onclick="removeEventRow(this)" title="حذف الفعالية">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="row g-2">
        <div class="col-md-2">
            <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
            <input type="date" name="events[{{ $i }}][event_date]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.event_date', $ev?->event_date?->format('Y-m-d') ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">المكتب</label>
            <input type="text" name="events[{{ $i }}][office]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.office', $ev?->office ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">اسم الفعالية <span class="text-danger">*</span></label>
            <input type="text" name="events[{{ $i }}][event_name]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.event_name', $ev?->event_name ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">اليوم</label>
            <input type="text" name="events[{{ $i }}][day]" class="form-control form-control-sm"
                   placeholder="الإثنين"
                   value="{{ old('events.' . $i . '.day', $ev?->day ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">الساعة <span class="text-danger">*</span></label>
            <input type="time" name="events[{{ $i }}][event_time]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.event_time', $ev ? substr((string) $ev->event_time, 0, 5) : '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small">موقع الفعالية</label>
            <input type="text" name="events[{{ $i }}][location]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.location', $ev?->location ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small">المسؤول عن الفعالية</label>
            <select name="events[{{ $i }}][responsible_user_id]" class="form-select form-select-sm">
                <option value="">— اختر —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ old('events.' . $i . '.responsible_user_id', $ev?->responsible_user_id ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">ملخص الفعالية</label>
            <input type="text" name="events[{{ $i }}][summary]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.summary', $ev?->summary ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small">نوع التغطية المطلوبة</label>
            <input type="text" name="events[{{ $i }}][coverage_type]" class="form-control form-control-sm"
                   placeholder="تصوير/تقرير/بث"
                   value="{{ old('events.' . $i . '.coverage_type', $ev?->coverage_type ?? '') }}">
        </div>
    </div>
    <div class="mt-2">
        <label class="form-label small">ملاحظات</label>
        <input type="text" name="events[{{ $i }}][notes]" class="form-control form-control-sm"
               value="{{ old('events.' . $i . '.notes', $ev?->notes ?? '') }}">
    </div>
</div>