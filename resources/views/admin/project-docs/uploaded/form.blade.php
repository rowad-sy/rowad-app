@extends('admin.layouts.master')

@section('title', $doc ? 'تعديل وثيقة مؤرشفة' : 'رفع وثيقة PDF')

@section('content')
<x-page-header :title="$doc ? 'تعديل وثيقة مؤرشفة' : 'رفع وثيقة إلى الأرشيف'"
               :description="'ملف PDF جاهز (وثيقة قديمة، قرار، عقد…) يُعرض ويُنزَل كما هو'"
               :breadcrumb="[['label' => 'أرشيف PDF', 'url' => route('admin.documents-archive.index')], ['label' => $doc ? 'تعديل' : 'جديد']]" />

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<div class="form-card mb-3">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $doc ? route('admin.documents-archive.update', $doc) : route('admin.documents-archive.store') }}">
        @csrf
        @if ($doc) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">عنوان الوثيقة <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title', $doc->title ?? '') }}" class="form-control @error('title') is-invalid @enderror" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">التصنيف (تبويب) <span class="text-danger">*</span></label>
                <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                    @foreach (\App\Models\Admin\ProjectDocs\UploadedDocument::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $doc->category ?? 'other') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">تاريخ الوثيقة</label>
                <input type="date" name="document_date" value="{{ old('document_date', isset($doc) && $doc->document_date ? $doc->document_date->format('Y-m-d') : '') }}"
                       class="form-control @error('document_date') is-invalid @enderror">
                @error('document_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select">
                    <option value="">عام (بلا مشروع)</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected((int) old('project_id', $doc->project_id ?? 0) === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select">
                    <option value="">عام (بلا مركز)</option>
                    @foreach ($centers as $c)
                        <option value="{{ $c->id }}" @selected((int) old('center_id', $doc->center_id ?? 0) === $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">الملف (PDF) <span class="text-danger">*</span></label>
                @if ($doc)
                    <div class="small text-muted mb-1">الحالي: {{ $doc->file_name }} ({{ $doc->sizeLabel() }}) — اتركه فارغاً للإبقاء عليه.</div>
                @endif
                <input type="file" name="file" accept="application/pdf" class="form-control @error('file') is-invalid @enderror" {{ $doc ? '' : 'required' }}>
                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">وصف مختصر</label>
            <textarea name="description" rows="2" class="form-control">{{ old('description', $doc->description ?? '') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> حفظ</button>
            <a href="{{ route('admin.documents-archive.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection
