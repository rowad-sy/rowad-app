@extends('admin.layouts.master')

@php
    $formType = isset($purchaseRequest)
        ? ($purchaseRequest->request_type ?: 'purchase')
        : ($requestType ?? 'purchase');
    $typeLabel = $formType === 'maintenance' ? 'صيانة' : 'شراء';
@endphp

@section('title', (isset($purchaseRequest) ? 'تعديل طلب ' : 'طلب ').$typeLabel.(isset($purchaseRequest) ? '' : ' جديد'))

@section('content')
@php $pr = $purchaseRequest ?? null; @endphp
<x-page-header :title="$pr ? 'تعديل طلب '.$typeLabel.' '.$pr->request_number : 'طلب '.$typeLabel.' جديد'"
               :description="'يعبّئه مدير المشروع كاملاً (بنود بأسعار وعملات) ويحيله للموافقة — التسلسل: المدير المباشر ← المالية ← التنفيذي ← اللوجستي.'"
               :breadcrumb="[['label' => 'طلبات الشراء والصيانة', 'url' => route('admin.logistics.purchase-requests.index', ['type' => $formType])], ['label' => $pr ? 'تعديل' : 'جديد']]" />

<div class="row">
    <div class="col-lg-10">
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $pr ? route('admin.logistics.purchase-requests.update', $pr) : route('admin.logistics.purchase-requests.store') }}">
                @csrf
                @if ($pr) @method('PUT') @endif
                <input type="hidden" name="request_type" value="{{ $formType }}">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">رقم الطلب <span class="text-danger">*</span></label>
                        <input type="text" name="request_number" dir="ltr" class="form-control text-start @error('request_number') is-invalid @enderror"
                               value="{{ old('request_number', $pr?->request_number) }}" required>
                        @error('request_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الطلب <span class="text-danger">*</span></label>
                        <input type="date" name="pr_date" class="form-control @error('pr_date') is-invalid @enderror"
                               value="{{ old('pr_date', optional($pr?->pr_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                        @error('pr_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">محدد تلقائياً باليوم — عدّله إن أردت.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ التنفيذ المطلوب <span class="text-danger">*</span></label>
                        <input type="date" name="required_date" class="form-control @error('required_date') is-invalid @enderror"
                               value="{{ old('required_date', optional($pr?->required_date)->format('Y-m-d')) }}" required>
                        @error('required_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الإحالة للموافقة الأولى <span class="text-danger">*</span></label>
                        <select name="refer_to_approver1_id" class="user-picker @error('refer_to_approver1_id') is-invalid @enderror"
                                data-placeholder="ابحث عن المستخدم..." required>
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('refer_to_approver1_id', $pr?->refer_to_approver1_id ?? $defaultApproverId ?? 0) === $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('refer_to_approver1_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">عادةً يكون مدير المشاريع — قابل للاختيار لأي مستخدم.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">المكتب (المركز) <span class="text-danger">*</span></label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror" required>
                            <option value="">— اختر —</option>
                            @foreach ($centers as $c)
                                <option value="{{ $c->id }}" data-code="{{ $c->code }}" {{ (int) old('center_id', $pr?->center_id ?? $employee?->center_id) === $c->id ? 'selected' : '' }}>
                                    {{ $c->code ? $c->code.' - ' : '' }}{{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" id="projectSelect" class="form-select @error('project_id') is-invalid @enderror" required>
                            <option value="">— اختر —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" data-code="{{ $p->code }}" {{ (int) old('project_id', $pr?->project_id ?? $employee?->project_id) === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">كود المشروع (تلقائي)</label>
                        <input type="text" id="projectCodeDisplay" class="form-control" dir="ltr" readonly tabindex="-1"
                               value="{{ old('project_code_display', $pr?->project?->code ?? '') }}" placeholder="يُجلب من المشروع">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">الإدارة / القسم / الوحدة <span class="text-danger">*</span></label>
                        @if ($departments->isNotEmpty())
                            <select id="deptSelect" class="form-select">
                                <option value="">— اختر —</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->name_ar }}">{{ $d->name_ar }}</option>
                                @endforeach
                                <option value="__manual">أخرى — إدخال يدوي</option>
                            </select>
                            <input type="text" name="management_unit" id="manualUnit" class="form-control mt-2 @error('management_unit') is-invalid @enderror"
                                   placeholder="اكتب اسم الإدارة يدوياً" value="{{ old('management_unit', $pr?->management_unit) }}" required>
                            @error('management_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @else
                            <input type="text" name="management_unit" class="form-control @error('management_unit') is-invalid @enderror"
                                   value="{{ old('management_unit', $pr?->management_unit) }}" placeholder="مثال: إدارة المشاريع" required>
                            @error('management_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">توقيعك كطالب للطلب (صورة) <span class="text-danger">* إلزامي</span></label>
                        @unless($pr)
                        <input type="file" name="signature_image" accept="image/png,image/jpeg" class="form-control @error('signature_image') is-invalid @enderror" required>
                        @error('signature_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @else
                        <div class="form-control-plaintext text-muted small">
                            @if ($pr->signatures->firstWhere('role', 'requested_by')?->signature_path)
                                <img src="{{ asset('storage/'.$pr->signatures->firstWhere('role', 'requested_by')->signature_path) }}" alt="التوقيع" style="height:44px">
                            @else
                                التوقيع مسجّل مسبقاً.
                            @endif
                        </div>
                        @endunless
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات عامة <span class="text-danger">*</span></label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" required>{{ old('notes', $pr?->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">بنود الطلب</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPrItemRow()">
                        <i class="bi bi-plus-lg me-1"></i> إضافة بند
                    </button>
                </div>

                @error('items') <div class="alert alert-danger py-2 small">الطلب يحتاج بنداً واحداً على الأقل.</div> @enderror

                <div id="prItemsContainer">
                    @php
                        $oldItems = old('items');
                        if ($pr) {
                            $renderItems = $pr->items->mapWithKeys(fn ($row, $i) => [$i => $row]);
                            $maxItemKey = $renderItems->keys()->max() ?? -1;
                        } elseif (is_array($oldItems) && count($oldItems)) {
                            // نحفظ المفاتيح الأصلية للبنود المُدخلة بعد فشل الحفظ (items[3][...], items[7][...])
                            $renderItems = collect($oldItems)->filter(fn ($r) => is_array($r) || $r === null);
                            $maxItemKey = $renderItems->keys()->map(fn ($k) => (int) $k)->max() ?? -1;
                        } else {
                            $renderItems = collect([0 => null]);
                            $maxItemKey = 0;
                        }
                    @endphp
                    @foreach ($renderItems as $rowKey => $item)
                        @include('admin.logistics.purchase-requests._item_row', ['item' => $item, 'index' => $rowKey])
                    @endforeach
                </div>

                <div class="alert alert-light border small mt-2 mb-3 d-flex justify-content-between">
                    <span>الإجمالي التقديري:</span>
                    <span><strong class="ltr-cell" id="grandUsd">0.00 $</strong> &nbsp; <strong class="ltr-cell" id="grandSyp">0.00 SYP</strong></span>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> {{ $pr ? 'حفظ التعديلات' : 'إنشاء وإحالة للموافقة' }}
                    </button>
                    <a href="{{ $pr ? route('admin.logistics.purchase-requests.show', $pr) : route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let prItemIndex = {{ $maxItemKey + 1 }};

function prItemTemplate() {
    return {!! json_encode(view('admin.logistics.purchase-requests._item_row', ['item' => null, 'index' => '__INDEX__'])->render()) !!};
}

function addPrItemRow() {
    const container = document.getElementById('prItemsContainer');
    const html = prItemTemplate().replace(/__INDEX__/g, prItemIndex++);
    container.insertAdjacentHTML('beforeend', html);
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    recalcPrTotals();
}

function removePrItemRow(btn) {
    btn.closest('.pr-item-card').remove();
    recalcPrTotals();
}

function recalcPrTotals() {
    let usd = 0, syp = 0;
    document.querySelectorAll('.pr-item-card').forEach(card => {
        const qty = parseFloat(card.querySelector('[data-qty]').value) || 0;
        const price = parseFloat(card.querySelector('[data-price]').value) || 0;
        const total = qty * price;
        const cell = card.querySelector('[data-rowtotal]');
        if (cell) cell.value = total.toFixed(2);
        if (card.querySelector('[data-currency]').value === 'USD') usd += total; else syp += total;
    });
    document.getElementById('grandUsd').textContent = usd.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' $';
    document.getElementById('grandSyp').textContent = syp.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SYP';
}

document.addEventListener('input', e => {
    if (e.target.closest('.pr-item-card')) recalcPrTotals();
});

const projectSelect = document.getElementById('projectSelect');
function syncProjectCode() {
    const opt = projectSelect.selectedOptions[0];
    document.getElementById('projectCodeDisplay').value = opt && opt.dataset.code ? opt.dataset.code : '';
}
projectSelect.addEventListener('change', syncProjectCode);
if (projectSelect.value) syncProjectCode();

const deptSelect = document.getElementById('deptSelect');
if (deptSelect) {
    const manualUnit = document.getElementById('manualUnit');
    // مزامنة: اختيار إدارة يكتبها في الحقل؛ «أخرى» تفرّغه للكتابة اليدوية
    const current = manualUnit.value;
    if (current && [...deptSelect.options].some(o => o.value === current)) deptSelect.value = current;
    else if (current) deptSelect.value = '__manual';
    deptSelect.addEventListener('change', () => {
        if (deptSelect.value === '' || deptSelect.value === '__manual') {
            manualUnit.value = deptSelect.value === '' ? manualUnit.value : '';
            manualUnit.focus();
        } else {
            manualUnit.value = deptSelect.value;
        }
    });
}

recalcPrTotals();
</script>
@endpush
