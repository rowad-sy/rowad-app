@php
    $n = is_numeric($idx) ? ((int) $idx + 1) : 'جديد';
    $err = $errors->first("notes_list.$idx.note");
@endphp
<tr>
    <td>
        <textarea name="notes_list[{{ $idx }}][note]" rows="2" class="form-control form-control-sm{{ $err ? ' is-invalid' : '' }}" aria-label="الملاحظة {{ $n }}"@if ($err) aria-invalid="true" aria-describedby="err-note-{{ $idx }}"@endif>{{ $row['note'] ?? '' }}</textarea>
        @if ($err)<div class="invalid-feedback" id="err-note-{{ $idx }}">{{ $err }}</div>@endif
        @if (!empty($row['meta']))<small class="text-muted">{{ $row['meta'] }}</small>@endif
    </td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" aria-label="إزالة الملاحظة {{ $n }}" title="إزالة"><i class="bi bi-x" aria-hidden="true"></i></button></td>
</tr>
