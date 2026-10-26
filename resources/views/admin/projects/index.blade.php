@extends('admin.layouts.master')

@section('title', 'المشاريع')

@section('content')
<x-page-header :title="'المشاريع'" :description="'إدارة المشاريع التي تعمل عليها المؤسسة'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'المشاريع']]">
    @canPermission('App\Models\Admin\Project', 'create')
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مشروع
    </a>
    @endcanPermission
</x-page-header>

<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="تصفية المشاريع حسب الحالة">
    <a href="{{ route('admin.projects.index', array_filter(['search' => $search, 'per_page' => $perPage])) }}"
       class="nav-link border rounded-pill px-3 py-2 {{ $status === null ? 'active bg-primary text-white' : 'bg-body text-body' }}">
        الكل <span class="badge {{ $status === null ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary' }} ms-1">{{ $totalProjects }}</span>
    </a>
    @foreach (\App\Models\Admin\Project::STATUSES as $key => $label)
        <a href="{{ route('admin.projects.index', array_filter(['status' => $key, 'search' => $search, 'per_page' => $perPage])) }}"
           class="nav-link border rounded-pill px-3 py-2 {{ $status === $key ? 'active bg-primary text-white' : 'bg-body text-body' }}">
            {{ $label }}
            <span class="badge {{ $status === $key ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary' }} ms-1">{{ $statusCounts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

<div class="table-container">
    <x-filter-bar>
            <div class="col-12 col-md-5 filter-field">
                <label class="form-label" for="project-search">بحث عن مشروع</label>
                <div class="input-group">
                    <input id="project-search" type="search" name="search" class="form-control" placeholder="اكتب اسم المشروع…" value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit" aria-label="بحث">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-6 col-md-3 filter-field">
                <label class="form-label" for="projects-per-page">عدد المشاريع في الصفحة</label>
                <select id="projects-per-page" name="per_page" class="form-select">
                    @foreach ($perPageOptions as $count)
                        <option value="{{ $count }}" {{ $perPage === $count ? 'selected' : '' }}>{{ $count }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المشروع</th>
                <th>الترتيب</th>
                <th>الكود</th>
                <th>المسار</th>
                <th>الحالة</th>
                <th>المراكز النشطة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projects as $project)
                <tr>
                    <td>{{ $project->id }}</td>
                    <td class="fw-medium">{{ $project->name }}</td>
                    <td class="text-muted">{{ $project->sort_order ?? '—' }}</td>
                    <td class="text-muted">{{ $project->code ?? '—' }}</td>
                    <td class="text-muted">
                        @if ($project->path)
                            <span class="badge bg-light text-dark border me-1">{{ $project->path->sort_order ?? '—' }}</span>
                            {{ $project->path->name }}
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span></td>
                    <td>
                        @foreach ($project->centers as $center)
                            <span class="badge bg-light text-dark border me-1">{{ $center->name }}</span>
                        @endforeach
                        @if ($project->centers->isEmpty())
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.projects.overview', $project) }}" class="btn btn-sm btn-outline-brand" title="صفحة المشروع">
                            <i class="bi bi-box-arrow-up-left"></i>
                        </a>
                        @canPermission('App\Models\Admin\Project', 'edit')
                        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        <x-audit-history :model="'App\Models\Admin\Project'" :model-id="$project->id" />
                        @canPermission('App\Models\Admin\Project', 'delete')
                        <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="8" icon="bi-inbox" title="لا توجد مشاريع" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3">
        <div class="text-muted small mb-2">إجمالي المشاريع: {{ $projects->total() }}</div>
        {{ $projects->links() }}
    </div>
</div>
@endsection
