@extends('admin.layouts.master')

@section('title', isset($design) ? 'تعديل التصميم' : 'مصمم الشهادة')

@push('styles')
<style>
.designer-layout { display: flex; gap: 1rem; height: calc(100vh - 200px); min-height: 500px; }
.designer-sidebar { width: 200px; flex-shrink: 0; display: flex; flex-direction: column; gap: 0.5rem; }
.designer-sidebar .field-btn { padding: 0.5rem; border: 1px dashed #94a3b8; border-radius: 8px; cursor: grab; text-align: center; font-size: 0.8rem; background: #f8fafc; transition: all 0.15s; }
.designer-sidebar .field-btn:hover { border-color: #0d6efd; background: #eef2ff; }
.designer-sidebar .field-btn:active { cursor: grabbing; }
.designer-canvas-wrap { flex: 1; display: flex; align-items: flex-start; justify-content: center; background: #e2e8f0; border-radius: 12px; padding: 1.5rem; overflow: auto; }
.designer-canvas {
    width: 100%; max-width: 800px;
    aspect-ratio: 297 / 210;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.12);
    position: relative;
    border-radius: 4px;
    overflow: hidden;
}
.designer-canvas .canvas-field {
    position: absolute;
    cursor: move;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px;
    border: 1px dashed transparent;
    font-family: 'Tajawal', sans-serif;
    user-select: none;
    min-width: 20px;
    min-height: 16px;
}
.designer-canvas .canvas-field:hover { border-color: #94a3b8; background: rgba(13,110,253,0.03); }
.designer-canvas .canvas-field.selected { border-color: #0d6efd; background: rgba(13,110,253,0.06); }
.designer-canvas .canvas-field .resize-handle {
    position: absolute;
    width: 10px; height: 10px;
    background: #0d6efd;
    border: 2px solid #fff;
    border-radius: 2px;
    display: none;
}
.designer-canvas .canvas-field.selected .resize-handle { display: block; }
.resize-handle.se { bottom: -5px; right: -5px; cursor: se-resize; }
.resize-handle.sw { bottom: -5px; left: -5px; cursor: sw-resize; }
.resize-handle.ne { top: -5px; right: -5px; cursor: ne-resize; }
.resize-handle.nw { top: -5px; left: -5px; cursor: nw-resize; }

.designer-props { width: 260px; flex-shrink: 0; }
.designer-props .prop-group { margin-bottom: 0.75rem; }
.designer-props .prop-group label { font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 2px; }
.designer-props .prop-group input,
.designer-props .prop-group select { width: 100%; padding: 4px 8px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; }
.designer-props .prop-group input[type="color"] { height: 32px; padding: 2px; }
</style>
@endpush

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ isset($design) ? 'تعديل التصميم: ' . $design->name : 'مصمم الشهادة' }}</h4>
        <p>اسحب الحقول وأفلتها على الشهادة</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success" onclick="saveDesign()">
            <i class="bi bi-check-lg me-1"></i> حفظ التصميم
        </button>
        <a href="{{ route('admin.students.certificates.designs') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</div>

<form id="designForm" method="POST" enctype="multipart/form-data"
      action="{{ isset($design) ? route('admin.students.certificates.designs.update', $design) : route('admin.students.certificates.designs.store') }}">
    @csrf
    @if (isset($design)) @method('PUT') @endif

    <input type="hidden" name="fields_config" id="fieldsConfig">
    @foreach ((array)($studentIds ?? []) as $sid)
        <input type="hidden" name="student_ids[]" value="{{ $sid }}">
    @endforeach
    <input type="hidden" name="course_id" value="{{ $courseId ?? '' }}">

    <div class="designer-layout">
        {{-- Sidebar: Fields --}}
        <div class="designer-sidebar">
            <div class="p-2 border-bottom"><strong>الحقول</strong></div>
            @foreach ([
                ['type' => 'student_name', 'icon' => 'bi-person', 'label' => 'اسم الطالب'],
                ['type' => 'student_code', 'icon' => 'bi-upc-scan', 'label' => 'كود الطالب'],
                ['type' => 'course_name', 'icon' => 'bi-book', 'label' => 'اسم المقرر'],
                ['type' => 'period_name', 'icon' => 'bi-calendar-range', 'label' => 'الفترة'],
                ['type' => 'certificate_number', 'icon' => 'bi-hash', 'label' => 'رقم الشهادة'],
                ['type' => 'issue_date', 'icon' => 'bi-calendar', 'label' => 'تاريخ الإصدار'],
                ['type' => 'barcode', 'icon' => 'bi-upc-scan', 'label' => 'باركود'],
                ['type' => 'text', 'icon' => 'bi-font', 'label' => 'نص حر'],
            ] as $ft)
                <div class="field-btn" data-type="{{ $ft['type'] }}" onclick="addField('{{ $ft['type'] }}')">
                    <i class="bi {{ $ft['icon'] }} d-block fs-5"></i>
                    <span>{{ $ft['label'] }}</span>
                </div>
            @endforeach

            <hr>
            <div class="p-2 border-bottom"><strong>الخلفية</strong></div>
            <div class="mb-2">
                <input type="file" name="template_image" accept="image/jpeg,image/png" class="form-control form-control-sm"
                       onchange="previewBackground(event)">
            </div>
            @if (isset($design) && $design->template_image)
                <div class="small text-muted">موجود: {{ basename($design->template_image) }}</div>
                <label class="small">
                    <input type="checkbox" name="remove_template" value="1"> إزالة الخلفية
                </label>
            @endif
        </div>

        {{-- Canvas --}}
        <div class="designer-canvas-wrap">
            <div class="designer-canvas" id="canvas" onclick="deselectField(event)">
                @if (isset($design) && $design->template_image)
                    <img src="{{ asset('storage/' . $design->template_image) }}" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:fill;pointer-events:none;">
                @endif
            </div>
        </div>

        {{-- Properties panel --}}
        <div class="designer-props" id="propsPanel">
            <div class="p-2 border-bottom"><strong>الخصائص</strong></div>
            <div class="p-2" id="propsContent">
                <p class="text-muted small">اختر حقلاً لعرض خصائصه</p>
            </div>
            <div class="p-2 border-top mt-auto">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="deleteSelectedField()" id="deleteFieldBtn" style="display:none;">
                    <i class="bi bi-trash me-1"></i> حذف الحقل
                </button>
            </div>
        </div>
    </div>

    {{-- Design info --}}
    <div class="row g-2 mt-3 p-3 bg-light rounded">
        <div class="col-md-4">
            <label class="form-label small">اسم التصميم <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $design->name ?? '') }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">المقرر المرتبط</label>
            <select name="course_id" class="form-select">
                <option value="">عام</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" {{ old('course_id', $design->course_id ?? $courseId ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">السنة <span class="text-danger">*</span></label>
            <input type="number" name="year" class="form-control" value="{{ old('year', $design->year ?? date('Y')) }}" min="2000" max="2100" required>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// ─── State ───
