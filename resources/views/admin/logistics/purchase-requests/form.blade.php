@extends('admin.logistics.layouts.master')

@section('title', isset($purchaseRequest) ? 'تعديل طلب شراء' : 'إضافة طلب شراء')

@push('styles')
<style>
    .signature-canvas { border: 2px dashed #ccc; border-radius: 8px; cursor: crosshair; width: 100%; height: 200px; background: #fff; }
    .signature-tab-content { padding-top: 1rem; }
    .item-row td { vertical-align: middle; }
    .item-row .form-control, .item-row .form-select { font-size: 0.875rem; }
</style>
@endpush

@section('logistics-content')
<div class="page-header">
    <h4>{{ isset($purchaseRequest) ? 'تعديل طلب شراء' : 'إضافة طلب شراء' }}</h4>
    <p>
        <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="text-decoration-none">طلبات الشراء</a>
        / {{ isset($purchaseRequest) ? '#' . $purchaseRequest->id : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-card">
            <form method="POST"
                  action="{{ route('admin.logistics.purchase-requests.store') }}"
                  enctype="multipart/form-data">
                @csrf

                {{-- Items Table --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 fw-bold">بنود طلب الشراء <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                            <i class="bi bi-plus-lg me-1"></i> إضافة بند
                        </button>
                    </div>
                    @error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:35%">الوصف</th>
                                    <th style="width:10%">الكمية</th>
                                    <th style="width:10%">الوحدة</th>
                                    <th style="width:12%">سعر الوحدة</th>
                                    <th style="width:12%">الإجمالي</th>
                                    <th style="width:15%">ملاحظات</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                            </tbody>
                            <template id="itemTemplate">
                                <tr class="item-row">
                                    <td>
                                        <textarea class="form-control item-desc" rows="2" required></textarea>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-qty" value="1" min="1" required oninput="calcRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-unit" required placeholder="قطعة">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-price" value="0" min="0" step="0.01" required oninput="calcRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-total" value="0.00" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-notes" placeholder="اختياري">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-start fw-bold">الإجمالي الكلي:</td>
                                    <td class="text-start" id="grandTotal">0.00</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المركز <span class="text-danger">*</span></label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">— اختر المركز —</option>
                            @foreach ($centers ?? [] as $center)
                                <option value="{{ $center->id }}" {{ old('center_id', $purchaseRequest->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects ?? [] as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $purchaseRequest->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchaseRequest->notes ?? '') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Signature Section --}}
                <div class="mb-3">
                    <label class="form-label">التوقيع الإلكتروني</label>
                    <ul class="nav nav-tabs" id="signatureTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#uploadSignature" type="button">رفع صورة توقيع</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="draw-tab" data-bs-toggle="tab" data-bs-target="#drawSignature" type="button">رسم التوقيع</button>
                        </li>
                    </ul>
                    <div class="tab-content signature-tab-content">
                        <div class="tab-pane fade show active" id="uploadSignature">
                            <input type="file" name="signature_image"
                                   class="form-control @error('signature_image') is-invalid @enderror" accept="image/*">
                            @error('signature_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if (isset($purchaseRequest) && $purchaseRequest->signature_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $purchaseRequest->signature_path) }}" alt="التوقيع" style="max-height:80px;">
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane fade" id="drawSignature">
                            <canvas id="signatureCanvas" class="signature-canvas"></canvas>
                            <input type="hidden" name="signature_data_url" id="signatureDataUrl">
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSignature()">
                                    <i class="bi bi-eraser me-1"></i> مسح
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var itemIndex = 0;

    function addItem(data) {
        var template = document.getElementById('itemTemplate');
        var clone = template.content.cloneNode(true);
        var row = clone.querySelector('.item-row');

        row.querySelector('.item-desc').setAttribute('name', 'items[' + itemIndex + '][description]');
        row.querySelector('.item-qty').setAttribute('name', 'items[' + itemIndex + '][quantity]');
        row.querySelector('.item-unit').setAttribute('name', 'items[' + itemIndex + '][unit]');
        row.querySelector('.item-price').setAttribute('name', 'items[' + itemIndex + '][unit_price]');
        row.querySelector('.item-notes').setAttribute('name', 'items[' + itemIndex + '][notes]');

        if (data) {
            row.querySelector('.item-desc').value = data.description || '';
            row.querySelector('.item-qty').value = data.quantity || 1;
            row.querySelector('.item-unit').value = data.unit || '';
            row.querySelector('.item-price').value = data.unit_price || 0;
            row.querySelector('.item-notes').value = data.notes || '';
            calcRow(row.querySelector('.item-qty'));
        }

        document.getElementById('itemsBody').appendChild(clone);
        itemIndex++;
        calcGrandTotal();
    }

    function removeItem(btn) {
        var row = btn.closest('tr');
        if (document.querySelectorAll('#itemsBody .item-row').length <= 1) {
            alert('يجب أن يحتوي الطلب على بند واحد على الأقل');
            return;
        }
        row.remove();
        calcGrandTotal();
    }

    function calcRow(el) {
        var row = el.closest('tr');
        var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        var price = parseFloat(row.querySelector('.item-price').value) || 0;
        row.querySelector('.item-total').value = (qty * price).toFixed(2);
        calcGrandTotal();
    }

    function calcGrandTotal() {
        var total = 0;
        document.querySelectorAll('#itemsBody .item-row:not([style*="display:none"])').forEach(function (row) {
            total += parseFloat(row.querySelector('.item-total').value) || 0;
        });
        document.getElementById('grandTotal').textContent = total.toFixed(2);
    }

    // Add first row on load
    document.addEventListener('DOMContentLoaded', function () {
        addItem();

        // Signature canvas
        var canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var drawing = false;
        var rect = canvas.getBoundingClientRect();

        function getPos(e) {
            if (e.touches) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            }
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }

        canvas.addEventListener('mousedown', function (e) {
            drawing = true;
            var pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        });

        canvas.addEventListener('mousemove', function (e) {
            if (!drawing) return;
            var pos = getPos(e);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        });

        canvas.addEventListener('mouseup', function () {
            drawing = false;
            document.getElementById('signatureDataUrl').value = canvas.toDataURL();
        });

        canvas.addEventListener('mouseleave', function () {
            drawing = false;
        });
    });

    function clearSignature() {
        var canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signatureDataUrl').value = '';
    }
</script>
@endpush
