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
                                data-version="{{ $template->version }}"
                                {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->title_ar }} (V{{ $template->version }} — {{ $template->sections()->count() }} أقسام)
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

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ old('center_id') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
document.getElementById('templateSelect').addEventListener('change', function () {
    const hint = document.getElementById('templateHint');
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        hint.textContent = 'الإصدار V' + opt.dataset.version + ' — ' + opt.dataset.sections + ' أقسام سيُنشأ منها';
    } else {
        hint.textContent = '';
    }
});
</script>
@endpush