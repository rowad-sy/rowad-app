@extends('admin.layouts.master')

@section('title', 'قوالب التقارير الشهرية')

@section('content')
<div class="page-header">
    <h4>قوالب التقارير الشهرية</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / التقارير الشهرية / القوالب
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2" style="max-width: 400px;">
        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="بحث بالاسم أو المفتاح...">
        <button class="btn btn-outline-primary">بحث</button>
    </form>
    @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'create')
    <a href="{{ route('admin.monthly-reports.templates.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> قالب جديد
    </a>
    @endcanPermission
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
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
                    <td><span class="badge bg-secondary">V{{ $template->version }}</span></td>
                    <td>{{ $template->sections()->count() }}</td>
                    <td>{{ $template->reports_count }}</td>
                    <td>
                        @if ($template->is_active)
                            <span class="badge bg-success">مفعّل</span>
                        @else
                            <span class="badge bg-secondary">موقوف</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'edit')
                        <a href="{{ route('admin.monthly-reports.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\MonthlyReports\MonthlyReportTemplate', 'delete')
                        <form action="{{ route('admin.monthly-reports.templates.destroy', $template) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('حذف القالب؟ (يمنع إن وُجدت تقارير منه)')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد قوالب بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $templates->links() }}</div>
@endsection