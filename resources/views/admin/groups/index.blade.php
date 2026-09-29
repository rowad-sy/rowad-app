@extends('admin.layouts.master')

@section('title', 'مجموعات المستخدمين')

@section('content')
<x-page-header title="مجموعات المستخدمين" description="إدارة مجموعات المستخدمين والصلاحيات"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'المجموعات']]">
    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة مجموعة
    </a>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
        <div class="col-12 col-md-4 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="search" id="f-search" name="search" class="form-control" placeholder="اسم المجموعة..." value="{{ $search }}">
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                        <td class="num">{{ $group->id }}</td>
                        <td class="fw-medium">{{ $group->name }}</td>
                        <td class="text-muted">{{ $group->description ?? '—' }}</td>
                        <td class="num"><x-status-badge tone="info">{{ $group->users_count }}</x-status-badge></td>
                        <td class="text-nowrap">
                            <div class="row-actions">
                                <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل {{ $group->name }}" title="تعديل">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <x-audit-history :model="'App\Models\Admin\Group'" :model-id="$group->id" />
                                <form method="POST" action="{{ route('admin.groups.destroy', $group) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $group->name }}" title="حذف">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" title="لا توجد مجموعات بعد" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $groups->links() }}
    </div>
</div>
@endsection
