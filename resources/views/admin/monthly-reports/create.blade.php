@extends('admin.layouts.master')

@section('title', 'تقرير جديد')

@section('content')
<x-page-header :title="'إضافة تقرير شهري من قالب'"
               :breadcrumb="[['label' => 'التقارير الشهرية', 'url' => route('admin.monthly-reports.index')], ['label' => 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.monthly-reports.store') }}">
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
                    @error('template_id') <div class="invalid-feedback">{{ $message }}</div> @enderror <div id="templateHint" class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">عنوان التقرير</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                           placeholder="مثال: التقرير الشهري لمشروع رواد المعرفة — حزيران 2026">
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
                           placeholder="مثال: 2026-06">
                </div>

                <div class="mb-3">
                    <label class="form-label">عدد الصفحات</label>
                    <div class="form-control-plaintext border rounded bg-light px-3 py-2 fw-bold d-inline-block" id="pageCountDisplay">—</div>
                    <div class="form-text">مرسوم من القالب المختار — للتغيير عدّل «عدد الصفحات الافتراضي» في القالب نفسه</div>
                </div>

                <button class="btn btn-primary">إنشاء التقرير</button>
                <a href="{{ route('admin.monthly-reports.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-info-circle"></i> كيف تعمل التقارير الشهرية</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>تختار قالباً فتُنشأ منه تقرير بكل أقسامه فارغة.</li>
                    <li>تُعبأ الأقسام في صفحة التعبئة (يمكن قفل قسم بعد الانتهاء منه).</li>
                    <li>عند الإرسال للمراجعة تُقفل كل الأقسام وينتقل التقرير لحالة "قيد المراجعة".</li>
                    <li>الاعتماد أو الرفض يتم من صفحة العرض مع ملاحظة.</li>
                    <li>وحدة مستقلة تماماً عن وثائق المشروع.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const templateSelect = document.getElementById('templateSelect');
const pageCountDisplay = document.getElementById('pageCountDisplay');
const templateHint = document.getElementById('templateHint');

function applyTemplateInfo() {
    const opt = templateSelect.options[templateSelect.selectedIndex];
    if (!opt.value) { templateHint.textContent = ''; pageCountDisplay.textContent = '—'; return; }
    templateHint.textContent = 'الإصدار V' + opt.dataset.version + ' — ' + opt.dataset.sections + ' أقسام — ' + opt.dataset.pages + ' صفحة افتراضياً';
    pageCountDisplay.textContent = opt.dataset.pages + ' صفحة';
}

templateSelect.addEventListener('change', applyTemplateInfo);
applyTemplateInfo();
</script>
@endpush