let fields = [];
let selectedId = null;
let nextId = 1;
let dragTarget = null;
let dragOffsetX = 0, dragOffsetY = 0;
let resizeTarget = null;
let resizeHandle = null;
let resizeStartX, resizeStartY, resizeStartW, resizeStartH;

// Canvas dimensions (in mm)
const CANVAS_W_MM = 297;
const CANVAS_H_MM = 210;

// ─── Load existing design ───
@if (isset($design) && $design->fields_config)
    fields = @json($design->fields_config);
    nextId = fields.reduce((max, f) => Math.max(max, (f.id || 0) + 1), 1);
    document.addEventListener('DOMContentLoaded', () => { renderFields(); });
@endif

// ─── Add field ───
function addField(type) {
    const field = {
        id: nextId++,
        type: type,
        label: type === 'text' ? 'نص' : '',
        x_mm: 20 + (fields.length * 5) % 100,
        y_mm: 20 + (fields.length * 8) % 150,
        width_mm: type === 'barcode' ? 30 : 50,
        height_mm: type === 'barcode' ? 30 : 10,
        font_size: type === 'barcode' ? 10 : 18,
        font_weight: type === 'barcode' ? 400 : 700,
        color: '#000000',
        align: 'center',
    };
    fields.push(field);
    selectField(field.id);
    renderFields();
}

