@extends('admin.layouts.master')

@section('title', 'المسارات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4>المسارات</h4>
        <p>كل مسار يجمع عدداً من المشاريع — الإضافة والتعديل من اختصاص إدارة المشاريع</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.paths.tree') }}" class="btn btn-outline-brand">
            <i class="bi bi-diagram-2 me-1"></i> عرض الشجرة
        </a>
        <a href="{{ route('admin.paths.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg me-1"></i> إضافة مسار
        </a>
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مسار..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>اسم المسار</th>
                <th>الكود</th>
                <th>الوصف</th>
                <th class="text-center">المشاريع</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paths as $path)
                <tr>
                    <td>{{ $path->id }}</td>
                    <td class="fw-medium">{{ $path->name }}</td>
                    <td class="text-muted">{{ $path->code ?? '—' }}</td>
                    <td class="text-muted" style="max-width: 280px;">
                        <span class="d-inline-block text-truncate" style="max-width: 280px;">{{ $path->description ?? '—' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="tree-count-badge">{{ $path->projects_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.paths.edit', $path) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <x-audit-history :model="'App\Models\Admin\ProjectPath'" :model-id="$path->id" />
                        <form method="POST" action="{{ route('admin.paths.destroy', $path) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار؟ لن تُحذف مشاريعه، فقط سيُفصل ارتباطها به.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-signpost-split fs-3 d-block mb-2"></i>
                        لا توجد مسارات بعد
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">{{ $paths->links() }}</div>
</div>
@endsection
