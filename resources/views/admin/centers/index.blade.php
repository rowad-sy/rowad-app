@extends('admin.layouts.master')

@section('title', 'المراكز')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المراكز</h4>
        <p>إدارة المراكز التابعة للمؤسسة</p>
    </div>
    <a href="{{ route('admin.centers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مركز
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مركز..." value="{{ $search }}">
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
                <th>اسم المركز</th>
                <th>العنوان</th>
                <th>الهاتف</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($centers as $center)
                <tr>
                    <td>{{ $center->id }}</td>
                    <td class="fw-medium">{{ $center->name }}</td>
                    <td>{{ $center->address ?? '—' }}</td>
                    <td dir="ltr">{{ $center->phone ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.centers.edit', $center) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.centers.destroy', $center) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المركز؟')">
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
                        لا توجد مراكز
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $centers->links() }}
    </div>
</div>
@endsection
