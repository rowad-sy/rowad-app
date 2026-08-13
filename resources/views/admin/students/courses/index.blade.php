@extends('admin.layouts.master')

@section('title', 'المقررات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المقررات</h4>
        <p>إدارة المقررات الدراسية</p>
    </div>
    @canPermission('App\Models\Admin\Student\Course', 'create')
    <a href="{{ route('admin.students.courses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مقرر
    </a>
    @endcanPermission
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
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
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الاسم AR</th>
                    <th>الاسم EN</th>
                    <th>المدة</th>
                    <th>المشروع</th>
                    <th>الفترات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td>{{ $course->id }}</td>
                        <td class="fw-medium">{{ $course->name_ar }}</td>
                        <td>{{ $course->name_en ?? '—' }}</td>
                        <td>{{ $course->duration ? $course->duration . ' يوم' : '—' }}</td>
                        <td>{{ $course->project?->name ?? '—' }}</td>
                        <td>{{ $course->periods->pluck('name_ar')->implode('، ') ?: '—' }}</td>
                        <td>
                            @canPermission('App\Models\Admin\Student\Course', 'edit')
                            <a href="{{ route('admin.students.courses.edit', $course) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\Student\Course', 'delete')
                            <form method="POST" action="{{ route('admin.students.courses.destroy', $course) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المقرر؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا يوجد مقررات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $courses->total() }} مقرر
        </div>
        <div>
            {{ $courses->links() }}
        </div>
    </div>
</div>
@endsection
