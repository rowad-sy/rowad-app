@extends('admin.layouts.master')

@section('title', 'الأنشطة')

@section('content')
<x-page-header :title="'الأنشطة'"
               :breadcrumb="[['label' => 'الأنشطة']]">
    @canPermission('App\Models\Admin\ProjectActivity', 'create')
        <a href="{{ route('admin.project-activities.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> إضافة نشاط
        </a>
        @endcanPermission
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-project_id">المشروع</label>
            <select id="f-project_id" name="project_id" class="form-select">
            <option value="">كل المشاريع</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ ($projectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-activity_date">التاريخ</label>
            <input id="f-activity_date" type="date" name="activity_date" class="form-control" value="{{ $activityDate ?? '' }}">
        </div>
    </x-filter-bar>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                        <a href="{{ route('admin.project-activities.edit', $activity) }}" class="btn btn-sm btn-outline-primary" title="تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\ProjectActivity', 'delete')
                        <form method="POST" action="{{ route('admin.project-activities.destroy', $activity) }}"
                              class="d-inline" onsubmit="return confirm('حذف النشاط نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <x-empty-row colspan="8" title="لا توجد أنشطة بعد" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>
@endsection