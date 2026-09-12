@extends('admin.layouts.master')

@section('title', 'وثائق المشروع')

@section('content')
<div class="page-header">
    <h4>وثائق المشروع</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / وثائق المشروع
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="status" class="form-select" style="width: auto;">
            <option value="">كل الحالات</option>
            @foreach (\App\Models\Admin\ProjectDocs\AnnexDocument::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ ($status ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="project_id" class="form-select" style="width: auto;">
            <option value="">كل المشاريع</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ ($projectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary">تصفية</button>
    </form>
    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
    <a href="{{ route('admin.project-docs.documents.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> وثيقة جديدة
    </a>
    @endcanPermission
    <a href="{{ route('admin.project-docs.documents.help') }}" class="btn btn-outline-info">
        <i class="bi bi-question-circle"></i> معلومات
    </a>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>الوثيقة</th>
                    <th>القالب</th>
                    <th>المشروع</th>
                    <th>المركز</th>
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
                    <td>{{ $document->center?->name }}</td>
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
                        <a href="{{ route('admin.project-docs.documents.show', $document) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                            <i class="bi bi-eye"></i>
                        </a>
                        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'edit')
                        @if ($document->status !== 'approved')
                        <a href="{{ route('admin.project-docs.documents.edit', $document) }}" class="btn btn-sm btn-outline-primary" title="تعبئة/تعديل">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                        @endcanPermission
                        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
                        <a href="{{ route('admin.project-docs.documents.print', $document) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="طباعة A4">
                            <i class="bi bi-printer"></i>
                        </a>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">لا توجد وثائق بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $documents->links() }}</div>
@endsection