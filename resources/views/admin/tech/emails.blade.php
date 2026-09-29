@extends('admin.layouts.master')

@section('title', 'البريد الرسمي')

@section('content')
<x-page-header title="البريد الرسمي" description="عناوين البريد الإلكتروني الرسمي للمستخدمين" :breadcrumb="[['label' => 'التقنية'], ['label' => 'البريد الرسمي']]" />

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو البريد..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                                @case('employee') <x-status-badge tone="brand">موظف</x-status-badge> @break
                                @case('student') <x-status-badge tone="info">طالب</x-status-badge> @break
                                @case('beneficiary') <x-status-badge tone="success">مستفيد</x-status-badge> @break
                                @default <x-status-badge>—</x-status-badge>
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
                                <x-status-badge tone="success">نشط</x-status-badge>
                            @else
                                <x-status-badge>غير نشط</x-status-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" icon="bi-inbox" title="لا يوجد مستخدمون" />
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
