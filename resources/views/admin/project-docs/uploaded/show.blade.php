@extends('admin.layouts.master')

@section('title', $document->title)

@section('content')
<x-page-header :title="$document->title"
               :breadcrumb="[['label' => 'أرشيف PDF', 'url' => route('admin.documents-archive.index')], ['label' => '#' . $document->id]]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.documents-archive.download', $document) }}" class="btn btn-success"><i class="bi bi-download me-1"></i> تنزيل PDF</a>
        @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'edit')
        <a href="{{ route('admin.documents-archive.edit', $document) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
        @endcanPermission
        <a href="{{ route('admin.documents-archive.index', ['category' => $document->category]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i> عودة
        </a>
    </div>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-9">
        <div class="table-container p-0 overflow-hidden">
            <object data="{{ $document->fileUrl() }}" type="application/pdf" width="100%" style="height: 82vh; display: block;">
                <div class="p-4 text-center">
                    لا يمكن عرض المعاينة داخل المتصفح؟
                    <a href="{{ $document->fileUrl() }}" target="_blank" class="btn btn-sm btn-primary mt-2">فتح في تبويب جديد</a>
                </div>
            </object>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">بيانات الوثيقة</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0 small">
                    <tr><th>التصنيف</th><td><span class="badge bg-info text-dark">{{ $document->categoryLabel() }}</span></td></tr>
                    <tr><th>التاريخ</th><td>{{ optional($document->document_date)->format('Y-m-d') ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $document->project?->name ?? 'عام' }}</td></tr>
                    <tr><th>المركز</th><td>{{ $document->center?->name ?? 'عام' }}</td></tr>
                    <tr><th>الملف</th><td>{{ $document->file_name ?? '—' }}<br><span class="text-muted">{{ $document->sizeLabel() }}</span></td></tr>
                    <tr><th>رفعها</th><td>{{ $document->uploader?->name ?? '—' }}</td></tr>
                    <tr><th>تاريخ الرفع</th><td>{{ $document->created_at?->format('Y-m-d') }}</td></tr>
                </table>
                @if ($document->description)
                    <div class="mt-2 small text-muted">{{ $document->description }}</div>
                @endif
                <div class="mt-2">
                    <x-audit-history :model="'App\Models\Admin\ProjectDocs\UploadedDocument'" :model-id="$document->id" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
