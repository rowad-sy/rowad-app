@extends('admin.layouts.master')

@section('title', 'وثيقة جديدة')

@section('content')
<x-page-header :title="'وثيقة جديدة'"
               :breadcrumb="[['label' => 'الوثائق', 'url' => route('admin.project-docs.documents.index')], ['label' => 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.project-docs.documents.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">طريقة الإنشاء <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" value="structured" id="modeStructured"
                                   {{ old('mode', 'structured') === 'structured' ? 'checked' : '' }}>
                            <label class="form-check-label" for="modeStructured">
                                ملحق منظّم (تعبئة قالب)<br>
                                <span class="small text-muted">تُنشأ وثيقة بأقسام تُعبّأ داخل النظام ثم تُطبع A4</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" value="external" id="modeExternal"
                                   {{ old('mode') === 'external' ? 'checked' : '' }}>
                            <label class="form-check-label" for="modeExternal">
                                ملف خارجي (PDF جاهز)<br>
                                <span class="small text-muted">تُرفع الوثيقة كما هي وتدخل دورة الاعتماد نفسها</span>
                            </label>
                        </div>
                    </div>
                    @error('mode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                {{-- ===== حقول الملحق المنظّم ===== --}}
                <div id="structuredFields">
                    <div class="mb-3">
                        <label class="form-label">القالب <span class="text-danger d-none" data-req="structured">*</span></label>
                        <select name="template_id" id="templateSelect" class="form-select @error('template_id') is-invalid @enderror">
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
                        <label class="form-label">عدد الصفحات</label>
                        <div class="form-control-plaintext border rounded bg-light px-3 py-2 fw-bold d-inline-block" id="pageCountDisplay">—</div>
                        <div class="form-text">مرسوم من القالب المختار — للتغيير عدّل «عدد الصفحات الافتراضي» في القالب نفسه</div>
                    </div>
                </div>

                {{-- ===== حقول الملف الخارجي ===== --}}
                <div id="externalFields" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">ملف PDF <span class="text-danger d-none" data-req="external">*</span></label>
                        <input type="file" name="file" accept="application/pdf" class="form-control @error('file') is-invalid @enderror">
                        @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">حتى 25 ميجابايت — يُحفظ في أرشيف الوثائق ويرتبط بهذه الوثيقة</div>
                    </div>

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label">التصنيف <span class="text-danger d-none" data-req="external">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror">
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">تاريخ الوثيقة</label>
                            <input type="date" name="document_date" class="form-control" value="{{ old('document_date') }}">
                        </div>
                    </div>
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        الوثيقة الخارجية تُقدَّم للمراجعة وتُعتمد من النظام، لكن لا تُعبَّأ أقساماً ولا تُنتَج A4 — الطباعة تفتح الملف الأصلي.
                    </div>
                </div>

                {{-- ===== حقول مشتركة ===== --}}
                <div class="mb-3">
                    <label class="form-label">عنوان الوثيقة</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                           placeholder="مثال: بطاقة مشروع معهد الرواد — الدورة الثالثة">
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
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
                    <li><strong>ملحق منظّم:</strong> تختار قالباً فتُنشأ وثيقة بأقسام تُعبّأ ثم تُقفل عند الإرسال للمراجعة.</li>
                    <li><strong>ملف خارجي:</strong> ترفع PDF جاهزاً (تقرير مُعَدّ خارجياً، مذكرة ممسوحة...) فيدخل دورة المسودة → المراجعة → الاعتماد نفسها.</li>
                    <li>الاعتماد أو الرفض من صفحة العرض مع ملاحظة (توقيع موثّق) — في الحالتين.</li>
                    <li>ملفات الوثائق الخارجية تُحفظ في أرشيف الوثائق وتظهر هناك مرتبطة بها.</li>
                    <li>زر الطباعة في المنظّم ينتج A4 بهوية المؤسسة، وفي الخارجي يفتح الملف الأصلي.</li>
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
const structuredFields = document.getElementById('structuredFields');
const externalFields = document.getElementById('externalFields');

function applyTemplateInfo() {
    const opt = templateSelect.options[templateSelect.selectedIndex];
    if (!opt.value) { templateHint.textContent = ''; pageCountDisplay.textContent = '—'; return; }
    templateHint.textContent = 'الإصدار V' + opt.dataset.version + ' — ' + opt.dataset.sections + ' أقسام — ' + opt.dataset.pages + ' صفحة افتراضياً';
    pageCountDisplay.textContent = opt.dataset.pages + ' صفحة';
}

function applyMode() {
    const external = document.getElementById('modeExternal').checked;
    structuredFields.classList.toggle('d-none', external);
    externalFields.classList.toggle('d-none', !external);
    templateSelect.required = !external;
    document.querySelectorAll('[data-req]').forEach(el => el.classList.toggle('d-none', el.dataset.req === 'structured' ? external : !external));
}

document.getElementById('modeStructured').addEventListener('change', applyMode);
document.getElementById('modeExternal').addEventListener('change', applyMode);
templateSelect.addEventListener('change', applyTemplateInfo);
applyTemplateInfo();
applyMode();
</script>
@endpush
