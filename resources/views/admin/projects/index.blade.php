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

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مشروع..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المشروع</th>
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
                    <td class="text-muted">{{ $project->code ?? '—' }}</td>
                    <td class="text-muted">{{ $project->path?->name ?? '—' }}</td>
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
                <x-empty-row colspan="7" icon="bi-inbox" title="لا توجد مشاريع" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3">
        {{ $projects->links() }}
    </div>
</div>
@endsection
