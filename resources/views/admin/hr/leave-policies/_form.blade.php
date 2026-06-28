@php
    $prefix = isset($edit) && $edit ? 'edit_' : '';
@endphp

<div class="mb-3">
    <label class="form-label">الاسم <span class="text-danger">*</span></label>
    <input type="text" name="name_ar" id="{{ $prefix }}name_ar" class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">الأيام السنوية <span class="text-danger">*</span></label>
    <input type="number" name="annual_days" id="{{ $prefix }}annual_days" class="form-control" min="0" required>
</div>

<div class="mb-3">
    <label class="form-label">اللون <span class="text-danger">*</span></label>
    <input type="color" name="color" id="{{ $prefix }}color" class="form-control form-control-color" value="#0d6efd" required>
</div>

<div class="mb-3">
    <label class="form-label">الأيقونة <span class="text-danger">*</span></label>
    <input type="text" name="icon" id="{{ $prefix }}icon" class="form-control" placeholder="bi-sun" required>
    <small class="text-muted">اختر من <a href="https://icons.getbootstrap.com" target="_blank">Bootstrap Icons</a></small>
</div>

<div class="mb-3 form-check form-switch">
    <input type="hidden" name="requires_approval" value="0">
    <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="{{ $prefix }}requires_approval" checked>
    <label class="form-check-label" for="{{ $prefix }}requires_approval">يتطلب موافقة</label>
</div>

<div class="mb-3 form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="{{ $prefix }}is_active" checked>
    <label class="form-check-label" for="{{ $prefix }}is_active">نشط</label>
</div>

<div class="mb-3" id="{{ $prefix }}approvers-section">
    <label class="form-label">الموافقات (اختر من يمكنه الموافقة)</label>
    <div class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
        @forelse ($users ?? [] as $user)
            <div class="form-check">
                <input type="checkbox" name="approver_ids[]" value="{{ $user->id }}"
                       class="form-check-input approver-checkbox" id="{{ $prefix }}approver_{{ $user->id }}">
                <label class="form-check-label" for="{{ $prefix }}approver_{{ $user->id }}">{{ $user->name }}</label>
            </div>
        @empty
            <p class="text-muted small mb-0">لا يوجد موظفين لعرضهم</p>
        @endforelse
    </div>
</div>
