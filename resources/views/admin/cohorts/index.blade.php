@extends('admin.layouts.master')

@section('title', 'الأفواج')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الأفواج</h4>
        <p>إدارة أفواج الطلاب داخل كل مشروع (مثال: فوج صباحي / فوج مسائي) ومسؤول كل فوج</p>
    </div>
    <a href="{{ route('admin.cohorts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة فوج
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الكود..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">كل المشاريع</option>
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
                    <th>اسم الفوج</th>
                    <th>المشروع</th>
                    <th>الفترة</th>
                    <th>الكود</th>
                    <th>المسؤول</th>
                    <th>الطلاب</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cohorts as $cohort)
                    <tr>
                        <td>{{ $cohort->id }}</td>
                        <td class="fw-medium">{{ $cohort->name }}</td>
                        <td>{{ $cohort->project?->name ?? '—' }}</td>
                        <td>{{ $cohort->shift ?? '—' }}</td>
                        <td class="text-muted" dir="ltr">{{ $cohort->code ?? '—' }}</td>
                        <td>
                            @if ($cohort->manager)
                                <span class="badge bg-secondary-subtle text-dark">{{ $cohort->manager->first_name_ar }} {{ $cohort->manager->last_name_ar }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge bg-info text-white">{{ $cohort->students_count }}</span></td>
                        <td>
                            <span class="badge {{ $cohort->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $cohort->is_active ? 'نشط' : 'موقوف' }}</span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.cohorts.edit', $cohort) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.cohorts.destroy', $cohort) }}" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الفوج؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد أفواج
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $cohorts->links() }}
    </div>
</div>
@endsection
