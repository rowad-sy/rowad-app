@extends('admin.layouts.master')

@section('title', 'سجل التدقيق')

@section('content')
<x-page-header title="سجل التدقيق" description="عرض جميع التغييرات في النظام" :breadcrumb="[['label' => 'الإدارة'], ['label' => 'سجل التدقيق']]" />

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-model_name">الموديل</label>
            <select id="f-model_name" name="model_name" class="form-select">
                <option value="">كل الموديلات</option>
                @foreach($modelNames as $name)
                    <option value="{{ $name }}" {{ request('model_name') === $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-event">الحدث</label>
            <select id="f-event" name="event" class="form-select">
                <option value="">كل الأحداث</option>
                @foreach($events as $event)
                    <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>
                        {{ match($event) {
                            'created' => 'إنشاء', 'updated' => 'تعديل', 'deleted' => 'حذف', 'restored' => 'استعادة',
                            'login' => 'دخول', 'logout' => 'خروج', default => $event,
                        } }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-user_id">المستخدم</label>
            <select id="f-user_id" name="user_id" class="form-select">
                <option value="">كل المستخدمين</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-date_from">من تاريخ</label>
            <input type="date" id="f-date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-date_to">إلى تاريخ</label>
            <input type="date" id="f-date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-12 col-md-3 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="text" id="f-search" name="search" class="form-control" placeholder="بحث..." value="{{ request('search') }}">
        </div>
    </x-filter-bar>
</div>

<div class="table-container">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
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
                        @php $tone = ['created' => 'success', 'updated' => 'brand', 'deleted' => 'danger', 'restored' => 'warning', 'login' => 'info', 'failed' => 'danger'][$log->event] ?? 'neutral'; @endphp
                        <x-status-badge :tone="$tone">{{ match($log->event) {
                                'created' => 'إنشاء',
                                'updated' => 'تعديل',
                                'deleted' => 'حذف',
                                'restored' => 'استعادة',
                                'login' => 'دخول',
                                'logout' => 'خروج',
                                'failed' => 'فاشل',
                                default => $log->event,
                            } }}</x-status-badge>
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
                            <button class="btn btn-sm btn-outline-secondary" aria-label="عرض تفاصيل التغييرات" title="عرض تفاصيل التغييرات"
                                    onclick="loadAuditHistory('{{ addslashes($log->model) }}', {{ $log->model_id }})">
                                <i class="bi bi-eye"></i> {{ $log->changes->count() }}
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="8" title="لا توجد سجلات" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
