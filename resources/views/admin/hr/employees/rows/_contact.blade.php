@php
    $n = is_numeric($idx) ? ((int) $idx + 1) : 'جديد';
    $types = ['phone' => 'هاتف', 'mobile' => 'جوال', 'whatsapp' => 'واتسآب', 'emergency' => 'طوارئ', 'email' => 'بريد إلكتروني'];
    $errType = $errors->first("contacts.$idx.type");
    $errValue = $errors->first("contacts.$idx.value");
@endphp
<tr>
    <td>
        <select name="contacts[{{ $idx }}][type]" class="form-select form-select-sm{{ $errType ? ' is-invalid' : '' }}" aria-label="نوع وسيلة الاتصال {{ $n }}"@if ($errType) aria-invalid="true" aria-describedby="err-con-{{ $idx }}-type"@endif>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" {{ ($row['type'] ?? 'phone') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if ($errType)<div class="invalid-feedback" id="err-con-{{ $idx }}-type">{{ $errType }}</div>@endif
    </td>
    <td>
        <input type="text" name="contacts[{{ $idx }}][value]" class="form-control form-control-sm{{ $errValue ? ' is-invalid' : '' }}" aria-label="قيمة وسيلة الاتصال {{ $n }}"
               value="{{ $row['value'] ?? '' }}"@if ($errValue) aria-invalid="true" aria-describedby="err-con-{{ $idx }}-value"@endif>
        @if ($errValue)<div class="invalid-feedback" id="err-con-{{ $idx }}-value">{{ $errValue }}</div>@endif
    </td>
    <td class="text-center">
        {{-- إلغاء التحديد يُرسل غيابًا للمفتاح؛ لذلك يُحدَّد فقط عند وجوده في الصف --}}
        <input type="checkbox" name="contacts[{{ $idx }}][is_primary]" value="1" class="form-check-input" aria-label="وسيلة الاتصال {{ $n }} رئيسية"
               {{ !empty($row['is_primary']) ? 'checked' : '' }}>
    </td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" aria-label="إزالة وسيلة الاتصال {{ $n }}" title="إزالة"><i class="bi bi-x" aria-hidden="true"></i></button></td>
</tr>
