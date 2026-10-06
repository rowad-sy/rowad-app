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
                        @if ($path->code)
                            <span class="pc-col-code">{{ $path->code }}</span>
                        @endif
                    </div>
                    <div class="pc-col-meta">
                        <span class="pc-col-count">{{ $path->projects->count() }} مشروع</span>
                        @if ($canCreateProject)
                            <a href="{{ route('admin.projects.create', ['path' => $path->id]) }}"
                               class="btn btn-sm btn-light border" title="إضافة مشروع إلى مسار {{ $path->name }}" aria-label="إضافة مشروع إلى مسار {{ $path->name }}">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> مشروع
                            </a>
                        @endif
                    </div>
                </div>
                <ol class="pc-list">
                    @forelse ($path->projects as $project)
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
                    @empty
                        <li class="pc-empty">لا توجد مشاريع في هذا المسار.</li>
                    @endforelse
                </ol>
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
