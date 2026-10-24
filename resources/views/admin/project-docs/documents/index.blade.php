@extends('admin.layouts.master')

@section('title', 'الوثائق')

@php
    $sourceBadge = [
        'structured' => ['tone' => 'primary-subtle text-primary', 'icon' => 'bi-file-earmark-text', 'label' => 'منظّمة'],
        'external' => ['tone' => 'danger-subtle text-danger', 'icon' => 'bi-file-earmark-pdf', 'label' => 'ملف خارجي'],
        'archive' => ['tone' => 'secondary-subtle text-secondary', 'icon' => 'bi-archive', 'label' => 'أرشيف PDF'],
    ];
    $statusTone = [
        'draft' => 'secondary',
        'under_review' => 'warning text-dark',
        'approved' => 'success',
        'rejected' => 'danger',
    ];
    $tabDefs = array_values(array_filter([
        ['key' => null, 'label' => 'الكل', 'icon' => 'bi-collection', 'show' => true],
        ['key' => 'structured', 'label' => 'منظّمة', 'icon' => 'bi-file-earmark-text', 'show' => $canDocs],
        ['key' => 'external', 'label' => 'ملف خارجي', 'icon' => 'bi-file-earmark-pdf', 'show' => $canDocs],
        ['key' => 'archive', 'label' => 'أرشيف PDF', 'icon' => 'bi-archive', 'show' => $canArchive],
    ], fn ($t) => $t['show']));

    $activeFilters = [];
    $filterUrlWithout = function (array $keys) {
        $query = request()->query();
        unset($query['page']);
        foreach ($keys as $key) {
            unset($query[$key]);
        }

        return route('admin.project-docs.documents.index', $query);
    };
    if ($filters['q']) $activeFilters[] = ['label' => 'بحث: ' . $filters['q'], 'url' => $filterUrlWithout(['q'])];
    if ($filters['path_id']) $activeFilters[] = ['label' => 'المسار: ' . ($paths->firstWhere('id', $filters['path_id'])?->name ?? $filters['path_id']), 'url' => $filterUrlWithout(['path_id'])];
    if ($filters['project_id']) $activeFilters[] = ['label' => 'المشروع: ' . ($projects->firstWhere('id', $filters['project_id'])?->name ?? $filters['project_id']), 'url' => $filterUrlWithout(['project_id'])];
    if ($filters['template_id']) $activeFilters[] = ['label' => 'القالب: ' . ($templates->firstWhere('id', $filters['template_id'])?->title_ar ?? $filters['template_id']), 'url' => $filterUrlWithout(['template_id'])];
    if ($filters['year'] || $filters['period_from'] || $filters['period_to']) {
        $rangeLabel = $filters['year'] ? (string) $filters['year'] : trim(($filters['period_from'] ?: '…') . ' — ' . ($filters['period_to'] ?: '…'));
        $activeFilters[] = ['label' => 'الفترة: ' . $rangeLabel, 'url' => $filterUrlWithout(['year', 'period_from', 'period_to'])];
    }
    if ($filters['status']) $activeFilters[] = ['label' => 'الحالة: ' . \App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES[$filters['status']], 'url' => $filterUrlWithout(['status'])];
    if ($filters['category']) $activeFilters[] = ['label' => 'التصنيف: ' . ($categories[$filters['category']] ?? $filters['category']), 'url' => $filterUrlWithout(['category'])];
    if ($filters['center_id']) $activeFilters[] = ['label' => 'المركز: ' . ($centers->firstWhere('id', $filters['center_id'])?->name ?? $filters['center_id']), 'url' => $filterUrlWithout(['center_id'])];
@endphp

@section('content')
<x-page-header :title="'الوثائق'"
               :description="'الوثائق النظامية المعبّأة وملفات الأرشيف في صفحة واحدة'"
               :breadcrumb="[['label' => 'الوثائق']]">
    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
        <a href="{{ route('admin.project-docs.documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> وثيقة جديدة
        </a>
    @endcanPermission
    @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'create')
        <a href="{{ route('admin.documents-archive.create') }}" class="btn btn-outline-primary">
            <i class="bi bi-cloud-upload"></i> رفع ملف PDF
        </a>
    @endcanPermission
    <a href="{{ route('admin.project-docs.documents.help') }}" class="btn btn-outline-info">
        <i class="bi bi-question-circle"></i> معلومات
    </a>
</x-page-header>

