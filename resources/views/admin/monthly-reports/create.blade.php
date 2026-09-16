@extends('admin.layouts.master')

@section('title', 'تقرير جديد')

@section('content')
<div class="page-header">
    <h4>إضافة تقرير شهري من قالب</h4>
    <p>
        <a href="{{ route('admin.monthly-reports.index') }}" class="text-decoration-none">التقارير الشهرية</a> / جديد
    </p>
</div>

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
                                data-version="{{ $template->version }}"
                                {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->title_ar }} (V{{ $template->version }} — {{ $template->sections()->count() }} أقسام)
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
document.getElementById('templateSelect').addEventListener('change', function () {
    const hint = document.getElementById('templateHint');
    const opt = this.options[this.selectedIndex];
    hint.textContent = opt.value ? 'الإصدار V' + opt.dataset.version + ' — ' + opt.dataset.sections + ' أقسام سيُنشأ منها' : '';
});
</script>
@endpush