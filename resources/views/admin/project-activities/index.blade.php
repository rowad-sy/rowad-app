@extends('admin.layouts.master')

@section('title', 'الأنشطة')

@section('content')
<div class="page-header">
    <h4>الأنشطة</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / الأنشطة
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="project_id" class="form-select" style="width: auto;">
            <option value="">كل المشاريع</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ ($projectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
        <input type="date" name="activity_date" class="form-control" style="width: auto;" value="{{ $activityDate ?? '' }}">
        <button class="btn btn-outline-primary">تصفية</button>
    </form>
    @canPermission('App\Models\Admin\ProjectActivity', 'create')
    <a href="{{ route('admin.project-activities.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> إضافة نشاط
    </a>
    @endcanPermission
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>تاريخ النشاط</th>
                    <th>المسؤول عن النشاط</th>
                    <th>الجهة المستفيدة</th>
                    <th>المستفيدون (ذكور/إناث)</th>
                    <th>سير النشاط</th>
                    <th>المعوقات</th>
                    <th>المشروع</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                <tr>
                    <td>{{ $activity->activity_date?->format('d/m/Y') }}</td>
                    <td>{{ $activity->responsible ?: '—' }}</td>
                    <td>{{ $activity->beneficiary ?: '—' }}</td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary">{{ $activity->male_count }} ذكر</span>
                        <span class="badge bg-info-subtle text-info">{{ $activity->female_count }} أنثى</span>
                        <span class="text-muted small">({{ $activity->total_beneficiaries }} إجمالي)</span>
                    </td>
                    <td class="small">{{ Str::limit($activity->progress, 60) ?: '—' }}</td>
                    <td class="small">{{ Str::limit($activity->obstacles, 40) ?: '—' }}</td>
                    <td>{{ $activity->project?->name ?? ($activity->center?->name ?? '—') }}</td>
                    <td class="text-nowrap">
                        @canPermission('App\Models\Admin\ProjectActivity', 'edit')
                        <a href="{{ route('admin.project-activities.edit', $activity) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\ProjectActivity', 'delete')
                        <form method="POST" action="{{ route('admin.project-activities.destroy', $activity) }}"
                              class="d-inline" onsubmit="return confirm('حذف النشاط نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">لا توجد أنشطة بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>
@endsection