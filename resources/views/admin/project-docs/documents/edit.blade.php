@extends('admin.layouts.master')

@section('title', 'تعبئة وثيقة')

@section('content')
@php
    $isCreator = auth()->id() === (int) $document->created_by;
    $pageCount = max(1, (int) $document->page_count);
    $typeLabels = ['fields' => 'حقول', 'paragraph' => 'فقرة', 'table' => 'جدول', 'list' => 'قائمة'];
@endphp

<x-page-header :title="'تعبئة الوثيقة: ' . ($document->title ?: $document->template->title_ar)"
               :breadcrumb="[['label' => 'الوثائق', 'url' => route('admin.project-docs.documents.index')], ['label' => 'عرض', 'url' => route('admin.project-docs.documents.show', $document)], ['label' => 'تعبئة']]">
    <x-slot:meta><div class="mt-2"><x-status-badge>{{ $pageCount }} {{ Str::plural('صفحة', $pageCount) }}</x-status-badge></div></x-slot:meta>
</x-page-header>

@if ($document->status === 'under_review')
<div class="alert alert-warning"><i class="bi bi-clock-history"></i> الوثيقة <strong>قيد المراجعة</strong> — أقسامها مقفلة، ويمكن فقط رفضها/إعادة فتحها من صفحة العرض.</div>
@endif

@if ($document->status === 'rejected')
<div class="alert alert-danger"><i class="bi bi-x-circle"></i> الوثيقة <strong>مرفوضة</strong> — راجع ملاحظة الرفض في صفحة العرض ثم أعد التعبئة. الأقسام مقفلة حتى تُعاد الفتح.</div>
@endif

<form method="POST" action="{{ route('admin.project-docs.documents.update', $document) }}">
    @csrf
    @method('PUT')

    @if ($document->status === 'draft')
    <div class="row align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label mb-1 small text-muted">عدد صفحات الوثيقة (قابل للتعديل)</label>
            <input type="number" name="page_count" min="1" max="60" value="{{ $pageCount }}" class="form-control form-control-sm" style="width: 120px;">
        </div>
    </div>
    @endif

    @if ($pageCount > 1)
    <ul class="nav nav-tabs mb-3" role="tablist">
        @for ($p = 1; $p <= $pageCount; $p++)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $p === 1 ? 'active' : '' }}" type="button"
                        data-bs-toggle="tab" data-bs-target="#page-{{ $p }}" role="tab">
                    صفحة {{ $p }}
                </button>
            </li>
        @endfor
    </ul>
    @endif

    <div class="tab-content">
        @for ($p = 1; $p <= $pageCount; $p++)
            <div class="tab-pane fade {{ $p === 1 ? 'show active' : '' }}" id="page-{{ $p }}" role="tabpanel">
                @php
                    $pageSections = $document->sections()->filter(function ($section) use ($document, $p) {
                        $block = $document->blocks->firstWhere('block_key', $section['key'] ?? null);
                        return ($block?->page_number ?? 1) == $p;
                    });
                @endphp

                @if ($pageSections->isEmpty())
                    <div class="alert alert-light text-muted text-center py-4">
                        <i class="bi bi-inbox"></i> لا توجد أقسام مخصصة لهذه الصفحة بعد.
                        <br><small>يمكنك تعيين أقسام للصفحة {{ $p }} من خلال التحديث أدناه.</small>
                    </div>
                @endif

                @foreach ($pageSections as $section)
                    @php
                        $block = $document->blocks->firstWhere('block_key', $section['key'] ?? null);
                        $editable = !$block?->locked || $isCreator;
                        $filled = $block && $document->isBlockComplete($block);
                    @endphp
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold">{{ $section['title'] ?? $section['key'] }}</span>
                                <span class="badge bg-light text-dark border">{{ $typeLabels[$section['type']] ?? $section['type'] }}</span>
                                @if (!empty($section['assignee_role']))
                                    <x-status-badge tone="info">قسم يُعبأ بواسطة: {{ $section['assignee_role'] }}</x-status-badge>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if ($filled)
                                    <span class="badge bg-success-subtle text-success">معبأ</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">ناقص</span>
                                @endif
                                @if ($block?->locked)
                                    <x-status-badge tone="warning"><i class="bi bi-lock"></i> مقفول</x-status-badge>
                                @endif
                                @if ($pageCount > 1 && $editable && $document->status === 'draft')
                                    <select name="lock[{{ $section['key'] }}_page]" class="form-select form-select-sm" style="width: 100px;">
                                        @for ($pg = 1; $pg <= $pageCount; $pg++)
                                            <option value="{{ $pg }}" {{ ($block?->page_number ?? 1) == $pg ? 'selected' : '' }}>
                                                صفحة {{ $pg }}
                                            </option>
                                        @endfor
                                    </select>
                                @endif
                                @if ($editable && $document->status === 'draft')
                                    <div class="form-check form-switch form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" name="lock[{{ $section['key'] }}]" value="1"
                                               id="lock_{{ $section['key'] }}" {{ $block?->locked ? 'checked' : '' }}>
                                        <label class="form-check-label" for="lock_{{ $section['key'] }}">قفل</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body" id="section-{{ $section['key'] }}">
                            @if ($editable && $document->status === 'draft')
                                @include('admin.project-docs.partials.block-edit', ['section' => $section, 'block' => $block])
                            @else
                                @include('admin.project-docs.partials.block-view', ['section' => $section, 'block' => $block])
                            @endif
                            @if ($block?->updated_by)
                                <div class="small text-muted mt-2">
                                    <i class="bi bi-person"></i> آخر تحديث: {{ $block->updater?->name ?? '—' }} · {{ $block->updated_at?->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endfor
    </div>

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save"></i> حفظ الأقسام</button>
        <a href="{{ route('admin.project-docs.documents.show', $document) }}" class="btn btn-outline-secondary">عرض بدون حفظ</a>
    </div>
</form>

@endsection

@push('scripts')
@include('admin.project-docs.partials.block-edit-scripts')
@endpush