<ul class="nav nav-pills mb-3 flex-nowrap overflow-auto">
    @foreach ($tabDefs as $tab)
        <li class="nav-item">
            <a href="{{ route('admin.project-docs.documents.index', array_filter([
                    'type' => $tab['key'],
                    'q' => $filters['q'], 'year' => $filters['year'],
                    'period_from' => $filters['period_from'], 'period_to' => $filters['period_to'],
                    'path_id' => $filters['path_id'], 'project_id' => $filters['project_id'],
                    'center_id' => $filters['center_id'],
                    'template_id' => in_array($tab['key'], [null, 'structured'], true) ? $filters['template_id'] : null,
                    'status' => $tab['key'] === 'archive' ? null : $filters['status'],
                    'category' => in_array($tab['key'], [null, 'archive'], true) ? $filters['category'] : null,
                    'per_page' => $perPage,
                ])) }}"
               class="nav-link {{ $filters['type'] === $tab['key'] ? 'active' : '' }}">
            <i class="bi {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                <span class="badge bg-light text-dark ms-1">
                    {{ $tab['key'] === null ? $typeCounts['all'] : $typeCounts[$tab['key']] }}
                </span>
            </a>
        </li>
    @endforeach
</ul>

<section class="documents-filter-panel mb-3">
    <div class="documents-filter-heading">
        <div class="d-flex align-items-start gap-2">
            <span class="documents-filter-icon"><i class="bi bi-funnel" aria-hidden="true"></i></span>
            <div>
                <h5 class="mb-1">اعثر على الوثيقة المطلوبة</h5>
                <p class="mb-0 text-muted small">ابدأ بالبحث أو حدّد المسار والمشروع والقالب والفترة.</p>
            </div>
        </div>
        @if ($activeFilters)
            <span class="badge rounded-pill text-bg-light border">{{ count($activeFilters) }} فلاتر نشطة</span>
        @endif
    </div>
    <x-filter-bar>
        @if ($filters['type'])
            <input type="hidden" name="type" value="{{ $filters['type'] }}">
        @endif
        <div class="col-12 col-lg-4 filter-field">
            <label class="form-label" for="f-q">بحث</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
                <input type="search" id="f-q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="عنوان، قالب، مشروع، كود، أو ملف…">
            </div>
        </div>
        <div class="col-6 col-lg-2 filter-field">
            <label class="form-label" for="f-path">المسار</label>
            <select id="f-path" name="path_id" class="form-select">
                <option value="">كل المسارات</option>
                @foreach ($paths as $path)
                    <option value="{{ $path->id }}" {{ $filters['path_id'] === $path->id ? 'selected' : '' }}>{{ $path->sort_order }} — {{ $path->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-3 filter-field">
            <label class="form-label" for="f-project-search">المشروع</label>
            <input type="search" id="f-project-search" class="form-control form-control-sm mb-1"
                   placeholder="ابحث داخل قائمة المشاريع…" autocomplete="off"
                   value="{{ $filters['project_id'] ? ($projects->firstWhere('id', $filters['project_id'])?->name ?? '') : '' }}">
            <select id="f-project" name="project_id" class="form-select">
                <option value="">كل المشاريع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" data-path-id="{{ $project->path_id }}" {{ (int) $filters['project_id'] === $project->id ? 'selected' : '' }}>
                        {{ $project->path?->name ? $project->path->name . ' — ' : '' }}{{ $project->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @if ($canDocs && in_array($filters['type'], [null, 'structured'], true))
            <div class="col-6 col-lg-3 filter-field">
                <label class="form-label" for="f-template">قالب الوثيقة</label>
                <select id="f-template" name="template_id" class="form-select">
                    <option value="">كل القوالب</option>
                    @foreach ($templates as $template)
                        @if ($template->key !== \App\Models\Admin\ProjectDocs\AnnexDocument::EXTERNAL_TEMPLATE_KEY)
                            <option value="{{ $template->id }}" {{ $filters['template_id'] === $template->id ? 'selected' : '' }}>
                                {{ $template->title_ar }}{{ $template->is_active ? '' : ' (غير نشط)' }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-6 col-lg-2 filter-field">
            <label class="form-label" for="f-year">سنة الفترة</label>
            <select id="f-year" name="year" class="form-select">
                <option value="">كل السنوات</option>
                @foreach (range((int) date('Y'), (int) date('Y') - 15) as $y)
                    <option value="{{ $y }}" {{ $filters['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2 filter-field">
            <label class="form-label" for="f-period-from">من شهر</label>
            <input id="f-period-from" type="month" name="period_from" value="{{ $filters['period_from'] }}" class="form-control">
        </div>
        <div class="col-6 col-lg-2 filter-field">
            <label class="form-label" for="f-period-to">إلى شهر</label>
            <input id="f-period-to" type="month" name="period_to" value="{{ $filters['period_to'] }}" class="form-control">
        </div>
        <div class="col-12 small text-muted documents-date-hint">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            يُطبّق النطاق على فترة التقارير، وعلى تاريخ الوثيقة في الأرشيف.
        </div>
        <div class="col-12">
            <details {{ $filters['status'] || $filters['category'] || $filters['center_id'] ? 'open' : '' }}>
                <summary class="small text-primary">
                    <i class="bi bi-sliders me-1" aria-hidden="true"></i>فلاتر إضافية
                    @if ($filters['status'] || $filters['category'] || $filters['center_id'])
                        <span class="badge rounded-pill text-bg-light border ms-1">مستخدمة</span>
                    @endif
                </summary>
                <div class="row g-2 mt-1">
        @if ($canDocs && $filters['type'] !== 'archive')
            <div class="col-6 col-md-2 filter-field">
                <label class="form-label" for="f-status">الحالة</label>
                <select id="f-status" name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    @foreach (\App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if ($canArchive && in_array($filters['type'], [null, 'archive'], true))
            <div class="col-6 col-md-2 filter-field">
                <label class="form-label" for="f-category">التصنيف</label>
                <select id="f-category" name="category" class="form-select">
                    <option value="">كل التصنيفات</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" {{ $filters['category'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-center">المركز</label>
            <select id="f-center" name="center_id" class="form-select">
                <option value="">كل المراكز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ $filters['center_id'] === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-per-page">عدد الوثائق في الصفحة</label>
            <select id="f-per-page" name="per_page" class="form-select">
                @foreach ($perPageOptions as $count)
                    <option value="{{ $count }}" {{ $perPage === $count ? 'selected' : '' }}>{{ $count }}</option>
                @endforeach
            </select>
        </div>
                </div>
            </details>
        </div>
    </x-filter-bar>
</section>

@if ($activeFilters)
    <div class="documents-active-filters mb-3" aria-label="الفلاتر النشطة">
        <span class="small text-muted me-1">محدداتك:</span>
        @foreach ($activeFilters as $filter)
            <a href="{{ $filter['url'] }}" class="badge rounded-pill documents-filter-chip text-decoration-none">
                {{ $filter['label'] }} <i class="bi bi-x ms-1" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif

<section class="table-container documents-results">
    <div class="documents-results-heading">
        <div>
            <h5 class="mb-1">النتائج</h5>
            <div class="text-muted small">
                @if ($documents->total() > 0)
                    عرض {{ $documents->firstItem() }}–{{ $documents->lastItem() }} من {{ $documents->total() }} وثيقة
                @else
                    لا توجد وثائق مطابقة للمعايير الحالية
                @endif
            </div>
        </div>
        <span class="documents-results-total"><i class="bi bi-files me-1" aria-hidden="true"></i>{{ $documents->total() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 documents-table">
            <thead>
                <tr>
                    <th>الوثيقة</th>
                    <th>المصدر</th>
                    <th>المشروع</th>
                    <th>فترة الوثيقة</th>
                    <th class="d-none d-xl-table-cell">المركز</th>
                    <th class="d-none d-lg-table-cell">تاريخ الوثيقة / الإضافة</th>
                    <th>الحالة</th>
                    <th class="d-none d-lg-table-cell">المستخدم</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $row)
                    @php $model = $row['model']; @endphp
                    <tr>
                        <td>
                            <a href="{{ $row['kind'] === 'archive'
                                    ? route('admin.documents-archive.show', $model)
                                    : route('admin.project-docs.documents.show', $model) }}"
                               class="fw-semibold text-decoration-none">
                                {{ $row['title'] }}
                            </a>
                            <div class="text-muted small" dir="ltr">#{{ $model->id }} · {{ $row['subtitle'] }}</div>
                        </td>
                        <td class="text-nowrap">
                            <span class="badge bg-{{ $sourceBadge[$row['source']]['tone'] }}">
                                <i class="bi {{ $sourceBadge[$row['source']]['icon'] }}"></i> {{ $sourceBadge[$row['source']]['label'] }}
                            </span>
                        </td>
                        <td>
                            {{ $row['project'] ?? '—' }}
                            @if ($row['path']) <div class="small text-muted">{{ $row['path'] }}</div> @endif
                        </td>
                        <td class="text-nowrap">{{ $row['period'] ?? '—' }}</td>
                        <td class="d-none d-xl-table-cell">{{ $row['center'] ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $row['when']?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($row['kind'] === 'archive')
                                <span class="badge bg-light text-dark border">أرشيف</span>
                            @else
                                <span class="badge bg-{{ $statusTone[$model->status] ?? 'secondary' }}">
                                    {{ \App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES[$model->status] ?? $model->status }}
                                </span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $row['person'] ?? '—' }}</td>
                        <td class="text-nowrap">
                            {{-- ===== وثيقة نظامية ===== --}}
                            @if ($row['kind'] === 'document')
                                <a href="{{ route('admin.project-docs.documents.show', $model) }}" class="btn btn-sm btn-outline-secondary" title="عرض" aria-label="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>

                                @if (!$model->isExternal())
                                    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'edit')
                                        @if ($model->status !== 'approved')
                                            <a href="{{ route('admin.project-docs.documents.edit', $model) }}" class="btn btn-sm btn-outline-primary" title="تعبئة/تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                        @endif
                                    @endcanPermission
                                    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
                                        <a href="{{ route('admin.project-docs.documents.print', $model) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="طباعة A4" aria-label="طباعة"><i class="bi bi-printer" aria-hidden="true"></i></a>
                                    @endcanPermission
                                    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
                                        <form method="POST" action="{{ route('admin.project-docs.documents.duplicate', $model) }}" class="d-inline"
                                              onsubmit="return confirm('إنشاء نسخة جديدة من هذه الوثيقة بكل محتواها لتعديلها؟')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" title="نسخ كوثيقة جديدة" aria-label="نسخ"><i class="bi bi-files" aria-hidden="true"></i></button>
                                        </form>
                                    @endcanPermission
                                @else
                                    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
                                        @if ($row['file'])
                                            <a href="{{ $row['file']->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" title="عرض الملف في تبويب جديد" aria-label="عرض الملف"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                            <a href="{{ route('admin.project-docs.documents.file', $model) }}" class="btn btn-sm btn-outline-success" title="تنزيل الملف" aria-label="تنزيل"><i class="bi bi-download" aria-hidden="true"></i></a>
                                        @else
                                            <span class="btn btn-sm btn-outline-secondary disabled" title="الملف غير متاح"><i class="bi bi-file-earmark-x" aria-hidden="true"></i></span>
                                        @endif
                                    @endcanPermission
                                @endif

                                @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'delete')
                                    @if ($model->status === 'draft')
                                        <form method="POST" action="{{ route('admin.project-docs.documents.destroy', $model) }}" class="d-inline"
                                              onsubmit="return confirm('حذف الوثيقة نهائياً؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="حذف المسودة" aria-label="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                    @endcanPermission
                                @endif

                            {{-- ===== ملف أرشيف مستقل ===== --}}
                            @else
                                <a href="{{ $model->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" title="عرض الملف في تبويب جديد" aria-label="عرض الملف"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                <a href="{{ route('admin.documents-archive.download', $model) }}" class="btn btn-sm btn-outline-success" title="تنزيل" aria-label="تنزيل"><i class="bi bi-download" aria-hidden="true"></i></a>
                                @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'edit')
                                    <a href="{{ route('admin.documents-archive.edit', $model) }}" class="btn btn-sm btn-outline-primary" title="تعديل بيانات الملف" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @endcanPermission
                                @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
                                    <form method="POST" action="{{ route('admin.documents-archive.convert', $model) }}" class="d-inline"
                                          onsubmit="return confirm('تحويل هذا الملف إلى وثيقة نظامية تدخل دورة المسودة → المراجعة → الاعتماد؟')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" title="تحويل إلى وثيقة نظامية" aria-label="تحويل"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></button>
                                    </form>
                                @endcanPermission
                                @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'delete')
                                    <form method="POST" action="{{ route('admin.documents-archive.destroy', $model) }}" class="d-inline"
                                          onsubmit="return confirm('حذف هذا الملف من الأرشيف؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="حذف الملف" aria-label="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endcanPermission
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="bi bi-folder2-open d-block fs-2 text-muted mb-2" aria-hidden="true"></i>
                            <strong>لم نعثر على وثائق بهذه المعايير</strong>
                            <div class="small text-muted mt-1">جرّب تغيير الفترة أو إزالة أحد الفلاتر.</div>
                            <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-sm btn-outline-primary mt-3">مسح الفلاتر</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="documents-pagination">
        <div class="text-muted small">{{ $documents->total() }} نتيجة</div>
        <div>{{ $documents->links() }}</div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const pathSelect = document.getElementById('f-path');
    const projectSearch = document.getElementById('f-project-search');
    const projectSelect = document.getElementById('f-project');
    if (!pathSelect || !projectSearch || !projectSelect) return;

    const projectOptions = Array.from(projectSelect.options).slice(1);
    const applyProjectOptions = () => {
        const pathId = pathSelect.value;
        const term = projectSearch.value.trim().toLocaleLowerCase();

        projectOptions.forEach(option => {
            const matchesPath = !pathId || option.dataset.pathId === pathId;
            const matchesSearch = !term || option.text.toLocaleLowerCase().includes(term);
            option.hidden = !matchesPath || !matchesSearch;
        });

        const selected = projectSelect.selectedOptions[0];
        if (selected?.hidden) projectSelect.value = '';
    };

    pathSelect.addEventListener('change', applyProjectOptions);
    projectSearch.addEventListener('input', applyProjectOptions);
    applyProjectOptions();
})();
</script>
@endpush
