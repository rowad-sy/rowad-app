{{-- صف مؤهل: $idx مفتاح الصف (أو __IDX__ للقالب)، $row القيم (من old() بعد فشل التحقق أو من قاعدة البيانات) --}}
@php $n = is_numeric($idx) ? ((int) $idx + 1) : 'جديد'; @endphp
<tr>
    @foreach ([['qualification', 'المؤهل', 'text'], ['specialization', 'الاختصاص', 'text'], ['university', 'الجامعة', 'text'], ['grade', 'التقدير', 'text'], ['graduation_year', 'سنة التخرج', 'number']] as [$f, $lab, $type])
        @php $err = $errors->first("educations.$idx.$f"); @endphp
        <td>
            <input type="{{ $type }}" name="educations[{{ $idx }}][{{ $f }}]" class="form-control form-control-sm{{ $err ? ' is-invalid' : '' }}"
                   aria-label="{{ $lab }} — مؤهل {{ $n }}" value="{{ $row[$f] ?? '' }}"@if ($err) aria-invalid="true" aria-describedby="err-edu-{{ $idx }}-{{ $f }}"@endif>
            @if ($err)<div class="invalid-feedback" id="err-edu-{{ $idx }}-{{ $f }}">{{ $err }}</div>@endif
        </td>
    @endforeach
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" aria-label="إزالة المؤهل {{ $n }}" title="إزالة"><i class="bi bi-x" aria-hidden="true"></i></button></td>
</tr>
