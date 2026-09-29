@extends('admin.layouts.master')

@section('title', 'الإدارات')

@section('content')
<x-page-header title="الإدارات" description="إدارة الأقسام والإدارات التابعة للمؤسسة"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'الإدارات']]">
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة إدارة
    </a>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
        <div class="col-12 col-md-4 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="search" id="f-search" name="search" class="form-control" placeholder="اسم الإدارة..." value="{{ $search }}">
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم (AR)</th>
                    <th>الاسم (EN)</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td class="num">{{ $department->id }}</td>
                        <td class="fw-medium">{{ $department->name_ar }}</td>
                        <td dir="ltr" class="text-end">{{ $department->name_en ?? '—' }}</td>
                        <td>
                            <x-status-badge :tone="$department->is_active ? 'success' : 'neutral'">{{ $department->is_active ? 'فعال' : 'غير فعال' }}</x-status-badge>
                        </td>
                        <td class="text-nowrap">
                            <div class="row-actions">
                                <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل {{ $department->name_ar }}" title="تعديل">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <x-audit-history :model="'App\Models\Admin\Department'" :model-id="$department->id" />
                                <form method="POST" action="{{ route('admin.departments.destroy', $department) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه الإدارة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $department->name_ar }}" title="حذف">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" title="لا توجد إدارات بعد" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $departments->links() }}
    </div>
</div>
@endsection
