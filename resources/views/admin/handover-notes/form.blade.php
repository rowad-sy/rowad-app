@extends('admin.layouts.master')

@section('title', 'محضر تسليم جديد')

@section('content')
@php
    $pr = $purchaseRequest;
@endphp
<x-page-header :title="'محضر تسليم عهدة — للطلب '.$pr->request_number"
               description="سلّم المعدات المشتراة إلى المسؤول عنها — نموذج GRN قابل للطباعة والتصدير. يمكن تسجيل المستلم من المستخدمين أو كتابة اسمه يدوياً إن لم يكن لديه حساب بعد."
               :breadcrumb="[['label' => 'طلبات الشراء', 'url' => route('admin.logistics.purchase-requests.index')], ['label' => $pr->request_number, 'url' => route('admin.logistics.purchase-requests.show', $pr)], ['label' => 'محضر تسليم']]" />

<form method="POST" action="{{ route('admin.logistics.handover-notes.store', $pr) }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-card-checklist text-primary me-1" aria-hidden="true"></i> رأس المحضر</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم محضر التسليم / GRN No. <span class="text-danger">*</span></label>
                        <input type="text" name="grn_number" dir="ltr" class="form-control @error('grn_number') is-invalid @enderror"
                               value="{{ old('grn_number', $pr->request_number) }}" required>
                        @error('grn_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التسليم / Date <span class="text-danger">*</span></label>
                        <input type="date" name="handover_date" class="form-control @error('handover_date') is-invalid @enderror"
                               value="{{ old('handover_date', now()->toDateString()) }}" required>
                        @error('handover_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الطلب المرتبط / PR</label>
                        <input type="text" class="form-control" dir="ltr" readonly value="{{ $pr->request_number }}" tabindex="-1">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات على التسليم</label>
                        <textarea name="note" rows="2" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="form-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box-seam text-primary me-1" aria-hidden="true"></i> بنود العهدة المسلَّمة</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addHoRow()"><i class="bi bi-plus-lg me-1"></i> إضافة بند</button>
                </div>
                @error('items') <div class="alert alert-danger py-2 small">أضف بنداً واحداً على الأقل.</div> @enderror
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 ho-table">
                        <thead>
                            <tr>
                                <th style="width:36px">#</th>
                                <th>وصف البند / ITEM</th>
                                <th style="width:130px">رقم تسلسلي / Serial</th>
                                <th style="width:100px">الوحدة</th>
                                <th style="width:90px">الكمية</th>
                                <th style="width:120px">الحالة / Status</th>
                                <th style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="hoItems">
                            @foreach ($items as $i => $row)
                                <tr class="ho-row">
                                    <td class="num ho-n">{{ $loop->iteration }}</td>
                                    <td><input type="text" class="form-control form-control-sm @error('items.'.$i.'.description') is-invalid @enderror" required
                                               name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" dir="ltr" placeholder="—"
                                               name="items[{{ $i }}][serial]" value="{{ $row['serial'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}"></td>
                                    <td><input type="number" class="form-control form-control-sm" dir="ltr" min="0" step="any" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[{{ $i }}][status]" value="{{ $row['status'] ?? 'جيدة' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHoRow(this)" aria-label="حذف البند"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-arrow-down-up text-primary me-1" aria-hidden="true"></i> تم الاستلام من قبل</h6>
                <div class="mb-3">
                    <label class="form-label">مستخدم مسجل (اختياري)</label>
                    <select name="received_by_user_id" id="receiverSelect" class="user-picker form-select @error('received_by_user_id') is-invalid @enderror"
                            data-placeholder="ابحث عن الموظف...">
                        <option value="">— بدون حساب، سأكتب الاسم يدوياً —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-position="{{ $u->jobTitle?->title_ar ?? '' }}"
                                    @selected((int) old('received_by_user_id', 0) === $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('received_by_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">اختيار الموظف يملأ الاسم والصفة تلقائياً — ويمكن تعديلها.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">الاسم / Name <span class="text-danger">*</span></label>
                    <input type="text" name="received_by_name" id="receiverName" class="form-control @error('received_by_name') is-invalid @enderror"
                           value="{{ old('received_by_name') }}">
                    @error('received_by_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label">الصفة / Position</label>
                    <input type="text" name="received_by_position" id="receiverPosition" class="form-control" value="{{ old('received_by_position') }}">
                </div>
            </div>

            <div class="form-card mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> تم التسليم من قبل</h6>
                <div class="mb-3">
                    <label class="form-label">الاسم / Name <span class="text-danger">*</span></label>
                    <input type="text" name="delivered_by_name" class="form-control @error('delivered_by_name') is-invalid @enderror"
                           value="{{ $deliveredName }}" required>
                    @error('delivered_by_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label">الصفة / Position</label>
                    <input type="text" name="delivered_by_position" class="form-control" value="{{ $deliveredPosition }}">
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> حفظ محضر التسليم</button>
                <a href="{{ route('admin.logistics.purchase-requests.show', $pr) }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let hoIndex = {{ count($items) }};

function hoTemplate() {
    return {!! json_encode('<tr class="ho-row"><td class="num ho-n">__N__</td><td><input type="text" class="form-control form-control-sm" required name="items[__I__][description]" value=""></td><td><input type="text" class="form-control form-control-sm" dir="ltr" placeholder="—" name="items[__I__][serial]" value=""></td><td><input type="text" class="form-control form-control-sm" name="items[__I__][unit]" value=""></td><td><input type="number" class="form-control form-control-sm" dir="ltr" min="0" step="any" name="items[__I__][quantity]" value=""></td><td><input type="text" class="form-control form-control-sm" name="items[__I__][status]" value="جيدة"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHoRow(this)" aria-label="حذف البند"><i class="bi bi-x-lg"></i></button></td></tr>') !!};
}

function renumberHo() {
    document.querySelectorAll('#hoItems .ho-row').forEach(function (tr, i) {
        tr.querySelector('.ho-n').textContent = i + 1;
    });
}

function addHoRow() {
    const html = hoTemplate().replace(/__I__/g, hoIndex).replace('__N__', hoIndex + 1);
    hoIndex++;
    document.getElementById('hoItems').insertAdjacentHTML('beforeend', html);
    renumberHo();
}

function removeHoRow(btn) {
    btn.closest('tr').remove();
    renumberHo();
}

// مزامنة المستلم: اختيار موظف يملأ الاسم والصفة (قابلان للتعديل)
const receiverSelect = document.getElementById('receiverSelect');
receiverSelect.addEventListener('change', function () {
    const opt = receiverSelect.selectedOptions[0];
    const nameEl = document.getElementById('receiverName');
    const posEl = document.getElementById('receiverPosition');
    if (opt && opt.dataset.name) {
        nameEl.value = opt.dataset.name;
        if (!posEl.value && opt.dataset.position) posEl.value = opt.dataset.position;
    }
});
</script>
@endpush
