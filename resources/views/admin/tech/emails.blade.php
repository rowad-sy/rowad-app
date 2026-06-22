@extends('admin.layouts.master')

@section('title', 'البريد الرسمي')

@section('content')
<div class="page-header">
    <h4>البريد الرسمي</h4>
    <p>عناوين البريد الإلكتروني الرسمي للمستخدمين</p>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو البريد..." value="{{ $search }}">
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
                    <th>الاسم</th>
                    <th>نوع الحساب</th>
                    <th>البريد الإلكتروني الرسمي</th>
                    <th>البريد المسجل</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td class="fw-medium">{{ $user->name }}</td>
                        <td>
                            @switch($user->type)
                                @case('employee') <span class="badge bg-primary">موظف</span> @break
                                @case('student') <span class="badge bg-info">طالب</span> @break
                                @case('beneficiary') <span class="badge bg-success">مستفيد</span> @break
                                @default <span class="badge bg-secondary">—</span>
                            @endswitch
                        </td>
                        <td>
                            @if ($user->official_email)
                                <a href="mailto:{{ $user->official_email }}" dir="ltr">{{ $user->official_email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td dir="ltr">
                            <small>{{ $user->email }}</small>
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">غير نشط</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا يوجد مستخدمون
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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
