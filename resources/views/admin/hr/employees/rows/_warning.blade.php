@php
    $n = is_numeric($idx) ? ((int) $idx + 1) : 'جديد';
    $errDate = $errors->first("warnings.$idx.date");
    $errReason = $errors->first("warnings.$idx.reason");
@endphp
<tr>
    <td>
        <input type="date" name="warnings[{{ $idx }}][date]" class="form-control form-control-sm{{ $errDate ? ' is-invalid' : '' }}" aria-label="تاريخ التنبيه {{ $n }}"
               value="{{ $row['date'] ?? '' }}"@if ($errDate) aria-invalid="true" aria-describedby="err-war-{{ $idx }}-date"@endif>
        @if ($errDate)<div class="invalid-feedback" id="err-war-{{ $idx }}-date">{{ $errDate }}</div>@endif
    </td>
    <td>
        <input type="text" name="warnings[{{ $idx }}][reason]" class="form-control form-control-sm{{ $errReason ? ' is-invalid' : '' }}" aria-label="سبب التنبيه {{ $n }}"
               value="{{ $row['reason'] ?? '' }}"@if ($errReason) aria-invalid="true" aria-describedby="err-war-{{ $idx }}-reason"@endif>
        @if ($errReason)<div class="invalid-feedback" id="err-war-{{ $idx }}-reason">{{ $errReason }}</div>@endif
    </td>
    <td>
        <select name="warnings[{{ $idx }}][level]" class="form-select form-select-sm" aria-label="مستوى التنبيه {{ $n }}">
            @foreach (['verbal' => 'شفهي', 'written' => 'كتابي', 'termination' => 'إنذار بالفصل'] as $value => $label)
                <option value="{{ $value }}" {{ ($row['level'] ?? 'verbal') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="text-center">
        @if (array_key_exists('is_folded', $row))
            <x-status-badge :tone="$row['is_folded'] ? 'success' : 'warning'">{{ $row['is_folded'] ? 'مطوي' : 'غير مطوي' }}</x-status-badge>
        @else
            —
        @endif
    </td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" aria-label="إزالة التنبيه {{ $n }}" title="إزالة"><i class="bi bi-x" aria-hidden="true"></i></button></td>
</tr>
