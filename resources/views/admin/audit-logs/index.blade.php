@extends('admin.layouts.master')

@section('title', 'سجل التدقيق')

@section('content')
<div class="page-header">
    <h4>سجل التدقيق</h4>
    <p>عرض جميع التغييرات في النظام</p>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select name="model_name" class="form-select form-select-sm">
                    <option value="">كل الموديلات</option>
                    @foreach($modelNames as $name)
                        <option value="{{ $name }}" {{ request('model_name') === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="event" class="form-select form-select-sm">
                    <option value="">كل الأحداث</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>
                            {{ match($event) {
                                'created' => 'إنشاء',
                                'updated' => 'تعديل',
                                'deleted' => 'حذف',
                                'restored' => 'استعادة',
                                'login' => 'دخول',
                                'logout' => 'خروج',
                                default => $event,
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">كل المستخدمين</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" placeholder="من" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" placeholder="إلى" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="{{ request('search') }}">
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
                <th>التاريخ</th>
                <th>الحدث</th>
                <th>الموديل</th>
                <th>رقم السجل</th>
                <th>المستخدم</th>
                <th>الوصف</th>
                <th>التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td><small>{{ $log->created_at->format('Y-m-d H:i') }}</small></td>
                    <td>
                        <span class="badge bg-{{ match($log->event) {
                            'created' => 'success',
                            'updated' => 'primary',
                            'deleted' => 'danger',
                            'restored' => 'warning',
                            'login' => 'info',
                            'logout' => 'secondary',
                            'failed' => 'danger',
                            default => 'secondary',
                        } }}">
                            {{ match($log->event) {
                                'created' => 'إنشاء',
                                'updated' => 'تعديل',
                                'deleted' => 'حذف',
                                'restored' => 'استعادة',
                                'login' => 'دخول',
                                'logout' => 'خروج',
                                'failed' => 'فاشل',
                                default => $log->event,
                            } }}
                        </span>
                    </td>
                    <td>{{ $log->model_name }}</td>
                    <td>{{ $log->model_id }}</td>
                    <td>
                        @if($log->user)
                            {{ $log->user->name }}
                            <br><small class="text-muted">{{ $log->user->email }}</small>
                        @else
                            <span class="text-muted">نظام</span>
                        @endif
                    </td>
                    <td><small class="text-muted">{{ Str::limit($log->description, 40) ?? '—' }}</small></td>
                    <td>
                        @if($log->changes->count())
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="loadAuditHistory('{{ addslashes($log->model) }}', {{ $log->model_id }})">
                                <i class="bi bi-eye"></i> {{ $log->changes->count() }}
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        لا توجد سجلات
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
