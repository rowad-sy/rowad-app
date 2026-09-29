@extends('admin.layouts.master')

@section('title', 'المراكز')

@section('content')
<x-page-header title="المراكز" description="إدارة المراكز التابعة للمؤسسة"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'المراكز']]">
    <a href="{{ route('admin.centers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة مركز
    </a>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
        <div class="col-12 col-md-4 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="search" id="f-search" name="search" class="form-control" placeholder="اسم المركز..." value="{{ $search }}">
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                        <td class="num">{{ $center->id }}</td>
                        <td class="fw-medium">{{ $center->name }}</td>
                        <td>{{ $center->address ?? '—' }}</td>
                        <td class="ltr-cell">{{ $center->phone ?? '—' }}</td>
                        <td class="text-nowrap">
                            <div class="row-actions">
                                <a href="{{ route('admin.centers.edit', $center) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل {{ $center->name }}" title="تعديل">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <x-audit-history :model="'App\Models\Admin\Center'" :model-id="$center->id" />
                                <form method="POST" action="{{ route('admin.centers.destroy', $center) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا المركز؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $center->name }}" title="حذف">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" title="لا توجد مراكز بعد" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $centers->links() }}
    </div>
</div>
@endsection
