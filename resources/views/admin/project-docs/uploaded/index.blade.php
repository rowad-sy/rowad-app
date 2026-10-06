@extends('admin.layouts.master')

@section('title', 'أرشيف الوثائق (PDF)')

@section('content')
<x-page-header :title="'أرشيف الوثائق — ملفات PDF'" :description="'الوثائق القديمة والمستندات المرفوعة جاهزة — مستقلة عن نظام وثائق المشاريع المُعبَّأة'"
               :breadcrumb="[['label' => 'الوثائق'], ['label' => 'أرشيف PDF']]">
    <div class="d-flex gap-2">
        @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'create')
        <a href="{{ route('admin.documents-archive.create') }}" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-1"></i> رفع وثيقة PDF
        </a>
        @endcanPermission
        <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i> وثائق المشاريع (النظام المعتمد)
        </a>
    </div>
</x-page-header>

<ul class="nav nav-pills mb-3 flex-nowrap overflow-auto">
    <li class="nav-item">
        <a href="{{ route('admin.documents-archive.index', array_filter(['q' => request('q'), 'year' => request('year'), 'project_id' => request('project_id')])) }}"
           class="nav-link {{ $category === null ? 'active' : '' }}">
            الكل <span class="badge bg-light text-dark ms-1">{{ $totalCount }}</span>
        </a>
    </li>
    @foreach (\App\Models\Admin\ProjectDocs\UploadedDocument::CATEGORIES as $key => $label)
        <li class="nav-item">
            <a href="{{ route('admin.documents-archive.index', array_filter(['category' => $key, 'q' => request('q'), 'year' => request('year'), 'project_id' => request('project_id')])) }}"
               class="nav-link {{ $category === $key ? 'active' : '' }}">
                {{ $label }} <span class="badge bg-light text-dark ms-1">{{ $tabCounts[$key] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="table-container">
    <x-filter-bar>
        <div class="col-md-3">
            <label class="form-label">بحث</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="عنوان أو وصف…">
        </div>
        <div class="col-md-2">
            <label class="form-label">السنة</label>
            <select name="year" class="form-select form-select-sm">
                <option value="">الكل</option>
                @foreach (range((int) date('Y'), (int) date('Y') - 15) as $y)
                    <option value="{{ $y }}" {{ (int) request('year') === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select form-select-sm">
                <option value="">الكل</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" {{ (int) request('project_id') === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">المركز</label>
            <select name="center_id" class="form-select form-select-sm">
                <option value="">الكل</option>
                @foreach ($centers as $c)
                    <option value="{{ $c->id }}" {{ (int) request('center_id') === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>العنوان</th>
                    <th>التصنيف</th>
                    <th>تاريخ الوثيقة</th>
                    <th>المشروع</th>
                    <th>المركز</th>
                    <th>الحجم</th>
                    <th>رفعها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    <tr>
                        <td>{{ $doc->id }}</td>
                        <td class="fw-medium">
                            <a href="{{ route('admin.documents-archive.show', $doc) }}" class="text-decoration-none">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> {{ $doc->title }}
                            </a>
                        </td>
                        <td><span class="badge bg-info text-dark">{{ $doc->categoryLabel() }}</span></td>
                        <td>{{ optional($doc->document_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $doc->project?->name ?? '—' }}</td>
                        <td>{{ $doc->center?->name ?? '—' }}</td>
                        <td>{{ $doc->sizeLabel() }}</td>
                        <td>{{ $doc->uploader?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.documents-archive.show', $doc) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.documents-archive.download', $doc) }}" class="btn btn-sm btn-outline-success" aria-label="تنزيل" title="تنزيل"><i class="bi bi-download"></i></a>
                            @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'edit')
                            <a href="{{ route('admin.documents-archive.edit', $doc) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\ProjectDocs\UploadedDocument'" :model-id="$doc->id" />
                            @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'delete')
                            <form method="POST" action="{{ route('admin.documents-archive.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('حذف هذه الوثيقة من الأرشيف؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-file-earmark-pdf" title="لا توجد وثائق في هذا التصنيف بعد" />
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