// ─── Render all fields on canvas ───
function renderFields() {
    const canvas = document.getElementById('canvas');
    const rect = canvas.getBoundingClientRect();
    const pxPerMmX = rect.width / CANVAS_W_MM;
    const pxPerMmY = rect.height / CANVAS_H_MM;
    // Remove existing field elements (keep background image)
    canvas.querySelectorAll('.canvas-field').forEach(el => el.remove());

    fields.forEach(f => {
        const el = document.createElement('div');
        el.className = 'canvas-field' + (f.id === selectedId ? ' selected' : '');
        el.dataset.fieldId = f.id;
        el.style.top = (f.y_mm * pxPerMmY) + 'px';
        el.style.left = (f.x_mm * pxPerMmX) + 'px';
        el.style.width = (f.width_mm * pxPerMmX) + 'px';
        el.style.height = (f.height_mm * pxPerMmY) + 'px';
        el.style.fontSize = f.font_size + 'pt';
        el.style.fontWeight = f.font_weight;
        el.style.color = f.color;
        el.style.justifyContent = f.align === 'right' ? 'flex-end' : (f.align === 'left' ? 'flex-start' : 'center');

        // Content
        const content = document.createElement('span');
        if (f.type === 'text') {
            content.textContent = f.label || 'نص';
        } else if (f.type === 'barcode') {
            content.innerHTML = '<span style="opacity:0.6;font-size:8pt;">||||||||||||||||</span>';
        } else {
            const labels = {
                student_name: 'اسم الطالب',
                student_code: 'STU00000',
                course_name: 'اسم المقرر',
                period_name: 'اسم الفترة',
                certificate_number: '2026-00001',
                issue_date: '2026-01-01',
            };
            content.textContent = labels[f.type] || f.type;
        }
        el.appendChild(content);

        // Resize handles
        ['se','sw','ne','nw'].forEach(h => {
            const handle = document.createElement('div');
            handle.className = 'resize-handle ' + h;
            el.appendChild(handle);
        });

        // Events
        el.addEventListener('mousedown', function(e) { onFieldMouseDown(e, f.id); });
        el.addEventListener('touchstart', function(e) { onFieldTouchStart(e, f.id); }, {passive: false});

        canvas.appendChild(el);
    });
}

// ─── Select field ───
function selectField(id) {
    selectedId = id;
    document.querySelectorAll('.canvas-field').forEach(el => el.classList.toggle('selected', parseInt(el.dataset.fieldId) === id));
    showProps(id);
    document.getElementById('deleteFieldBtn').style.display = id ? 'block' : 'none';
}

function deselectField(e) {
    if (e.target === e.currentTarget) { selectField(null); }
}

