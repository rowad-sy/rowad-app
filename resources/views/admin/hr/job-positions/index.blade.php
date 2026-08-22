@extends('admin.layouts.master')

@section('title', 'المناصب الوظيفية')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المناصب الوظيفية</h4>
        <p>إدارة المسميات والمناصب الوظيفية في المؤسسة</p>
    </div>
    <a href="{{ route('admin.hr.job-positions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة منصب
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="{{ $search }}">
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
                <th>المسمى AR</th>
                <th>المسمى EN</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($positions as $position)
                <tr>
                    <td>{{ $position->id }}</td>
                    <td class="fw-medium">{{ $position->title_ar }}</td>
                    <td>{{ $position->title_en ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.hr.job-positions.edit', $position) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <x-audit-history :model="'App\Models\Admin\Hr\JobPosition'" :model-id="$position->id" />
                        <form method="POST" action="{{ route('admin.hr.job-positions.destroy', $position) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المنصب؟')">
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
                    <td colspan="4" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        لا توجد مناصب وظيفية
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $positions->links() }}
    </div>
</div>
@endsection
