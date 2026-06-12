@extends('admin.layouts.master')

@section('title', 'الموظفين')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الموظفين</h4>
        <p>إدارة بيانات الموظفين في المؤسسة</p>
    </div>
    <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة موظف
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الكود..." value="{{ $search }}">
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
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>المركز</th>
                    <th>الإدارة</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $emp)
                    <tr>
                        <td>{{ $emp->id }}</td>
                        <td><code>{{ $emp->employee_code }}</code></td>
                        <td class="fw-medium">{{ $emp->first_name_ar }} {{ $emp->last_name_ar }}</td>
                        <td>{{ $emp->center?->name ?? '—' }}</td>
                        <td>{{ $emp->department?->name_ar ?? '—' }}</td>
                        <td>{{ $emp->project?->name ?? '—' }}</td>
                        <td>
                            @if ($emp->status === 'active')
                                <span class="badge bg-success">فعال</span>
                            @else
                                <span class="badge bg-secondary">غير فعال</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.hr.employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.hr.employees.destroy', $emp) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">
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
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا يوجد موظفون
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $employees->links() }}
    </div>
</div>
@endsection
