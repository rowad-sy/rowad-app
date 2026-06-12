@extends('admin.layouts.master')

@section('title', 'الصلاحيات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الصلاحيات</h4>
        <p>إدارة صلاحيات المستخدمين والمجموعات</p>
    </div>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة صلاحية
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن صلاحية..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>المستخدم / المجموعة</th>
                    <th>الموديل</th>
                    <th>النطاق</th>
                    <th>الصلاحيات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $perm)
                    <tr>
                        <td>{{ $perm->id }}</td>
                        <td class="fw-medium">
                            @if ($perm->user)
                                <i class="bi bi-person me-1"></i>{{ $perm->user->name }}
                            @elseif ($perm->group)
                                <i class="bi bi-people me-1"></i>{{ $perm->group->name }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @foreach ($perm->model_names ?? [] as $model)
                                <code>{{ class_basename($model) }}</code>@if (!$loop->last), @endif
                            @endforeach
                        </td>
                        <td>
                            @if ($perm->center)
                                <span class="badge bg-primary badge-scope">{{ $perm->center->name }}</span>
                            @else
                                <span class="badge bg-secondary badge-scope">جميع المراكز</span>
                            @endif
                            @if ($perm->project)
                                <span class="badge bg-success badge-scope">{{ $perm->project->name }}</span>
                            @else
                                <span class="badge bg-secondary badge-scope">جميع المشاريع</span>
                            @endif
                        </td>
                        <td>
                            @if ($perm->can_view) <span class="badge bg-info">عرض</span> @endif
                            @if ($perm->can_create) <span class="badge bg-success">إضافة</span> @endif
                            @if ($perm->can_edit) <span class="badge bg-warning text-dark">تعديل</span> @endif
                            @if ($perm->can_delete) <span class="badge bg-danger">حذف</span> @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.permissions.edit', $perm) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه الصلاحية؟')">
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
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد صلاحيات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $permissions->links() }}
    </div>
</div>
@endsection
