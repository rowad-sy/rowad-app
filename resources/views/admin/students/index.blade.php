@extends('admin.layouts.master')

@section('title', 'الطلاب')

@push('styles')
<style>
    .export-bar { background: var(--color-surface-muted); border-bottom: 1px solid var(--color-border); padding: 0.75rem 1rem; }
    .export-bar .btn { font-size: 0.85rem; }
    .import-btn { position: relative; overflow: hidden; }
    .import-btn input[type=file] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
</style>
@endpush

@section('content')
<x-page-header :title="'الطلاب'" :description="'إدارة بيانات الطلاب'"
               :breadcrumb="[['label' => 'الطلاب']]">
    <button type="button" class="btn btn-success" id="issueCertBtn" style="display:none;" onclick="issueCertificates()">
            <i class="bi bi-file-earmark-check me-1"></i> إصدار شهادة
        </button>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة طالب
    </a>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots" aria-hidden="true"></i> استيراد
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-2" aria-hidden="true"></i>استيراد</button></li>
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importFullModal"><i class="bi bi-upload me-2" aria-hidden="true"></i>استيراد كامل</button></li>
        </ul>
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-2">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الكود..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الفوج</label>
                <select name="cohort_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($cohorts as $cohort)
                        <option value="{{ $cohort->id }}" {{ (int)($cohortId ?? '') === $cohort->id ? 'selected' : '' }}>{{ $cohort->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">المقرر</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ (int)($courseId ?? '') === $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    <option value="graduated" {{ ($status ?? '') === 'graduated' ? 'selected' : '' }}>متخرج</option>
                    <option value="suspended" {{ ($status ?? '') === 'suspended' ? 'selected' : '' }}>موقوف</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">الجنس</label>
                <select name="gender" class="form-select form-select-sm">
                    <option value="all" {{ ($gender ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="male" {{ ($gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                    <option value="female" {{ ($gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <form id="exportForm" method="POST" action="{{ route('admin.students.export') }}">
        @csrf
        <div class="export-bar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectAll">
                    <label class="form-check-label small" for="selectAll">تحديد الكل</label>
                </div>
                <span class="text-muted small" id="selectedCount">0 مختار</span>
            </div>
            <div>
                <button type="submit" class="btn btn-success btn-sm" id="exportSelected" disabled>
                    <i class="bi bi-download me-1"></i> تصدير المحدد
                </button>
                <a href="{{ route('admin.students.export') }}" class="btn btn-outline-secondary btn-sm"
                   onclick="event.preventDefault();document.getElementById('exportAllForm').submit();">
                    <i class="bi bi-file-earmark-excel me-1"></i> تصدير الكل
                </a>
                <a href="{{ route('admin.students.export-full') }}" class="btn btn-outline-info btn-sm"
                   onclick="event.preventDefault();document.getElementById('exportFullAllForm').submit();">
                    <i class="bi bi-files me-1"></i> تصدير كامل
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>الكود</th>
                        <th>الاسم</th>
                        <th>الجنس</th>
                        <th>الهاتف</th>
                        <th>المركز</th>
                        <th>المشاريع</th>
                        <th>الفوج</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $student->id }}" class="form-check-input row-checkbox"
                                       data-student-id="{{ $student->id }}">
                            </td>
                            <td class="ltr-cell text-nowrap">{{ $student->student_code }}</td>
                            <td class="fw-medium">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</td>
                            <td>
                                {{ $student->gender === 'male' ? 'ذكر' : 'أنثى' }}
                            </td>
                            <td class="ltr-cell text-nowrap">{{ $student->phone ?? '—' }}</td>
                            <td>{{ $student->center?->name ?? '—' }}</td>
                            <td>
                                @if ($student->projects->isNotEmpty())
                                    @foreach ($student->projects as $p)
                                        <x-status-badge tone="info" class="me-1">{{ $p->name }}</x-status-badge>
                                    @endforeach
                                @else
                                    {{ $student->project?->name ?? '—' }}
                                @endif
                            </td>
                            <td>
                                @if ($student->cohort)
                                    <x-status-badge>{{ $student->cohort->name }}</x-status-badge>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($student->status === 'active')
                                    <x-status-badge tone="success">نشط</x-status-badge>
                                @elseif ($student->status === 'inactive')
                                    <x-status-badge>غير نشط</x-status-badge>
                                @elseif ($student->status === 'graduated')
                                    <x-status-badge tone="brand">متخرج</x-status-badge>
                                @elseif ($student->status === 'suspended')
                                    <x-status-badge tone="warning">موقوف</x-status-badge>
                                @endif
                            </td>
                            <td class="text-nowrap"><div class="row-actions">
                                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <x-audit-history :model="'App\Models\Admin\Student\Student'" :model-id="$student->id" />
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من إزالة هذا الطالب من المشروع؟')">
                                    @csrf
                                    @method('DELETE')
                                    @if ($projectId)
                                        <input type="hidden" name="project_id" value="{{ $projectId }}">
                                    @endif
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <x-empty-row colspan="10" icon="bi-mortarboard" title="لا يوجد طلاب بعد" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <form id="exportAllForm" method="POST" action="{{ route('admin.students.export') }}" class="d-none">
        @csrf
    </form>
    <form id="exportFullAllForm" method="POST" action="{{ route('admin.students.export-full') }}" class="d-none">
        @csrf
    </form>

    <form id="certForm" method="GET" action="{{ route('admin.students.certificates.issue') }}" class="d-none"></form>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $students->total() }} طالب
        </div>
        <div>
            {{ $students->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">استيراد طلاب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ملف إكسل</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i>
                    يُقبل الملف بأسماء أعمدة إنجليزية أو عربية. الأعمدة المطلوبة:
                </p>
                <ul class="text-muted small mt-1 mb-0" style="list-style:disc inside;">
                    <li>student_code / كود الطالب</li>
                    <li>first_name_ar / الاسم الأول AR</li>
                    <li>last_name_ar / الاسم الأخير AR</li>
                    <li>gender / الجنس (ذكر/أنثى)</li>
                    <li>birth_date / تاريخ الميلاد</li>
                    <li>center_name / المركز</li>
                    <li>status / الحالة (نشط/غير نشط/متخرج/موقوف)</li>
                    <li>enrollment_date / تاريخ التسجيل</li>
                </ul>
                <p class="text-muted small mt-1 mb-0">يمكنك تصدير الطلاب أولاً ثم تعديل الملف وإعادة استيراده.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">استيراد</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="importFullModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.import-full') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">استيراد كامل (جميع الجداول)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ملف إكسل</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i>
                    يجب أن يحتوي الملف على الشيتات التالية (بنفس أسماء الأعمدة الإنكليزية):
                </p>
                <ul class="text-muted small mt-2 mb-0">
                    <li><strong>students</strong> - جميع بيانات الطلاب الأساسية</li>
                    <li><strong>enrollments</strong> - التسجيلات في المقررات</li>
                    <li><strong>certificates</strong> - الشهادات</li>
                </ul>
                <p class="text-muted small mt-2 mb-0">يمكنك تنزيل ملف تصدير كامل وتعديله ثم رفعه مرّة أخرى.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">استيراد</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.row-checkbox');
    var exportBtn = document.getElementById('exportSelected');
    var countEl = document.getElementById('selectedCount');
    var issueCertBtn = document.getElementById('issueCertBtn');

    function updateUI() {
        var checked = document.querySelectorAll('.row-checkbox:checked').length;
        countEl.textContent = checked + ' مختار';
        exportBtn.disabled = checked === 0;
        issueCertBtn.style.display = checked > 0 ? 'inline-block' : 'none';
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checked === checkboxes.length;
        }
    }

    selectAll?.addEventListener('change', function () {
        checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
        updateUI();
    });

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateUI);
    });

    document.getElementById('exportForm').addEventListener('submit', function (e) {
        var checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
        }
    });
});

function issueCertificates() {
    var checked = document.querySelectorAll('.row-checkbox:checked');
    var certForm = document.getElementById('certForm');
    certForm.querySelectorAll('input[name="student_ids[]"]').forEach(function (el) { el.remove(); });
    checked.forEach(function (cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_ids[]';
        input.value = cb.value;
        certForm.appendChild(input);
    });
    certForm.submit();
}
</script>
@endpush
