@extends('admin.layouts.master')

@section('title', 'الطلاب')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الطلاب</h4>
        <p>إدارة بيانات الطلاب</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success" id="issueCertBtn" style="display:none;" onclick="issueCertificates()">
            <i class="bi bi-file-earmark-check me-1"></i> إصدار شهادة
        </button>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة طالب
        </a>
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الكود..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    <option value="graduated" {{ ($status ?? '') === 'graduated' ? 'selected' : '' }}>متخرج</option>
                    <option value="suspended" {{ ($status ?? '') === 'suspended' ? 'selected' : '' }}>موقوف</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">الجنس</label>
                <select name="gender" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($gender ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="male" {{ ($gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                    <option value="female" {{ ($gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <form id="certForm" method="GET" action="{{ route('admin.students.certificates.issue') }}">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>الجنس</th>
                    <th>الهاتف</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-check" onchange="updateIssueBtn()"></td>
                        <td><code>{{ $student->student_code }}</code></td>
                        <td class="fw-medium">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</td>
                        <td>
                            @if ($student->gender === 'male')
                                <span class="badge bg-info text-white">ذكر</span>
                            @else
                                <span class="badge bg-pink text-white">أنثى</span>
                            @endif
                        </td>
                        <td dir="ltr">{{ $student->phone ?? '—' }}</td>
                        <td>{{ $student->center?->name ?? '—' }}</td>
                        <td>{{ $student->project?->name ?? '—' }}</td>
                        <td>
                            @if ($student->status === 'active')
                                <span class="badge bg-success">نشط</span>
                            @elseif ($student->status === 'inactive')
                                <span class="badge bg-secondary">غير نشط</span>
                            @elseif ($student->status === 'graduated')
                                <span class="badge bg-primary">متخرج</span>
                            @elseif ($student->status === 'suspended')
                                <span class="badge bg-warning text-dark">موقوف</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الطالب؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا يوجد طلاب
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $students->total() }} طالب
        </div>
        <div>
            {{ $students->links() }}
        </div>
    </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = master.checked);
    updateIssueBtn();
}
function updateIssueBtn() {
    const checked = document.querySelectorAll('.student-check:checked').length;
    document.getElementById('issueCertBtn').style.display = checked > 0 ? 'inline-block' : 'none';
}
function issueCertificates() {
    document.getElementById('certForm').submit();
}
</script>
@endpush
