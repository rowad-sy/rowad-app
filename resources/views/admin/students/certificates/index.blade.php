@extends('admin.layouts.master')

@section('title', 'الشهادات المصدرة')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الشهادات المصدرة</h4>
        <p>جميع الشهادات التي تم إصدارها</p>
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="رقم الشهادة أو اسم الطالب..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">التصميم</label>
                <select name="design_id" class="form-select" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($designs as $d)
                        <option value="{{ $d->id }}" {{ $designId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('admin.students.certificates.print-batch', ['design_id' => $designId]) }}"
                   class="btn btn-success w-100 {{ $certificates->isEmpty() ? 'disabled' : '' }}"
                   target="_blank">
                    <i class="bi bi-printer me-1"></i> طباعة الكل
                </a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>رقم الشهادة</th>
                    <th>الطالب</th>
                    <th>التصميم</th>
                    <th>تاريخ الإصدار</th>
                    <th>التحقق</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($certificates as $cert)
                    <tr>
                        <td>{{ $cert->id }}</td>
                        <td><code>{{ $cert->certificate_number }}</code></td>
                        <td>
                            <a href="{{ route('admin.students.show', $cert->student) }}" class="text-decoration-none">
                                {{ $cert->student->first_name_ar }} {{ $cert->student->last_name_ar }}
                            </a>
                            <br><small class="text-muted">{{ $cert->student->student_code }}</small>
                        </td>
                        <td>{{ $cert->design?->name ?? '—' }}</td>
                        <td>{{ $cert->issue_date?->format('Y-m-d') }}</td>
                        <td>
                            @if ($cert->is_verified)
                                <span class="badge bg-success">تم</span>
                            @else
                                <span class="badge bg-secondary">لا</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.students.certificates.preview', $cert) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                <i class="bi bi-eye"></i> معاينة
                            </a>
                            <x-audit-history :model="'App\Models\Admin\Student\Certificate'" :model-id="$cert->id" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-x fs-3 d-block mb-2"></i>
                            لا يوجد شهادات مصدرة
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $certificates->total() }} شهادة</div>
        <div>{{ $certificates->links() }}</div>
    </div>
</div>
@endsection
