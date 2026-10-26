@php
    $i = $index ?? 0;
    $event = $event ?? (object) [];
    $dateValue = old('events.' . $i . '.event_date', $event->event_date ?? '');
    $dateValue = $dateValue instanceof \DateTimeInterface ? $dateValue->format('Y-m-d') : $dateValue;
@endphp
<div class="simple-event-card border rounded p-3 mb-3 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;left:8px"
            onclick="removeSimpleEventRow(this)" aria-label="حذف الفعالية" title="حذف الفعالية">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small">تاريخ الفعالية <span class="text-danger">*</span></label>
            <input type="date" name="events[{{ $i }}][event_date]" class="form-control form-control-sm @error('events.' . $i . '.event_date') is-invalid @enderror"
                   value="{{ $dateValue }}" required>
            @error('events.' . $i . '.event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small">وقت البداية <span class="text-danger">*</span></label>
            <input type="time" name="events[{{ $i }}][event_time]" class="form-control form-control-sm @error('events.' . $i . '.event_time') is-invalid @enderror"
                   value="{{ old('events.' . $i . '.event_time', isset($event->event_time) ? substr((string) $event->event_time, 0, 5) : '') }}" required>
            @error('events.' . $i . '.event_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small">وقت النهاية <span class="text-danger">*</span></label>
            <input type="time" name="events[{{ $i }}][event_end_time]" class="form-control form-control-sm @error('events.' . $i . '.event_end_time') is-invalid @enderror"
                   value="{{ old('events.' . $i . '.event_end_time', isset($event->event_end_time) ? substr((string) $event->event_end_time, 0, 5) : '') }}" required>
            @error('events.' . $i . '.event_end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small">اسم الفعالية <span class="text-danger">*</span></label>
            <input type="text" name="events[{{ $i }}][event_name]" class="form-control form-control-sm @error('events.' . $i . '.event_name') is-invalid @enderror"
                   value="{{ old('events.' . $i . '.event_name', $event->event_name ?? '') }}" required>
            @error('events.' . $i . '.event_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small">الموقع</label>
            <input type="text" name="events[{{ $i }}][location]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.location', $event->location ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">نوع التغطية المطلوبة</label>
            <input type="text" name="events[{{ $i }}][coverage_type]" class="form-control form-control-sm"
                   placeholder="تصوير، تقرير، مقابلة…" value="{{ old('events.' . $i . '.coverage_type', $event->coverage_type ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">ملاحظات</label>
            <input type="text" name="events[{{ $i }}][notes]" class="form-control form-control-sm"
                   value="{{ old('events.' . $i . '.notes', $event->notes ?? '') }}">
        </div>
    </div>
</div>
