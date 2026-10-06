@extends('admin.layouts.master')

@section('title', isset($permission) ? 'تعديل الصلاحيات' : 'إضافة صلاحيات')

@push('styles')
<style>
    .grant-step-head { display: flex; align-items: center; gap: .5rem; font-size: 1.02rem; margin-bottom: .9rem; }
    .grant-step-head .step-num { display: inline-flex; width: 26px; height: 26px; border-radius: 50%;
        background: var(--bs-primary, #0d6efd); color: #fff; align-items: center; justify-content: center; font-size: .85rem; font-weight: 700; }

    .assign-options { display: flex; gap: .55rem; flex-wrap: wrap; }
    .assign-opt { display: inline-flex; align-items: center; gap: .5rem; border: 2px solid #d7e0ef; border-radius: 12px;
        padding: .6rem 1.25rem; cursor: pointer; font-weight: 700; font-size: .95rem; user-select: none; transition: .15s; }
    .assign-opt input[type="radio"] { width: 1.2em; height: 1.2em; margin: 0; accent-color: var(--bs-primary, #0d6efd); cursor: pointer; }
    .assign-opt:hover { border-color: #b9c7e2; }
    .assign-opt.sel { border-color: var(--bs-primary, #0d6efd); background: #eef4ff; color: #0b5ed7; }

    .model-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .65rem;
        border: 1.5px solid #e2e8f4; border-radius: 12px; padding: .7rem .95rem; margin-bottom: .6rem; }
    .model-row .model-name { font-size: .95rem; font-weight: 700; min-width: 170px; }
    .model-row .model-tech { font-family: Consolas, monospace; direction: ltr; font-size: .7rem; color: #8a97ad; display: block; font-weight: 400; }
    .model-flags { display: flex; gap: .45rem; flex-wrap: wrap; align-items: center; }

    .flag-pill { display: inline-flex; align-items: center; border: 1.5px solid #e2e8f4; border-radius: 10px;
        padding: 0; font-size: .9rem; font-weight: 600; user-select: none; transition: .12s; }
    .flag-pill:hover { border-color: #b9c7e2; }
    .flag-pill input[type="checkbox"] { width: 1.25em; height: 1.25em; margin: .55rem 0 .55rem .65rem; cursor: pointer; flex-shrink: 0; }
    .flag-pill label { padding: .5rem .8rem .5rem .4rem; cursor: pointer; margin: 0; display: inline-flex; align-items: center; }
    .flag-pill.f-can_view.on   { border-color: #0ea5e9; background: #e5f6fd; color: #076588; }
    .flag-pill.f-can_create.on { border-color: #2e9e5b; background: #e7f7ee; color: #1b6338; }
    .flag-pill.f-can_edit.on   { border-color: #d99400; background: #fdf3dc; color: #8a5d00; }
    .flag-pill.f-can_delete.on { border-color: #e05065; background: #fde9ed; color: #a02038; }

    .cat-section { margin-bottom: 1.25rem; }
    .cat-head-bar { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        background: var(--bs-primary-bg-subtle, #eef3fb); border-radius: 10px; padding: .5rem .85rem; margin-bottom: .6rem; }
    .cat-head-bar h6 { margin: 0; font-size: .95rem; }

    #grid-summary { min-height: 22px; }
    .grant-actions-bar { position: sticky; bottom: 0; z-index: 5; }
</style>
@endpush

@php
    $defaultAssign = old('rows.0.assign_to', $rows[0]['assign_to'] ?? 'user');
    $defaultUserId = old('rows.0.user_id', $rows[0]['user_id'] ?? '');
    $defaultGroupId = old('rows.0.group_id', $rows[0]['group_id'] ?? '');
    $prePerms = old('perms') ?? ($rows[0]['perms'] ?? []);
    $flags = ['can_view' => 'عرض', 'can_create' => 'إضافة', 'can_edit' => 'تعديل', 'can_delete' => 'حذف'];
    $legacyScope = isset($permission)
        && ($permission->center_id !== null || $permission->project_id !== null || $permission->cohort_id !== null);
@endphp

@section('content')
<x-page-header :title="isset($permission) ? 'تعديل الصلاحيات' : 'إضافة صلاحيات'"
               :breadcrumb="[['label' => 'الصلاحيات', 'url' => route('admin.permissions.index')], ['label' => isset($permission) ? 'تعديل' : 'جديد']]" />

@if ($legacyScope)
    <div class="alert alert-warning py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        هذا التعيين القديم يحمل نطاقاً مدمجاً ({{ collect(['المركز' => $permission->center?->name, 'المشروع' => $permission->project?->name, 'الفوج' => $permission->cohort?->name])->filter()->implode(' · ') ?: '—' }}) —
        ما زال فاعلاً؛ ننصح بنقل التقييد إلى <a href="{{ route('admin.roles.index') }}">إسناد دور بنطاق</a>.
    </div>
@endif

<form method="POST"
      action="{{ isset($permission) ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">
    @csrf
    @if (isset($permission))
        @method('PUT')
    @endif

    {{-- ═══ الخطوة ١: الجهة ═══ --}}
    <div class="form-card mb-3">
        <div class="grant-step-head"><span class="step-num">١</span> لمن تُمنح الصلاحية؟</div>

        <div class="row g-3 align-items-start">
            <div class="col-lg-5">
                <div class="assign-options" id="assign_options">
                    <label class="assign-opt">
                        <input type="radio" name="rows[0][assign_to]" value="user" onchange="applyAssign()" {{ $defaultAssign === 'user' ? 'checked' : '' }}>
                        <i class="bi bi-person" aria-hidden="true"></i> مستخدم
                    </label>
                    <label class="assign-opt">
                        <input type="radio" name="rows[0][assign_to]" value="role" onchange="applyAssign()" {{ $defaultAssign === 'role' ? 'checked' : '' }}>
                        <i class="bi bi-briefcase" aria-hidden="true"></i> دور
                    </label>
                    <label class="assign-opt">
                        <input type="radio" name="rows[0][assign_to]" value="group" onchange="applyAssign()" {{ $defaultAssign === 'group' ? 'checked' : '' }}>
                        <i class="bi bi-people" aria-hidden="true"></i> مجموعة
                    </label>
                </div>
                <div class="small text-muted mt-2">
                    دور للوظائف (يُعاد استخدامه مع كل شخص بنطاقه) · مجموعة لحزمة مشتركة · مستخدم للاستثناء الفردي.
                </div>
            </div>

            <div class="col-lg-7">
                <div data-field="user" style="{{ $defaultAssign === 'user' ? '' : 'display:none;' }}">
                    <label class="form-label">المستخدم</label>
                    <select name="rows[0][user_id]" class="form-select form-select-lg @error('rows.0.user_id') is-invalid @enderror">
                        <option value="">— اختر مستخدماً —</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $defaultUserId === $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('rows.0.user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">المنح المباشر للمستخدم يسري على كل المنظمة — استخدمه للاستثناءات الفردية فقط.</div>
                </div>

                <div data-field="group" style="{{ $defaultAssign === 'user' ? 'display:none;' : '' }}">
                    <label class="form-label" id="group_select_label">{{ $defaultAssign === 'group' ? 'المجموعة' : 'الدور' }}</label>
                    <select name="rows[0][group_id]" id="grant_group_select"
                            class="form-select form-select-lg @error('rows.0.group_id') is-invalid @enderror"
                            data-selected="{{ $defaultGroupId }}">
                        <option value="">{{ $defaultAssign === 'group' ? '— اختر مجموعة —' : '— اختر دوراً —' }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" data-kind="{{ $group->kind }}" @selected((int) $defaultGroupId === $group->id)>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('rows.0.group_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    @if ($groups->where('kind', 'role')->isEmpty())
                        <div class="form-text text-warning mt-1">
                            لا توجد أدوار بعد — <a href="{{ route('admin.roles.create') }}">عرّف دوراً أولاً</a>
                            (المنح عبر الأدوار أسهل: تعريف واحد يُعاد مع كل شخص بنطاقه).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ الخطوة ٢: الصلاحيات ═══ --}}
    <div class="form-card">
        <div class="grant-step-head"><span class="step-num">٢</span> ما الذي تريد السماح به؟ <small class="text-muted fw-normal">(علّر كل صلاحية تريد منحها — النطاق يُحدَّد لاحقاً في شاشة الأدوار)</small></div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" class="form-control" id="model_search" placeholder="بحث عن موديل أو صفحة..." oninput="filterModels(this.value)">
            </div>
            <button type="button" class="btn btn-sm btn-success" onclick="setAllFlags(true)">
                <i class="bi bi-check2-square me-1" aria-hidden="true"></i> تحديد الكل
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAllFlags(false)">
                <i class="bi bi-x-square me-1" aria-hidden="true"></i> إلغاء الكل
            </button>
            <div class="ms-auto small fw-bold text-muted" id="grid-summary" aria-live="polite"></div>
        </div>

        <div class="row g-2 mb-3 small text-muted">
            <div class="col-auto"><span class="badge text-bg-info">عرض</span> فتح الصفحة ورؤية القائمة</div>
            <div class="col-auto"><span class="badge text-bg-success">إضافة</span> إنشاء سجلات جديدة</div>
            <div class="col-auto"><span class="badge text-bg-warning">تعديل</span> تغيير سجل موجود</div>
            <div class="col-auto"><span class="badge text-bg-danger">حذف</span> إزالة سجل</div>
        </div>

        @foreach ($modelGroups as $category => $models)
            @php $catIndex = $loop->index; @endphp
            <div class="cat-section" data-cat-section="{{ $catIndex }}">
                <div class="cat-head-bar">
                    <h6><i class="bi bi-collection me-1" aria-hidden="true"></i>{{ $category }}</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-success" onclick="setCatAll({{ $catIndex }}, true)">تحديد الفئة</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setCatAll({{ $catIndex }}, false)">إلغاء</button>
                    </div>
                </div>

                @foreach ($models as $model => $label)
                    @php $modelKey = $modelKeys[$model] ?? null; @endphp
                    @if ($modelKey !== null)
                        <div class="model-row" data-cat="{{ $catIndex }}" data-model-key="{{ $modelKey }}"
                             data-search="{{ $label }} {{ class_basename($model) }} {{ $model }}">
                            <div class="model-name">
                                {{ $label }}
                                <span class="model-tech">{{ class_basename($model) }}</span>
                            </div>
                            <div class="model-flags">
                                @foreach ($flags as $flag => $flagLabel)
                                    <div class="flag-pill f-{{ $flag }} {{ ($prePerms[$modelKey][$flag] ?? false) ? 'on' : '' }}">
                                        <input type="checkbox" class="form-check-input"
                                               id="perm_{{ $modelKey }}_{{ $flag }}"
                                               aria-label="{{ $label }} — {{ $flagLabel }}"
                                               name="perms[0][{{ $modelKey }}][{{ $flag }}]"
                                               value="1" data-cat="{{ $catIndex }}" data-row="0"
                                               {{ ($prePerms[$modelKey][$flag] ?? false) ? 'checked' : '' }}>
                                        <label for="perm_{{ $modelKey }}_{{ $flag }}">{{ $flagLabel }}</label>
                                    </div>
                                @endforeach
                                <div class="btn-group btn-group-sm ms-1" role="group" aria-label="تحديد موديل {{ $label }}">
                                    <button type="button" class="btn btn-outline-primary py-1 px-2" title="تحديد هذا الموديل كاملاً"
                                            onclick="setRowAll(this, true)">الكل</button>
                                    <button type="button" class="btn btn-outline-secondary py-1 px-2" title="إلغاء هذا الموديل"
                                            onclick="setRowAll(this, false)">لا</button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach

        @error('rows')
            <div class="alert alert-danger py-2 mt-2 mb-2">{{ $message }}</div>
        @enderror
        @error('rows.0.user_id')
            <div class="alert alert-danger py-2 mt-2 mb-2">{{ $message }}</div>
        @enderror
        @error('rows.0.group_id')
            <div class="alert alert-danger py-2 mt-2 mb-2">{{ $message }}</div>
        @enderror
    </div>

    {{-- ═══ شريط الحفظ ═══ --}}
    <div class="form-card grant-actions-bar mt-3 py-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="submit" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ الصلاحيات
            </button>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            <span class="small text-muted ms-auto">لن تُحفظ أي خانة غير معلَّمة — والموديلات الجديدة تُضاف للكتالوج من كود المشروع.</span>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function currentAssign() {
    const checked = document.querySelector('input[name="rows[0][assign_to]"]:checked');
    return checked ? checked.value : 'user';
}

function applyAssign() {
    const kind = currentAssign();

    document.querySelectorAll('#assign_options .assign-opt').forEach(function (l) {
        l.classList.toggle('sel', l.querySelector('input').value === kind && l.querySelector('input').checked);
    });

    document.querySelector('[data-field="user"]').style.display = kind === 'user' ? '' : 'none';
    document.querySelector('[data-field="group"]').style.display = kind === 'user' ? 'none' : '';
    document.getElementById('group_select_label').textContent = kind === 'group' ? 'المجموعة' : 'الدور';

    const sel = document.getElementById('grant_group_select');
    sel.options[0].textContent = kind === 'group' ? '— اختر مجموعة —' : '— اختر دوراً —';

    let current = sel.value;
    Array.from(sel.options).forEach(function (o) {
        if (!o.value) return;
        const show = o.dataset.kind === kind;
        o.hidden = !show;
        o.disabled = !show;
    });
    if (current) {
        const active = Array.from(sel.options).find(o => o.value === current && !o.disabled);
        sel.value = active ? current : '';
    }
    updateSummary();
}

function refreshPills() {
    document.querySelectorAll('.flag-pill').forEach(function (pill) {
        pill.classList.toggle('on', pill.querySelector('input[type="checkbox"]').checked);
    });
}

function setRowAll(button, select) {
    button.closest('.model-row').querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = select; });
    refreshPills();
    updateSummary();
}

function setCatAll(catIndex, select) {
    document.querySelectorAll('.model-row[data-cat="' + catIndex + '"] input[type="checkbox"]').forEach(function (cb) { cb.checked = select; });
    refreshPills();
    updateSummary();
}

function setAllFlags(select) {
    document.querySelectorAll('.model-row input[type="checkbox"]').forEach(function (cb) { cb.checked = select; });
    refreshPills();
    updateSummary();
}

function filterModels(query) {
    query = query.trim().toLowerCase();
    document.querySelectorAll('.model-row').forEach(function (row) {
        row.style.display = (!query || row.dataset.search.toLowerCase().includes(query)) ? '' : 'none';
    });
    document.querySelectorAll('[data-cat-section]').forEach(function (section) {
        const visible = Array.from(section.querySelectorAll('.model-row')).some(r => r.style.display !== 'none');
        section.style.display = visible ? '' : 'none';
    });
}

function updateSummary() {
    const summary = document.getElementById('grid-summary');
    if (!summary) return;
    const checkedBoxes = document.querySelectorAll('.model-row input[type="checkbox"]:checked');
    const models = new Set();
    checkedBoxes.forEach(function (cb) {
        const row = cb.closest('.model-row');
        if (row) models.add(row.dataset.modelKey);
    });
    summary.textContent = models.size > 0
        ? 'تم تحديد ' + models.size + ' موديلاً (' + checkedBoxes.length + ' صلاحية)'
        : 'لم تُحدد أي صلاحية بعد';
    summary.classList.toggle('text-success', models.size > 0);
    summary.classList.toggle('text-muted', models.size === 0);
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('model_search')?.closest('form')
        .addEventListener('change', function (e) {
            if (e.target.matches('.flag-pill input[type="checkbox"]')) { refreshPills(); updateSummary(); }
        });
    applyAssign();
});
</script>
@endpush
