@extends('admin.layouts.master')

@section('title', 'وثيقة جديدة')

@section('content')
<div class="page-header">
    <h4>وثيقة جديدة من قالب</h4>
    <p>
        <a href="{{ route('admin.project-docs.documents.index') }}" class="text-decoration-none">الوثائق</a> / جديد
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.project-docs.documents.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">القالب <span class="text-danger">*</span></label>
                    <select name="template_id" id="templateSelect" class="form-select @error('template_id') is-invalid @enderror" required>
                        <option value="">اختر القالب...</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}"
                                data-sections="{{ $template->sections()->count() }}"
                                data-pages="{{ max(1, (int) ($template->default_page_count ?? 1)) }}"
                                data-version="{{ $template->version }}"
                                {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->title_ar }} (V{{ $template->version }} — {{ $template->sections()->count() }} أقسام — {{ max(1, (int) ($template->default_page_count ?? 1)) }} {{ (int) ($template->default_page_count ?? 1) > 3 ? 'صفحات' : 'صفحة' }})
                            </option>
                        @endforeach
                    </select>
                    @error('template_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div id="templateHint" class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">عنوان الوثيقة</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                           placeholder="مثال: بطاقة مشروع معهد الرواد — الدورة الثالثة">
                </div>

                <div class="mb-3">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">الفترة (شهر/سنة)</label>
                    <input type="text" name="period" class="form-control" dir="ltr" value="{{ old('period') }}"
                           placeholder="مثال: 2026-09 — للتقارير الشهرية">
                </div>

                <div class="mb-3">
                    <label class="form-label">عدد الصفحات</label>
                    <input type="number" name="page_count" id="pageCountInput" class="form-control @error('page_count') is-invalid @enderror"
                           min="1" max="60" value="{{ old('page_count', 1) }}">
                    @error('page_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">القيمة الافتراضية تُقرأ من القالب — يمكن تعديلها؛ توزيع الأقسام على الصفحات يتم لاحقاً من صفحة التعبئة</div>
                </div>

                <button class="btn btn-primary">إنشاء الوثيقة</button>
                <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-info-circle"></i> كيف تعمل الوثائق</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>تختار قالباً فتُنشأ منه وثيقة بكل أقسامه فارغة.</li>
                    <li>تُعبأ الأقسام في صفحة التعبئة (يمكن قفل قسم بعد الانتهاء منه).</li>
                    <li>عند الإرسال للمراجعة تُقفل كل الأقسام وتنتقل الوثيقة لحالة "قيد المراجعة".</li>
                    <li>الاعتماد أو الرفض يتم من صفحة العرض مع ملاحظة (توقيع موثّق).</li>
                    <li>زر الطباعة ينتج نسخة A4 بهوية المؤسسة.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const templateSelect = document.getElementById('templateSelect');
const pageCountInput = document.getElementById('pageCountInput');
const templateHint = document.getElementById('templateHint');

function applyTemplateInfo(setPages) {
    const opt = templateSelect.options[templateSelect.selectedIndex];
    if (!opt.value) { templateHint.textContent = ''; return; }
    templateHint.textContent = 'الإصدار V' + opt.dataset.version + ' — ' + opt.dataset.sections + ' أقسام — ' + opt.dataset.pages + ' صفحة افتراضياً';
    if (setPages || pageCountInput.value === '1') pageCountInput.value = opt.dataset.pages;
}

templateSelect.addEventListener('change', () => applyTemplateInfo(true));
applyTemplateInfo(false);
</script>
@endpush