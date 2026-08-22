@extends('admin.layouts.master')

@section('title', 'الطلاب')

@push('styles')
<style>
    .export-bar { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; }
    .export-bar .btn { font-size: 0.85rem; }
    .import-btn { position: relative; overflow: hidden; }
    .import-btn input[type=file] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
</style>
@endpush

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
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#identityCheckModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة طالب
        </button>
        <button type="button" class="btn btn-outline-primary import-btn" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-1"></i> استيراد
        </button>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importFullModal">
            <i class="bi bi-upload me-1"></i> استيراد كامل
        </button>
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
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
                <label class="form-label small mb-1">المقرر</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ (int)($courseId ?? '') === $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
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
            <div class="col-md-1">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

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
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"></th>
                        <th>الكود</th>
                        <th>الاسم</th>
                        <th>الجنس</th>
                        <th>الهاتف</th>
                        <th>المركز</th>
                        <th>المشاريع</th>
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
                            <td>
                                @if ($student->projects->isNotEmpty())
                                    @foreach ($student->projects as $p)
                                        <span class="badge bg-info me-1">{{ $p->name }}</span>
                                    @endforeach
                                @else
                                    {{ $student->project?->name ?? '—' }}
                                @endif
                            </td>
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
                                <x-audit-history :model="'App\Models\Admin\Student\Student'" :model-id="$student->id" />
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من إزالة هذا الطالب من المشروع؟')">
                                    @csrf
                                    @method('DELETE')
                                    @if ($projectId)
                                        <input type="hidden" name="project_id" value="{{ $projectId }}">
                                    @endif
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
                    يجب أن يحتوي الملف على الأعمدة التالية: كود الطالب، نوع الهوية، رقم الهوية، الاسم الأول AR، الاسم الأخير AR، الاسم الأول EN، الاسم الأخير EN، الجنس، تاريخ الميلاد، الجنسية، الهاتف، البريد الإلكتروني، المركز، المشاريع (أو المشروع للتوافق مع الإصدارات السابقة)، الحالة، تاريخ التسجيل
                </p>
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
<!-- Hidden triggers for programmatic modal control (avoids bootstrap.Modal JS API) -->
<button type="button" id="showAddToProjectModalTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#addToProjectModal"></button>
<button type="button" id="hideIdentityModalTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#identityCheckModal"></button>

<!-- مودال البحث عن الطالب بهوية -->
<div class="modal fade" id="identityCheckModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-search me-1"></i> البحث عن طالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">أدخل رقم الهوية للبحث عن الطالب. إذا لم يكن موجوداً سيتم نقلك إلى صفحة إنشاء طالب جديد.</p>
                <div class="mb-3">
                    <label class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                    <input type="text" id="identityCheckInput" class="form-control" placeholder="أدخل رقم الهوية" dir="ltr" autofocus>
                </div>
                <div id="identityCheckResult" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="identityCheckBtn" onclick="checkIdentity()">
                    <i class="bi bi-search me-1"></i> بحث
                </button>
            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة الطالب لمشاريع أخرى -->
<div class="modal fade" id="addToProjectModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addToProjectForm" method="POST">
                @csrf
                <input type="hidden" name="student_id" id="addProjectStudentId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-1"></i> إضافة الطالب لمشاريع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="addProjectStudentInfo" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">المشاريع المتاحة للإضافة</label>
                        <div id="availableProjectsList" class="d-flex flex-wrap gap-2"></div>
                        <small class="text-muted">اختر مشروعاً واحداً أو أكثر</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
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

    // Enter key triggers search in identity modal
    document.getElementById('identityCheckInput')?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); checkIdentity(); }
    });

    // Re-focus input when modal opens
    document.getElementById('identityCheckModal')?.addEventListener('shown.bs.modal', function () {
        document.getElementById('identityCheckInput').focus();
        document.getElementById('identityCheckInput').value = '';
        document.getElementById('identityCheckResult').innerHTML = '';
    });
});

function checkIdentity() {
    var input = document.getElementById('identityCheckInput');
    var result = document.getElementById('identityCheckResult');
    var btn = document.getElementById('identityCheckBtn');
    var number = input.value.trim();

    if (!number) { input.focus(); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري البحث...';
    result.innerHTML = '';

    fetch('{{ route("admin.students.check-identity") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ identity_number: number }),
    })
    .then(function (res) {
        if (!res.ok) {
            throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        }
        return res.json();
    })
    .then(function (data) {
        if (data.found === false) {
            window.location.href = data.redirect;
            return;
        }

        if (data.can_edit) {
            window.location.href = data.redirect;
            return;
        }

        // Cannot edit → show add-to-project modal via hidden trigger
        var info = document.getElementById('addProjectStudentInfo');
        var projectsStr = data.student.projects && data.student.projects.length
            ? data.student.projects.join('، ')
            : 'لا يوجد';
        info.innerHTML = '<div class="alert alert-info">' +
            '<strong>' + data.student.name + '</strong><br>' +
            'الكود: <code>' + data.student.code + '</code><br>' +
            'المشاريع الحالية: ' + projectsStr +
            '</div>';

        document.getElementById('addProjectStudentId').value = data.student.id;
        document.getElementById('addToProjectForm').action = '{{ url("admin/students") }}/' + data.student.id + '/add-to-projects';

        var list = document.getElementById('availableProjectsList');
        list.innerHTML = '';
        data.available_projects.forEach(function (p) {
            var div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = '<input class="form-check-input" type="checkbox" name="project_ids[]" value="' + p.id + '" id="proj_' + p.id + '">' +
                '<label class="form-check-label" for="proj_' + p.id + '">' + p.name + '</label>';
            list.appendChild(div);
        });

        // Hide identity modal, show add-to-project modal via hidden Bootstrap triggers
        document.getElementById('hideIdentityModalTrigger').click();
        setTimeout(function () {
            document.getElementById('showAddToProjectModalTrigger').click();
        }, 300);
    })
    .catch(function (err) {
        result.innerHTML = '<div class="alert alert-danger">' +
            '<i class="bi bi-exclamation-triangle me-1"></i> حدث خطأ أثناء البحث: ' + err.message +
            '</div>';
    })
    .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search me-1"></i> بحث';
    });
}

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
