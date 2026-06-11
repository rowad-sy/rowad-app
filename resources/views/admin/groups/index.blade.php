@extends('admin.layouts.master')

@section('title', 'مجموعات المستخدمين')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>مجموعات المستخدمين</h4>
        <p>إدارة مجموعات المستخدمين والصلاحيات</p>
    </div>
    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مجموعة
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مجموعة..." value="{{ $search }}">
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
                <th>اسم المجموعة</th>
                <th>الوصف</th>
                <th>عدد الأعضاء</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td class="fw-medium">{{ $group->name }}</td>
                    <td class="text-muted">{{ $group->description ?? '—' }}</td>
                    <td>
                        <span class="badge bg-info text-white">{{ $group->users_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.groups.destroy', $group) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة؟')">
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
                        لا توجد مجموعات
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $groups->links() }}
    </div>
</div>
@endsection
