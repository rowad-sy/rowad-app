@extends('admin.layouts.master')

@section('title', isset($permission) ? 'تعديل الصلاحيات' : 'إضافة صلاحيات')

@push('styles')
<style>
    .matrix-wrap { overflow-x: auto; }
    .matrix { white-space: nowrap; font-size: 0.9rem; }
    .matrix th.cat-head { background: #eef1f4; text-align: center; font-size: 0.95rem; }
    .matrix th.model-head { background: #f8f9fa; font-weight: 600; text-align: center; font-size: 0.85rem; }
    .matrix td.perm-cell { min-width: 150px; vertical-align: top; text-align: right; }
    .matrix th.row-col,
    .matrix td.row-col {
        position: sticky;
        right: 0;
        z-index: 3;
        background: #fff;
        min-width: 340px;
        box-shadow: -2px 0 4px rgba(0,0,0,0.06);
    }
    .matrix thead th.row-col { z-index: 4; }
    .perm-cell .perm-flag {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        padding: 0.25rem 0;
    }
    .perm-cell .perm-flag input[type="checkbox"] { width: 1.15em; height: 1.15em; margin-top: 0; }
    .perm-cell .perm-flag label { margin: 0; cursor: pointer; }
    .row-col .form-select { font-size: 0.9rem; }
    .row-col .btn-group .btn { font-size: 0.9rem; }
    .row-actions { display: flex; flex-direction: column; gap: 0.4rem; align-items: flex-start; }
    .cat-select-all, .row-select-all { font-size: 0.85rem; cursor: pointer; color: #0d6efd; }
    .cat-select-all:hover, .row-select-all:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h4>{{ isset($permission) ? 'تعديل الصلاحيات' : 'إضافة صلاحيات' }}</h4>
    <p>
        <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none">الصلاحيات</a>
        / {{ isset($permission) ? 'تعديل' : 'جديد' }}
    </p>
</div>

@php
    $gridRows = [];
    if (old('rows')) {
        foreach (old('rows') as $i => $r) {
            $assignTo = $r['assign_to'] ?? 'user';
            $gridRows[] = [
                'assign_to' => $assignTo,
                'user_id' => $assignTo === 'user' ? ($r['user_id'] ?? '') : '',
                'group_id' => $assignTo === 'group' ? ($r['group_id'] ?? '') : '',
                'perms' => old("perms.$i") ?? [],
            ];
        }
    } else {
        foreach ($rows ?? [] as $r) {
            $assignTo = $r['assign_to'] ?? 'user';
            $gridRows[] = [
                'assign_to' => $assignTo,
                'user_id' => $assignTo === 'user' ? ($r['user_id'] ?? '') : '',
                'group_id' => $assignTo === 'group' ? ($r['group_id'] ?? '') : '',
                'perms' => $r['perms'] ?? [],
            ];
        }
    }
    $flags = ['can_view' => 'عرض', 'can_create' => 'إضافة', 'can_edit' => 'تعديل', 'can_delete' => 'حذف'];
@endphp

<div class="row">
    <div class="col-12">
        <form method="POST"
              action="{{ isset($permission) ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">
            @csrf
            @if (isset($permission))
                @method('PUT')
            @endif

            {{-- النطاق المشترك للمصفوفة كلها --}}
            <div class="form-card mb-3">
                <h6 class="mb-3"><i class="bi bi-geo-alt me-1"></i> النطاق المشترك (يُطبق على جميع الموديلات)</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">جميع المراكز</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}"
                                    {{ old('center_id', $scope['center_id'] ?? '') == $center->id ? 'selected' : '' }}>
                                    {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" id="scope_project" class="form-select @error('project_id') is-invalid @enderror" onchange="filterCohorts()">
                            <option value="">جميع المشاريع</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id', $scope['project_id'] ?? '') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الفوج</label>
                        <select name="cohort_id" id="scope_cohort" class="form-select @error('cohort_id') is-invalid @enderror">
                            <option value="">جميع الأفواج</option>
                            @foreach ($cohorts as $cohort)
                                <option value="{{ $cohort->id }}" data-project-id="{{ $cohort->project_id }}"
                                    {{ old('cohort_id', $scope['cohort_id'] ?? '') == $cohort->id ? 'selected' : '' }}>
                                    {{ $cohort->name }} ({{ $cohort->project?->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('cohort_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- أزرار الشبكة --}}
            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="addRow()">
                    <i class="bi bi-person-plus me-1"></i> إضافة عنصر (مستخدم/مجموعة)
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setAllFlags(true)">
                    <i class="bi bi-check2-square me-1"></i> تحديد الكل
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAllFlags(false)">
                    إلغاء التحديد
                </button>
            </div>

            {{-- شبكة المصفوفة --}}
            <div class="form-card">
                <div class="matrix-wrap">
                    <table class="table table-bordered table-sm matrix align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="row-col">العنصر</th>
                                @foreach ($modelGroups as $category => $models)
                                    <th class="cat-head" colspan="{{ count($models) }}">
                                        {{ $category }}
                                        <a href="#" class="d-block cat-select-all" data-cat="{{ $loop->index }}"
                                           onclick="event.preventDefault(); setCatAll({{ $loop->index }}, true)">تحديد الكل</a>
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="row-col"></th>
                                @foreach ($modelGroups as $models)
                                    @foreach ($models as $model => $label)
                                        <th class="model-head">{{ $label }}</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="matrix-body">
                            @foreach ($gridRows as $ri => $row)
                                @include('admin.permissions._matrix_row', [
                                    'ri' => $ri,
                                    'row' => $row,
                                    'modelGroups' => $modelGroups,
                                    'modelKeys' => $modelKeys,
                                    'flags' => $flags,
                                ])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('rows')
                    <div class="alert alert-danger py-2 mt-2 mb-2">{{ $message }}</div>
                @enderror
                @error('rows.*')
                    <div class="alert alert-danger py-2 mt-2 mb-2">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    كل خلية = موديل واحد بنطاقه وأعلامه المستقلة. الخلايا غير المحددة لا تُنشئ سجلات،
                    وعند التعديل تُحذف السجلات التي أُزيل تحديدها (ضمن النطاق المعروض فقط).
                </small>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> حفظ
                </button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

{{-- قالب صف جديد يُنسخ عند الضغط على "إضافة عنصر": الصفوف المخدومة تستخدم نفس الجزء الفرعي --}}
<template id="row-template">
    @include('admin.permissions._matrix_row', [
        'ri' => '__ROW__',
        'row' => ['assign_to' => 'user', 'user_id' => '', 'group_id' => '', 'perms' => []],
        'modelGroups' => $modelGroups,
        'modelKeys' => $modelKeys,
        'flags' => $flags,
    ])
</template>
@endsection

@push('scripts')
<script>
let gridRowCount = {{ count($gridRows) }};

function addRow() {
    const template = document.getElementById('row-template');
    const idx = gridRowCount++;
    const clone = template.content.firstElementChild.cloneNode(true);
    clone.innerHTML = clone.innerHTML.split('__ROW__').join(idx);
    clone.setAttribute('data-row-index', idx);
    document.getElementById('matrix-body').appendChild(clone);
}

function removeRow(button) {
    button.closest('tr').remove();
}

function duplicateRow(button) {
    const source = button.closest('tr');
    const oldIdx = source.getAttribute('data-row-index');
    const idx = gridRowCount++;
    const clone = source.cloneNode(true);

    clone.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace('[' + oldIdx + ']', '[' + idx + ']');
    });
    clone.querySelectorAll('[id]').forEach(function (el) {
        el.id = el.id.replace('_' + oldIdx, '_' + idx);
    });
    clone.querySelectorAll('label[for]').forEach(function (el) {
        el.setAttribute('for', el.getAttribute('for').replace('_' + oldIdx, '_' + idx));
    });
    clone.querySelectorAll('[data-row]').forEach(function (el) {
        el.setAttribute('data-row', el.getAttribute('data-row').replace(new RegExp('^' + oldIdx + '$'), idx));
    });
    clone.setAttribute('data-row-index', idx);

    source.after(clone);
}

function setRowAssign(radio) {
    const tr = radio.closest('tr');
    const isUser = radio.value === 'user';
    tr.querySelectorAll('.row-col select').forEach(function (sel) {
        if (sel.name.indexOf('[user_id]') !== -1) {
            sel.style.display = isUser ? '' : 'none';
        } else if (sel.name.indexOf('[group_id]') !== -1) {
            sel.style.display = isUser ? 'none' : '';
        }
    });
}

function setRowAll(link, select) {
    link.closest('tr').querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
        cb.checked = select;
    });
}

function setCatAll(catIndex, select) {
    document.querySelectorAll('#matrix-body .perm-cell[data-cat="' + catIndex + '"] input[type="checkbox"]').forEach(function (cb) {
        cb.checked = select;
    });
}

function setAllFlags(select) {
    document.querySelectorAll('#matrix-body input[type="checkbox"]').forEach(function (cb) {
        cb.checked = select;
    });
}

function filterCohorts() {
    const projectId = document.getElementById('scope_project').value;
    const cohortSelect = document.getElementById('scope_cohort');
    cohortSelect.querySelectorAll('option').forEach(function (opt) {
        if (opt.value === '') { opt.style.display = ''; return; }
        opt.style.display = (projectId === '' || opt.getAttribute('data-project-id') === projectId) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', filterCohorts);
</script>
@endpush