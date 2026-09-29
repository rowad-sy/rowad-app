@extends('admin.layouts.master')

@section('title', 'الفترات')

@section('content')
<x-page-header :title="'الفترات'" :description="'إدارة الفترات الزمنية للفروع'"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الفترات']]">
    @canPermission('App\Models\Admin\Student\Period', 'create')
    <a href="{{ route('admin.students.periods.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة فترة
    </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">السنة</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" {{ (int)($year ?? '') === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>السنة</th>
                    <th>تاريخ البداية</th>
                    <th>تاريخ النهاية</th>
                    <th>المشروع</th>
                    <th>المقررات</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td>{{ $period->id }}</td>
                        <td class="fw-medium">{{ $period->name_ar }}</td>
                        <td>{{ $period->year }}</td>
                        <td>{{ $period->start_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $period->end_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $period->project?->name ?? '—' }}</td>
                        <td>{{ $period->courses->pluck('name_ar')->implode('، ') ?: '—' }}</td>
                        <td>
                            @if ($period->is_active)
                                <span class="badge bg-success">نشطة</span>
                            @else
                                <span class="badge bg-secondary">غير نشطة</span>
                            @endif
                        </td>
                        <td>
                            @canPermission('App\Models\Admin\Student\Period', 'edit')
                            <a href="{{ route('admin.students.periods.edit', $period) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Student\Period'" :model-id="$period->id" />
                            @canPermission('App\Models\Admin\Student\Period', 'delete')
                            <form method="POST" action="{{ route('admin.students.periods.destroy', $period) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه الفترة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا يوجد فترات" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $periods->total() }} فترة
        </div>
        <div>
            {{ $periods->links() }}
        </div>
    </div>
</div>
@endsection
