@extends('admin.layouts.master')

@section('title', 'قوالب التقارير الشهرية')

@section('content')
<x-page-header :title="'قوالب التقارير الشهرية'"
               :breadcrumb="[['label' => 'التقارير الشهرية'], ['label' => 'القوالب']]">
    @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'create')
        <a href="{{ route('admin.monthly-reports.templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> قالب جديد
        </a>
        @endcanPermission
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input id="f-search" type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="بحث بالاسم أو المفتاح...">
        </div>
    </x-filter-bar>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>القالب</th>
                    <th>المفتاح</th>
                    <th>الإصدار</th>
                    <th>الأقسام</th>
                    <th>التقارير</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                <tr>
                    <td>{{ $template->title_ar }}</td>
                    <td><code>{{ $template->key }}</code></td>
                    <td><x-status-badge>V{{ $template->version }}</x-status-badge></td>
                    <td>{{ $template->sections()->count() }}</td>
                    <td>{{ $template->reports_count }}</td>
                    <td>
                        @if ($template->is_active)
                            <x-status-badge tone="success">مفعّل</x-status-badge>
                        @else
                            <x-status-badge>موقوف</x-status-badge>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'edit')
                        <a href="{{ route('admin.monthly-reports.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'delete')
                        <form action="{{ route('admin.monthly-reports.templates.destroy', $template) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('حذف القالب؟ (يمنع إن وُجدت تقارير منه)')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <x-empty-row colspan="7" title="لا توجد قوالب بعد" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $templates->links() }}</div>
@endsection