// ─── Properties panel ───
function showProps(id) {
    const panel = document.getElementById('propsContent');
    if (!id) { panel.innerHTML = '<p class="text-muted small">اختر حقلاً لعرض خصائصه</p>'; return; }
    const f = fields.find(x => x.id === id);
    if (!f) return;

    const labels = { student_name: 'اسم الطالب', student_code: 'كود الطالب', course_name: 'المقرر', period_name: 'الفترة', certificate_number: 'رقم الشهادة', issue_date: 'تاريخ الإصدار', barcode: 'باركود', text: 'نص حر' };

    panel.innerHTML = `
        <div class="prop-group"><label>النوع</label><input value="${labels[f.type] || f.type}" readonly style="background:#f1f5f9;"></div>
        ${f.type === 'text' ? `<div class="prop-group"><label>النص</label><input value="${f.label}" onchange="updateProp(${id},'label',this.value)"></div>` : ''}
        <div class="prop-group"><label>الموقع X (مم)</label><input type="number" value="${f.x_mm}" step="1" onchange="updateProp(${id},'x_mm',parseFloat(this.value)||0)"></div>
        <div class="prop-group"><label>الموقع Y (مم)</label><input type="number" value="${f.y_mm}" step="1" onchange="updateProp(${id},'y_mm',parseFloat(this.value)||0)"></div>
        ${f.type === 'barcode' ? `
        <div class="prop-group"><label>حجم الباركود (مم)</label><input type="number" value="${f.width_mm}" step="1" min="10" max="100" onchange="setBarcodeSize(${id},parseFloat(this.value)||30)"></div>
        ` : `
        <div class="prop-group"><label>العرض (مم)</label><input type="number" value="${f.width_mm}" step="1" min="10" onchange="updateProp(${id},'width_mm',parseFloat(this.value)||10)"></div>
        <div class="prop-group"><label>الارتفاع (مم)</label><input type="number" value="${f.height_mm}" step="1" min="5" onchange="updateProp(${id},'height_mm',parseFloat(this.value)||5)"></div>
        `}
        <div class="prop-group"><label>حجم الخط (pt)</label><input type="number" value="${f.font_size}" step="1" min="6" max="72" onchange="updateProp(${id},'font_size',parseFloat(this.value)||12)"></div>
        <div class="prop-group"><label>وزن الخط</label><select onchange="updateProp(${id},'font_weight',this.value)">
            <option value="400" ${f.font_weight == 400 ? 'selected':''}>عادي</option>
            <option value="500" ${f.font_weight == 500 ? 'selected':''}>متوسط</option>
            <option value="700" ${f.font_weight == 700 ? 'selected':''}>غامق</option>
        </select></div>
        <div class="prop-group"><label>اللون</label><input type="color" value="${f.color}" onchange="updateProp(${id},'color',this.value)"></div>
        <div class="prop-group"><label>المحاذاة</label><select onchange="updateProp(${id},'align',this.value)">
            <option value="center" ${f.align=='center'?'selected':''}>وسط</option>
            <option value="right" ${f.align=='right'?'selected':''}>يمين</option>
            <option value="left" ${f.align=='left'?'selected':''}>يسار</option>
        </select></div>
    `;
}

// ─── Update property ───
function updateProp(id, prop, value) {
    const f = fields.find(x => x.id === id);
    if (!f) return;
    f[prop] = value;
    // Keep barcode fields square
    if (f.type === 'barcode' && (prop === 'width_mm' || prop === 'height_mm')) {
        f.width_mm = f.height_mm = value;
    }
    renderFields();
    if (selectedId === id) showProps(id);
}

// ─── Set barcode size (square) ───
function setBarcodeSize(id, size) {
    const f = fields.find(x => x.id === id);
    if (!f) return;
    f.width_mm = f.height_mm = Math.max(10, Math.min(100, size));
    renderFields();
    if (selectedId === id) showProps(id);
}

// ─── Delete selected field ───
function deleteSelectedField() {
    if (!selectedId) return;
    if (!confirm('حذف هذا الحقل؟')) return;
    fields = fields.filter(f => f.id !== selectedId);
    selectedId = null;
    renderFields();
    showProps(null);
    document.getElementById('deleteFieldBtn').style.display = 'none';
}

// ─── Mouse drag ───
function onFieldMouseDown(e, id) {
    e.stopPropagation();
    selectField(id);
    if (e.target.classList.contains('resize-handle')) {
        startResize(e, id, e.target.className.split(' ')[1]);
        return;
    }
    startDrag(e, id);
}

function startDrag(e, id) {
    const canvas = document.getElementById('canvas');
    const rect = canvas.getBoundingClientRect();
    const f = fields.find(x => x.id === id);
    if (!f) return;
    const pxPerMmX = rect.width / CANVAS_W_MM;
    const pxPerMmY = rect.height / CANVAS_H_MM;
    dragTarget = id;
    dragOffsetX = (e.clientX - rect.left) / pxPerMmX - f.x_mm;
    dragOffsetY = (e.clientY - rect.top) / pxPerMmY - f.y_mm;
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragEnd);
}

