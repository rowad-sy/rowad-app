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
                    'project_id' => $filters['project_id'], 'center_id' => $filters['center_id'],
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

<div class="table-container">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-q">بحث</label>
            <input type="text" id="f-q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="عنوان أو وصف…">
        </div>
        @if ($canDocs)
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
        @if ($canArchive)
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
            <label class="form-label" for="f-project">المشروع</label>
            <select id="f-project" name="project_id" class="form-select">
                <option value="">كل المشاريع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ (int) $filters['project_id'] === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-year">السنة</label>
            <select id="f-year" name="year" class="form-select">
                <option value="">كل السنوات</option>
                @foreach (range((int) date('Y'), (int) date('Y') - 15) as $y)
                    <option value="{{ $y }}" {{ $filters['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الوثيقة</th>
                    <th>المصدر</th>
                    <th>المشروع</th>
                    <th class="d-none d-xl-table-cell">المركز</th>
                    <th class="d-none d-lg-table-cell">التاريخ</th>
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
                        <td>{{ $row['project'] ?? '—' }}</td>
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
                    <x-empty-row colspan="8" title="لا توجد وثائق مطابقة" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي الظاهر: {{ $documents->total() }}</div>
        <div>{{ $documents->links() }}</div>
    </div>
</div>
@endsection
