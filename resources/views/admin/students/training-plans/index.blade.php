@extends('admin.layouts.master')

@section('title', 'الخطط التدريبية')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الخطط التدريبية</h4>
        <p>الخطط الدراسية الأسبوعية لكل مشروع (دورة من عدة أشهر) بأسماء الدروس لكل أسبوع</p>
    </div>
    @canPermission('App\Models\Admin\Student\Course', 'create')
    <a href="{{ route('admin.students.training-plans.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إنشاء خطة تدريبية
    </a>
    @endcanPermission
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
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
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد خطط تدريبية
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
