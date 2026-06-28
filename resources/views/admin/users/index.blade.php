@extends('admin.layouts.master')

@section('title', 'المستخدمين')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المستخدمين</h4>
        <p>إدارة مستخدمي النظام</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مستخدم
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مستخدم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">النوع</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="super-admin" {{ ($type ?? '') === 'super-admin' ? 'selected' : '' }}>سوبر أدمن</option>
                    <option value="employee" {{ ($type ?? '') === 'employee' ? 'selected' : '' }}>موظف</option>
                    <option value="beneficiary" {{ ($type ?? '') === 'beneficiary' ? 'selected' : '' }}>مستفيد</option>
                    <option value="student" {{ ($type ?? '') === 'student' ? 'selected' : '' }}>طالب</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>النوع</th>
                <th>الحالة</th>
                <th>المجموعات</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td class="fw-medium">{{ $user->name }}</td>
                    <td dir="ltr">{{ $user->email }}</td>
                    <td>
                        @if ($user->type === 'super-admin')
                            <span class="badge bg-danger text-white">سوبر أدمن</span>
                        @elseif ($user->type === 'employee')
                            <span class="badge bg-info text-white">موظف</span>
                        @elseif ($user->type === 'beneficiary')
                            <span class="badge bg-secondary text-white">مستفيد</span>
                        @elseif ($user->type === 'student')
                            <span class="badge bg-primary text-white">طالب</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge bg-success">نشط</span>
                        @else
                            <span class="badge bg-danger">غير نشط</span>
                        @endif
                    </td>
                    <td>
                        @foreach ($user->groups as $group)
                            <span class="badge bg-info text-white me-1">{{ $group->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}">
                                <i class="bi bi-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
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
                        لا توجد مستخدمين
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $users->total() }} مستخدم
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
