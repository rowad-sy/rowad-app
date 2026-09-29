@extends('admin.layouts.master')

@section('title', 'الموظفين')

@push('styles')
<style>
    .export-bar { background: var(--color-surface-muted); border-bottom: 1px solid var(--color-border); padding: 0.75rem 1rem; }
    .export-bar .btn { font-size: 0.85rem; }
    .import-btn { position: relative; overflow: hidden; }
    .import-btn input[type=file] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
</style>
@endpush

@section('content')
<x-page-header :title="'الموظفين'" :description="'إدارة بيانات الموظفين في المؤسسة'"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'الموظفين']]">
    <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة موظف
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
            <div class="col-md-3">
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
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير فعال</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <form id="exportForm" method="POST" action="{{ route('admin.hr.employees.export') }}">
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
                <a href="{{ route('admin.hr.employees.export') }}" class="btn btn-outline-secondary btn-sm"
                   onclick="event.preventDefault();document.getElementById('exportAllForm').submit();">
                    <i class="bi bi-file-earmark-excel me-1"></i> تصدير الكل
                </a>
                <a href="{{ route('admin.hr.employees.export-full') }}" class="btn btn-outline-info btn-sm"
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
                        <th>المركز</th>
                        <th>الإدارة</th>
                        <th>المشروع</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $emp)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $emp->id }}" class="form-check-input row-checkbox" aria-label="تحديد {{ $emp->first_name_ar }} {{ $emp->last_name_ar }}">
                            </td>
                            <td class="ltr-cell text-nowrap">{{ $emp->employee_code }}</td>
                            <td class="fw-medium"><a href="{{ route('admin.hr.employees.show', $emp) }}" class="text-decoration-none">{{ $emp->first_name_ar }} {{ $emp->last_name_ar }}</a></td>
                            <td>{{ $emp->center?->name ?? '—' }}</td>
                            <td>{{ $emp->department?->name_ar ?? '—' }}</td>
                            <td>{{ $emp->project?->name ?? '—' }}</td>
                            <td>
                                <x-status-badge :tone="$emp->status === 'active' ? 'success' : 'neutral'">{{ $emp->status === 'active' ? 'فعال' : 'غير فعال' }}</x-status-badge>
                            </td>
                            <td class="text-nowrap"><div class="row-actions">
                                <a href="{{ route('admin.hr.employees.show', $emp) }}" class="btn btn-sm btn-outline-info" aria-label="عرض ملف {{ $emp->first_name_ar }} {{ $emp->last_name_ar }}" title="عرض الملف"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <a href="{{ route('admin.hr.employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <x-audit-history :model="'App\Models\Admin\Hr\Employee'" :model-id="$emp->id" />
                                <form method="POST" action="{{ route('admin.hr.employees.destroy', $emp) }}" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $emp->first_name_ar }} {{ $emp->last_name_ar }}" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <x-empty-row colspan="8" icon="bi-person-workspace" title="لا يوجد موظفون بعد" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <form id="exportAllForm" method="POST" action="{{ route('admin.hr.employees.export') }}" class="d-none">
        @csrf
    </form>
    <form id="exportFullAllForm" method="POST" action="{{ route('admin.hr.employees.export-full') }}" class="d-none">
        @csrf
    </form>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $employees->total() }} موظف
        </div>
        <div>
            {{ $employees->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.hr.employees.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">استيراد موظفين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ملف إكسل</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i>
                    يجب أن يحتوي الملف على الأعمدة التالية: كود الموظف، الاسم AR، الاسم EN، الحالة، رقم الهوية، الجنس، الجنسية، تاريخ الميلاد، مكان الميلاد، الحالة الاجتماعية، عدد الأولاد، المركز، الإدارة، المشروع، البريد الإلكتروني
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
        <form method="POST" action="{{ route('admin.hr.employees.import-full') }}" enctype="multipart/form-data" class="modal-content">
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
                    <li><strong>employees</strong> - جميع بيانات الموظفين الأساسية</li>
                    <li><strong>employee_educations</strong> - المؤهلات والدراسات</li>
                    <li><strong>employee_contacts</strong> - جهات الاتصال</li>
                    <li><strong>work_schedules</strong> - جداول الدوام</li>
                    <li><strong>contracts</strong> - العقود</li>
                    <li><strong>salaries</strong> - الرواتب</li>
                    <li><strong>warnings</strong> - الإنذارات</li>
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

    function updateUI() {
        var checked = document.querySelectorAll('.row-checkbox:checked').length;
        countEl.textContent = checked + ' مختار';
        exportBtn.disabled = checked === 0;
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

    // Export selected: only submit checked rows
    document.getElementById('exportForm').addEventListener('submit', function (e) {
        var checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            return;
        }
    });
});
</script>
@endpush
