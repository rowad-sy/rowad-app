@extends('admin.layouts.master')

@php
if (! function_exists('defaultMonthlyReportDefinition')) {
    function defaultMonthlyReportDefinition(): string
    {
        return json_encode([
            'header_meta' => ['اسم المشروع', 'الفترة (الشهر/السنة)', 'الموقع', 'إعداد: مدير المشروع'],
            'sections' => [
                ['key' => 'exec_summary', 'title' => '1 - ملخص تنفيذي', 'type' => 'paragraph', 'assignee_role' => 'مدير المشروع'],
                ['key' => 'achievements', 'title' => '2 - أبرز الإنجازات والتحديات', 'type' => 'table', 'columns' => ['البند', 'ملخص لا يتجاوز 3 أسطر'], 'assignee_role' => null],
                ['key' => 'kpis', 'title' => '3 - مؤشرات الأداء KPIs', 'type' => 'table', 'columns' => ['المؤشر', 'المستهدف', 'الفعلي', 'نسبة الإنجاز'], 'assignee_role' => null],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
@endphp

@section('title', isset($template) ? 'تعديل قالب تقرير شهري' : 'قالب تقرير شهري جديد')

@section('content')
@php $isEdit = isset($template); if (!$isEdit) { $template = new \App\Models\Admin\MonthlyReports\MonthlyReportTemplate(['is_active' => true]); } @endphp

<div class="page-header">
    <h4>{{ $isEdit ? 'تعديل قالب تقرير شهري' : 'قالب تقرير شهري جديد' }}</h4>
    <p>
        <a href="{{ route('admin.monthly-reports.templates.index') }}" class="text-decoration-none">القوالب</a>
        / {{ $isEdit ? $template->title_ar : 'جديد' }}
    </p>
</div>

@if ($isEdit)
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>حفظ التعديل يرفع الإصدار تلقائياً</strong> من <code>V{{ $template->version }}</code> إلى <code>V{{ $template->version + 1 }}</code> عند تغيّر التعريف البنيوي (json_definition). التقارير القديمة تحتفظ برقم إصدارها لقطة عند إنشائها.
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="form-card">
            <form method="POST" action="{{ $isEdit ? route('admin.monthly-reports.templates.update', $template) : route('admin.monthly-reports.templates.store') }}">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">اسم القالب <span class="text-danger">*</span></label>
                        <input type="text" name="title_ar" class="form-control @error('title_ar') is-invalid @enderror"
                               value="{{ old('title_ar', $template->title_ar ?? '') }}" required>
                        @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">المفتاح (key) <span class="text-danger">*</span></label>
                        <input type="text" name="key" dir="ltr" class="form-control @error('key') is-invalid @enderror"
                               value="{{ old('key', $template->key ?? '') }}" required
                               {{ $isEdit ? 'readonly' : '' }}>
                        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">الترويسة (slug)</label>
                        <input type="text" name="slug" dir="ltr" class="form-control"
                               value="{{ old('slug', $template->slug ?? '') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">عدد الصفحات الافتراضي <span class="text-danger">*</span></label>
                        <input type="number" name="default_page_count" min="1" max="60" class="form-control @error('default_page_count') is-invalid @enderror"
                               value="{{ old('default_page_count', $template->default_page_count ?? 1) }}">
                        @error('default_page_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">تعريف الأقسام (JSON) <span class="text-danger">*</span></label>
                    <textarea name="json_definition" id="jsonDefinition" rows="22" dir="ltr"
                              class="form-control font-monospace @error('json_definition') is-invalid @enderror"
                              spellcheck="false">{{ old('json_definition', $isEdit ? json_encode($template->json_definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : \defaultMonthlyReportDefinition()) }}</textarea>
                    @error('json_definition') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div id="jsonStatus" class="form-text"></div>
                    <div class="form-text">توزيع الأقسام على الصفحات: أضف مفتاح <code dir="ltr">"page": N</code> لكل قسم — التقارير المنشأة من القالب ترث الصفحة المحددة، ويمكن تعديلها في صفحة التعبئة.</div>
                </div>

                <div class="mb-3 w-25">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                               {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">قالب مفعّل</label>
                    </div>
                </div>

                <button class="btn btn-primary">حفظ القالب</button>
                <a href="{{ route('admin.monthly-reports.templates.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const area = document.getElementById('jsonDefinition');
    const status = document.getElementById('jsonStatus');

    function validate() {
        try {
            const parsed = JSON.parse(area.value);
            if (!parsed.sections || !Array.isArray(parsed.sections) || parsed.sections.length === 0) {
                status.className = 'form-text text-danger';
                status.textContent = 'يجب أن يحتوي على مصفوفة sections غير فارغة';
                return;
            }
            const maxPage = Math.max(1, ...parsed.sections.map(s => parseInt(s.page ?? 1, 10)));
            const pagesInput = document.querySelector('input[name="default_page_count"]');
            const pageCount = parseInt(pagesInput?.value ?? '1', 10);
            const overflow = maxPage > pageCount
                ? ` — تحذير: أقسام تصل للصفحة ${maxPage} بينما عدد الصفحات ${pageCount}`
                : '';
            status.className = 'form-text ' + (overflow ? 'text-warning' : 'text-success');
            status.textContent = `JSON صحيح — ${parsed.sections.length} قسم على ${maxPage} ${maxPage > 3 ? 'صفحات' : 'صفحة'}، الأنواع: ${parsed.sections.map(s => s.type).join(', ')}${overflow}`;
        } catch (e) {
            status.className = 'form-text text-danger';
            status.textContent = 'صيغة JSON غير صحيحة: ' + e.message;
        }
    }

    area.addEventListener('input', validate);
    document.querySelector('input[name="default_page_count"]')?.addEventListener('input', validate);
    validate();
})();
</script>
@endpush