@extends('admin.layouts.master')

@section('title', 'الإدارات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الإدارات</h4>
        <p>إدارة الأقسام والإدارات التابعة للمؤسسة</p>
    </div>
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة إدارة
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن إدارة..." value="{{ $search }}">
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
                <th>الاسم (AR)</th>
                <th>الاسم (EN)</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departments as $department)
                <tr>
                    <td>{{ $department->id }}</td>
                    <td class="fw-medium">{{ $department->name_ar }}</td>
                    <td>{{ $department->name_en ?? '—' }}</td>
                    <td>
                        @if ($department->is_active)
                            <span class="badge bg-success">فعال</span>
                        @else
                            <span class="badge bg-secondary">غير فعال</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذه الإدارة؟')">
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
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        لا توجد إدارات
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $departments->links() }}
    </div>
</div>
@endsection
