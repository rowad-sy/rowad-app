@extends('admin.layouts.master')

@section('title', 'المناصب الوظيفية')

@section('content')
<x-page-header :title="'المناصب الوظيفية'" :description="'إدارة المسميات والمناصب الوظيفية في المؤسسة'"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'المناصب الوظيفية']]">
    <a href="{{ route('admin.hr.job-positions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة منصب
    </a>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
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
                        <a href="{{ route('admin.hr.job-positions.edit', $position) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        <x-audit-history :model="'App\Models\Admin\Hr\JobPosition'" :model-id="$position->id" />
                        <form method="POST" action="{{ route('admin.hr.job-positions.destroy', $position) }}" class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المنصب؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="4" icon="bi-inbox" title="لا توجد مناصب وظيفية" />
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3">
        {{ $positions->links() }}
    </div>
</div>
@endsection
