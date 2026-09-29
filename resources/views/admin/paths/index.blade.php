@extends('admin.layouts.master')

@section('title', 'المسارات')

@section('content')
<x-page-header :title="'المسارات'" :description="'كل مسار يجمع عدداً من المشاريع — الإضافة والتعديل من اختصاص إدارة المشاريع'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'المسارات']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.paths.tree') }}" class="btn btn-outline-brand">
            <i class="bi bi-diagram-2 me-1"></i> عرض الشجرة
        </a>
        @canPermission('App\Models\Admin\ProjectPath', 'create')
        <a href="{{ route('admin.paths.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg me-1"></i> إضافة مسار
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مسار..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
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
                        @canPermission('App\Models\Admin\ProjectPath', 'edit')
                        <a href="{{ route('admin.paths.edit', $path) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        <x-audit-history :model="'App\Models\Admin\ProjectPath'" :model-id="$path->id" />
                        @canPermission('App\Models\Admin\ProjectPath', 'delete')
                        <form method="POST" action="{{ route('admin.paths.destroy', $path) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار؟ لن تُحذف مشاريعه، فقط سيُفصل ارتباطها به.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="6" icon="bi-signpost-split" title="لا توجد مسارات بعد" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3">{{ $paths->links() }}</div>
</div>
@endsection