function onDragMove(e) {
    if (!dragTarget) return;
    const canvas = document.getElementById('canvas');
    const rect = canvas.getBoundingClientRect();
    const pxPerMmX = rect.width / CANVAS_W_MM;
    const pxPerMmY = rect.height / CANVAS_H_MM;
    const f = fields.find(x => x.id === dragTarget);
    if (!f) return;
    f.x_mm = Math.max(0, Math.min(CANVAS_W_MM - f.width_mm, (e.clientX - rect.left) / pxPerMmX - dragOffsetX));
    f.y_mm = Math.max(0, Math.min(CANVAS_H_MM - f.height_mm, (e.clientY - rect.top) / pxPerMmY - dragOffsetY));
    renderFields();
}

function onDragEnd() {
    dragTarget = null;
    document.removeEventListener('mousemove', onDragMove);
    document.removeEventListener('mouseup', onDragEnd);
}

// ─── Resize ───
function startResize(e, id, handle) {
    e.stopPropagation();
    const f = fields.find(x => x.id === id);
    if (!f) return;
    resizeTarget = id;
    resizeHandle = handle;
    resizeStartX = e.clientX;
    resizeStartY = e.clientY;
    resizeStartW = f.width_mm;
    resizeStartH = f.height_mm;
    document.addEventListener('mousemove', onResizeMove);
    document.addEventListener('mouseup', onResizeEnd);
}

function onResizeMove(e) {
    if (!resizeTarget) return;
    const canvas = document.getElementById('canvas');
    const rect = canvas.getBoundingClientRect();
    const pxPerMmX = rect.width / CANVAS_W_MM;
    const pxPerMmY = rect.height / CANVAS_H_MM;
    const f = fields.find(x => x.id === resizeTarget);
    if (!f) return;
    const dx = (e.clientX - resizeStartX) / pxPerMmX;
    const dy = (e.clientY - resizeStartY) / pxPerMmY;

    if (resizeHandle.includes('e')) { f.width_mm = Math.max(10, resizeStartW + dx); }
    if (resizeHandle.includes('s')) { f.height_mm = Math.max(10, resizeStartH + dy); }
    if (resizeHandle.includes('w')) {
        const newW = Math.max(10, resizeStartW - dx);
        f.x_mm = f.x_mm + (resizeStartW - newW);
        f.width_mm = newW;
    }
    if (resizeHandle.includes('n')) {
        const newH = Math.max(10, resizeStartH - dy);
        f.y_mm = f.y_mm + (resizeStartH - newH);
        f.height_mm = newH;
    }
    // Keep barcode fields square
    if (f.type === 'barcode') {
        const sz = Math.max(10, f.width_mm, f.height_mm);
        f.width_mm = f.height_mm = sz;
    }
    renderFields();
}

function onResizeEnd() {
    resizeTarget = null;
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', onResizeEnd);
}

// ─── Background preview ───
function previewBackground(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        const canvas = document.getElementById('canvas');
        const existing = canvas.querySelector('.bg-img');
        if (existing) existing.remove();
        const img = document.createElement('img');
        img.className = 'bg-img';
        img.src = ev.target.result;
        img.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;object-fit:fill;pointer-events:none;';
        canvas.prepend(img);
    };
    reader.readAsDataURL(file);
}

// ─── Save ───
function saveDesign() {
    // Validate fields have student_name and certificate_number
    const hasStudentName = fields.some(f => f.type === 'student_name');
    const hasCertNum = fields.some(f => f.type === 'certificate_number');
    if (!hasStudentName) { alert('يجب إضافة حقل "اسم الطالب"'); return; }
    if (!hasCertNum) { alert('يجب إضافة حقل "رقم الشهادة"'); return; }

    document.getElementById('fieldsConfig').value = JSON.stringify(fields);
    document.getElementById('designForm').submit();
}
</script>
@endpush
