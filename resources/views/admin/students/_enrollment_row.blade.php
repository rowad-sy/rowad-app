{{-- صف تسجيل في نموذج الطالب. $idx رقم الصف (أو __IDX__ للقالب الديناميكي)، $row قيم الصف (من old() أو من قاعدة البيانات) --}}
@php
    $err = fn ($k) => $errors->first("enrollments.$idx.$k");
    $n = is_numeric($idx) ? ((int) $idx + 1) : 'جديد';
    $cls = fn ($k) => $err($k) ? ' is-invalid' : '';
    $described = fn ($k) => $err($k) ? ' aria-invalid="true" aria-describedby="err-enr-'.$idx.'-'.$k.'"' : '';
@endphp
<tr>
    <td>
        <select name="enrollments[{{ $idx }}][course_id]" class="form-select form-select-sm{{ $cls('course_id') }}" aria-label="المقرر في التسجيل {{ $n }}" required{!! $described('course_id') !!}>
            <option value="">— اختر —</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" data-project-id="{{ $course->project_id }}" {{ (string) ($row['course_id'] ?? '') === (string) $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
            @endforeach
        </select>
        @if ($err('course_id'))<div class="invalid-feedback" id="err-enr-{{ $idx }}-course_id">{{ $err('course_id') }}</div>@endif
    </td>
    <td>
        <select name="enrollments[{{ $idx }}][period_id]" class="form-select form-select-sm{{ $cls('period_id') }}" aria-label="الفترة في التسجيل {{ $n }}" required{!! $described('period_id') !!}>
            <option value="">— اختر —</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" {{ (string) ($row['period_id'] ?? '') === (string) $period->id ? 'selected' : '' }}>{{ $period->name_ar }} ({{ $period->year }})</option>
            @endforeach
        </select>
        @if ($err('period_id'))<div class="invalid-feedback" id="err-enr-{{ $idx }}-period_id">{{ $err('period_id') }}</div>@endif
    </td>
    <td>
        <input type="date" name="enrollments[{{ $idx }}][enrollment_date]" class="form-control form-control-sm{{ $cls('enrollment_date') }}" aria-label="تاريخ التسجيل في التسجيل {{ $n }}"
               value="{{ $row['enrollment_date'] ?? '' }}"{!! $described('enrollment_date') !!}>
        @if ($err('enrollment_date'))<div class="invalid-feedback" id="err-enr-{{ $idx }}-enrollment_date">{{ $err('enrollment_date') }}</div>@endif
    </td>
    <td>
        <select name="enrollments[{{ $idx }}][status]" class="form-select form-select-sm{{ $cls('status') }}" aria-label="الحالة في التسجيل {{ $n }}"{!! $described('status') !!}>
            <option value="enrolled" {{ ($row['status'] ?? 'enrolled') === 'enrolled' ? 'selected' : '' }}>مسجل</option>
            <option value="completed" {{ ($row['status'] ?? '') === 'completed' ? 'selected' : '' }}>مكتمل</option>
            <option value="dropped" {{ ($row['status'] ?? '') === 'dropped' ? 'selected' : '' }}>منسحب</option>
        </select>
        @if ($err('status'))<div class="invalid-feedback" id="err-enr-{{ $idx }}-status">{{ $err('status') }}</div>@endif
    </td>
    <td>
        <input type="number" name="enrollments[{{ $idx }}][grade]" class="form-control form-control-sm{{ $cls('grade') }}" min="0" max="100" step="0.01" aria-label="الدرجة في التسجيل {{ $n }}"
               value="{{ $row['grade'] ?? '' }}"{!! $described('grade') !!}>
        @if ($err('grade'))<div class="invalid-feedback" id="err-enr-{{ $idx }}-grade">{{ $err('grade') }}</div>@endif
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" aria-label="حذف التسجيل {{ $n }}" title="حذف التسجيل" onclick="removeEnrollmentRow(this)">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    </td>
</tr>
