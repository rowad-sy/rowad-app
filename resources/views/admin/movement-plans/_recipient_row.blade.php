@php
    $i = $index ?? 0;
@endphp
<div class="recipient-row border rounded p-2 mb-2 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;left:8px"
            onclick="this.closest('.recipient-row').remove()" title="حذف المتابِع">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="row g-2">
        <div class="col-md-8">
            <label class="form-label small">المتابِع <span class="text-danger">*</span></label>
            <select name="user_ids[]" class="form-select form-select-sm" required>
                <option value="">— اختر المستخدم —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">الدور</label>
            <input type="text" name="role_labels[]" class="form-control form-control-sm"
                   placeholder="سائق / مدير مركز / لوجستي">
        </div>
    </div>
</div>