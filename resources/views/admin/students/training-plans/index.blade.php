@extends('admin.layouts.master')

@section('title', 'الخطط التدريبية')

@section('content')
<x-page-header :title="'الخطط التدريبية'" :description="'الخطط الدراسية الأسبوعية لكل مشروع (دورة من عدة أشهر) بأسماء الدروس لكل أسبوع'"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الخطط التدريبية']]">
    @canPermission('App\Models\Admin\Student\Course', 'create')
    <a href="{{ route('admin.students.training-plans.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إنشاء خطة تدريبية
    </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الخطة</th>
                    <th>المشروع</th>
                    <th>بداية</th>
                    <th>نهاية</th>
                    <th>عدد الدروس</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>{{ $plan->id }}</td>
                        <td class="fw-medium">{{ $plan->name_ar }}</td>
                        <td>{{ $plan->project?->name ?? '—' }}</td>
                        <td>{{ $plan->start_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $plan->end_date?->format('Y-m-d') ?? '—' }}</td>
                        <td><span class="badge bg-primary">{{ $plan->lessons_count }}</span></td>
                        <td>
                            <span class="badge {{ $plan->status === 'active' ? 'bg-success' : ($plan->status === 'completed' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                {{ $plan->status }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.students.training-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-calendar-week"></i> الجدول
                                </a>
                                @canPermission('App\Models\Admin\Student\Course', 'edit')
                                <a href="{{ route('admin.students.training-plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> تعديل
                                </a>
                                @endcanPermission
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="8" icon="bi-inbox" title="لا توجد خطط تدريبية" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
