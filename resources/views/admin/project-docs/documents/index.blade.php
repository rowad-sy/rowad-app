@extends('admin.layouts.master')

@section('title', 'وثائق المشروع')

@section('content')
<x-page-header :title="'وثائق المشروع'"
               :breadcrumb="[['label' => 'وثائق المشروع']]">
    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
        <a href="{{ route('admin.project-docs.documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> وثيقة جديدة
        </a>
        @endcanPermission
        <a href="{{ route('admin.project-docs.documents.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle"></i> معلومات
        </a>
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-status">الحالة</label>
            <select id="f-status" name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ ($status ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-project_id">المشروع</label>
            <select id="f-project_id" name="project_id" class="form-select">
            <option value="">كل المشاريع</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ ($projectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
        </div>
    </x-filter-bar>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الوثيقة</th>
                    <th>القالب</th>
                    <th>المشروع</th>
                    <th>الفترة</th>
                    <th>الحالة</th>
                    <th>المنشئ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                <tr>
                    <td>
                        <a href="{{ route('admin.project-docs.documents.show', $document) }}" class="fw-semibold text-decoration-none">
                            {{ $document->title ?: ($document->template->title_ar ?? 'وثيقة') }}
                        </a>
                        <div class="text-muted small" dir="ltr">#{{ $document->id }} · V{{ $document->template_version }}</div>
                    </td>
                    <td>{{ $document->template?->title_ar }}</td>
                    <td>{{ $document->project?->name }}</td>
                    <td>{{ $document->period }}</td>
                    <td>
                        @php $badge = [
                            'draft' => 'secondary',
                            'under_review' => 'warning text-dark',
                            'approved' => 'success',
                            'rejected' => 'danger',
                        ][$document->status] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $badge }}">{{ \App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES[$document->status] ?? $document->status }}</span>
                    </td>
                    <td>{{ $document->creator?->name }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.project-docs.documents.show', $document) }}" class="btn btn-sm btn-outline-secondary" title="عرض" aria-label="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'edit')
                        @if ($document->status !== 'approved')
                        <a href="{{ route('admin.project-docs.documents.edit', $document) }}" class="btn btn-sm btn-outline-primary" title="تعبئة/تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endif
                        @endcanPermission
                        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
                        <a href="{{ route('admin.project-docs.documents.print', $document) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="طباعة A4" aria-label="طباعة"><i class="bi bi-printer" aria-hidden="true"></i></a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
                        <form method="POST" action="{{ route('admin.project-docs.documents.duplicate', $document) }}" class="d-inline"
                              onsubmit="return confirm('إنشاء نسخة جديدة من هذه الوثيقة بكل محتواها لتعديلها؟')">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" title="نسخ كوثيقة جديدة">
                                <i class="bi bi-files"></i>
                            </button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <x-empty-row colspan="7" title="لا توجد وثائق بعد" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $documents->links() }}</div>
@endsection