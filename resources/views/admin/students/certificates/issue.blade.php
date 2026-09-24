@extends('admin.layouts.master')

@section('title', 'إصدار شهادات')

@section('content')
<div class="page-header">
    <h4>إصدار شهادات</h4>
    <p>تأكيد إصدار الشهادات للطلاب المحددين</p>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">الطلاب المحددون ({{ $students->count() }})</h5></div>
            <div class="table-responsive" style="max-height:400px;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>الكود</th><th>الاسم</th><th>المركز</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td><code>{{ $student->student_code }}</code></td>
                                <td>{{ $student->first_name_ar }} {{ $student->last_name_ar }}</td>
                                <td>{{ $student->center?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <form method="POST" action="{{ route('admin.students.certificates.generate') }}">
            @csrf
            @foreach ((array)$studentIds as $sid)
                <input type="hidden" name="student_ids[]" value="{{ $sid }}">
            @endforeach

            <div class="table-container">
                <div class="p-3 border-bottom"><h5 class="mb-0">إعدادات الإصدار</h5></div>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label">التصميم <span class="text-danger">*</span></label>
                        <select name="design_id" class="form-select" required>
                            <option value="">اختر تصميماً</option>
                            @foreach ($designs as $d)
                                <option value="{{ $d->id }}" {{ ($design->id ?? '') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }} ({{ $d->year }})
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2">
                            <a href="{{ route('admin.students.certificates.designer.create', ['student_ids' => $studentIds]) }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i> تصميم جديد
                            </a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المقرر (اختياري)</label>
                        <select name="course_id" class="form-select">
                            <option value="">بدون</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ ($courseId ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الفترة (اختياري)</label>
                        <select name="period_id" class="form-select">
                            <option value="">بدون</option>
                            @foreach ($periods as $p)
                                <option value="{{ $p->id }}">{{ $p->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">مجموعة التوقيعات</label>
                        <select name="signatory_set_id" class="form-select">
                            <option value="">تلقائي (حسب المقرر + الفترة + مركز كل طالب)</option>
                            @foreach ($signatorySets as $set)
                                <option value="{{ $set->id }}">
                                    {{ $set->name }}
                                    @if ($set->course) — {{ $set->course->name_ar }} @endif
                                    @if ($set->center) — {{ $set->center->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            <a href="{{ route('admin.students.certificates.signatory-sets.index') }}" target="_blank">
                                <i class="bi bi-person-sign me-1"></i> إدارة مجموعات التوقيع والموقعين
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('سيتم إصدار {{ $students->count() }} شهادة. هل أنت متأكد؟')">
                        <i class="bi bi-file-earmark-check me-1"></i> إصدار {{ $students->count() }} شهادة
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
