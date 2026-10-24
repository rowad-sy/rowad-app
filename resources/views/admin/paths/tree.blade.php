@extends('admin.layouts.master')

@section('title', 'المسارات والمشاريع')

@section('content')
<x-page-header :title="'المسارات والمشاريع'" :description="'لكل مسار عمود مستقل ومشاريعه مرقمة بحالة كل مشروع — يمكن إضافة مشروع جديد من داخل المسار نفسه.'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'المسارات والمشاريع']]">
    <div class="d-flex flex-wrap gap-2">
        @canPermission('App\Models\Admin\ProjectPath', 'view')
        <a href="{{ route('admin.paths.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-signpost-split me-1"></i> إدارة المسارات
        </a>
        @endcanPermission
        <a href="{{ route('admin.paths.export.excel', request()->only('status')) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        @if ($orphanProjects->isNotEmpty())
            <a href="{{ route('admin.paths.export.excel', array_merge(request()->only('status'), ['include_orphans' => 1])) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-plus me-1"></i> Excel مع مشاريع بلا مسار
            </a>
        @endif
        <a href="{{ route('admin.paths.export.pdf', request()->only('status')) }}" target="_blank" class="btn btn-brand btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</x-page-header>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($statuses as $key => $label)
        <a href="{{ route('admin.paths.tree', array_filter(['status' => request('status') === $key ? null : $key])) }}"
           class="text-decoration-none">
            <span class="status-pill {{ \App\Models\Admin\Project::STATUS_BADGES[$key] }} {{ request('status') === $key ? 'pc-active' : '' }}">
                {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
            </span>
        </a>
    @endforeach
    @if (request('status'))
        <a href="{{ route('admin.paths.tree') }}" class="text-decoration-none align-self-center small">
            <i class="bi bi-x-circle me-1"></i>مسح الفلتر
        </a>
    @endif
</div>

@php
    $canCreateProject = \App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Project', 'create');
@endphp

@if ($paths->isEmpty() && $orphanProjects->isEmpty())
    <div class="table-container text-center p-5 text-muted">
        <i class="bi bi-diagram-2 fs-1 d-block mb-2 opacity-50"></i>
        لا توجد مسارات ولا مشاريع بعد.
    </div>
@else
    <div class="pc-grid">
        @foreach ($paths as $path)
            <div class="pc-col">
                <div class="pc-col-head">
                    <div class="pc-col-title">
                        <span>مسار {{ $path->name }}</span>
                        <span class="pc-col-rank">{{ $path->sort_order ?? '—' }}</span>
                        @if ($path->code)
                            <span class="pc-col-code">{{ $path->code }}</span>
                        @endif
                    </div>
                    <div class="pc-col-meta">
                        <span class="pc-col-count">{{ $path->projects->count() }} مشروع</span>
                        @if ($canCreateProject)
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-light border"
                                        data-bs-toggle="collapse" data-bs-target="#add-project-{{ $path->id }}"
                                        aria-expanded="false" aria-controls="add-project-{{ $path->id }}"
                                        title="إضافة مشروع إلى مسار {{ $path->name }}">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> مشروع
                                </button>
                                <a href="{{ route('admin.projects.create', ['path' => $path->id]) }}"
                                   class="btn btn-sm btn-light border" title="فتح نموذج المشروع الكامل" aria-label="فتح نموذج المشروع الكامل">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                <ol class="pc-list">
                    @forelse ($path->projects as $project)
                        <li>
                            <a href="{{ route('admin.projects.overview', $project) }}" class="pc-item">
                                <span class="pc-item-order">{{ $project->sort_order ?? $loop->iteration }}</span>
                                <span class="pc-item-name">
                                    {{ $project->name }}
                                    @if ($project->code)
                                        <span class="pc-item-code">{{ $project->code }}</span>
                                    @endif
                                </span>
                                <span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="pc-empty">لا توجد مشاريع في هذا المسار.</li>
                    @endforelse
                </ol>
                @if ($canCreateProject)
                    @php
                        $isThisPathOldInput = (int) old('path_id', 0) === (int) $path->id;
                        $nextProjectOrder = max(1, ((int) $path->projects->max('sort_order')) + 1);
                    @endphp
                    <div class="collapse {{ $isThisPathOldInput && $errors->any() ? 'show' : '' }}" id="add-project-{{ $path->id }}">
                        <form method="POST" action="{{ route('admin.projects.store') }}" class="pc-inline-project-form">
                            @csrf
                            <input type="hidden" name="path_id" value="{{ $path->id }}">
                            <input type="hidden" name="return_to" value="paths_tree">
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="project-name-{{ $path->id }}">اسم المشروع</label>
                                <input id="project-name-{{ $path->id }}" type="text" name="name" maxlength="255" required
                                       class="form-control form-control-sm @if ($isThisPathOldInput) @error('name') is-invalid @enderror @endif"
                                       value="{{ $isThisPathOldInput ? old('name') : '' }}">
                                @if ($isThisPathOldInput) @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="project-order-{{ $path->id }}">الترتيب</label>
                                    <input id="project-order-{{ $path->id }}" type="number" name="sort_order" min="1" max="9999" required
                                           class="form-control form-control-sm @if ($isThisPathOldInput) @error('sort_order') is-invalid @enderror @endif"
                                           value="{{ $isThisPathOldInput ? old('sort_order', $nextProjectOrder) : $nextProjectOrder }}">
                                    @if ($isThisPathOldInput) @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="project-code-{{ $path->id }}">الكود</label>
                                    <input id="project-code-{{ $path->id }}" type="text" name="code" maxlength="100"
                                           class="form-control form-control-sm @if ($isThisPathOldInput) @error('code') is-invalid @enderror @endif"
                                           value="{{ $isThisPathOldInput ? old('code') : '' }}">
                                    @if ($isThisPathOldInput) @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="project-status-{{ $path->id }}">الحالة</label>
                                <select id="project-status-{{ $path->id }}" name="status" class="form-select form-select-sm">
                                    @foreach (\App\Models\Admin\Project::STATUSES as $key => $label)
                                        <option value="{{ $key }}" {{ ($isThisPathOldInput ? old('status', 'active') : 'active') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>إضافة إلى هذا المسار
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach

        @if ($orphanProjects->isNotEmpty())
            <div class="pc-col pc-col-orphan">
                <div class="pc-col-head">
                    <div class="pc-col-title">
                        <span><i class="bi bi-inbox me-1" aria-hidden="true"></i>مشاريع بدون مسار</span>
                    </div>
                    <div class="pc-col-meta">
                        <span class="pc-col-count">{{ $orphanProjects->count() }} مشروع</span>
                        @if ($canCreateProject)
                            <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-light border" title="إضافة مشروع">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> مشروع
                            </a>
                        @endif
                    </div>
                </div>
                <ol class="pc-list">
                    @foreach ($orphanProjects as $project)
                        <li>
                            <a href="{{ route('admin.projects.overview', $project) }}" class="pc-item">
                                <span class="pc-item-name">
                                    {{ $project->name }}
                                    @if ($project->code)
                                        <span class="pc-item-code">{{ $project->code }}</span>
                                    @endif
                                </span>
                                <span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
@endif
@endsection
