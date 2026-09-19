@extends('admin.layouts.master')

@section('title', 'المشاريع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المشاريع</h4>
        <p>إدارة المشاريع التي تعمل عليها المؤسسة</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مشروع
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مشروع..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
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
                        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <x-audit-history :model="'App\Models\Admin\Project'" :model-id="$project->id" />
                        <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        لا توجد مشاريع
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $projects->links() }}
    </div>
</div>
@endsection
