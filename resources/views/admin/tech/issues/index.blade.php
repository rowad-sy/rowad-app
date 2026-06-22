@extends('admin.layouts.master')

@section('title', 'التذاكر الفنية')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>التذاكر الفنية</h4>
        <p>إدارة طلبات الدعم الفني</p>
    </div>
    <a href="{{ route('admin.tech.issues.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة تذكرة
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالعنوان..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="open" {{ ($status ?? '') === 'open' ? 'selected' : '' }}>مفتوحة</option>
                    <option value="in_progress" {{ ($status ?? '') === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                    <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>مكتملة</option>
                    <option value="blocked" {{ ($status ?? '') === 'blocked' ? 'selected' : '' }}>مغلقة</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الأولوية</label>
                <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($priority ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="low" {{ ($priority ?? '') === 'low' ? 'selected' : '' }}>منخفضة</option>
                    <option value="medium" {{ ($priority ?? '') === 'medium' ? 'selected' : '' }}>متوسطة</option>
                    <option value="high" {{ ($priority ?? '') === 'high' ? 'selected' : '' }}>مرتفعة</option>
                    <option value="urgent" {{ ($priority ?? '') === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                </select>
            </div>
            <div class="col-md-1">
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
                    <th>العنوان</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>الأولوية</th>
                    <th>المبلغ</th>
                    <th>المسند</th>
                    <th>تاريخ التقرير</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    <tr>
                        <td>{{ $issue->id }}</td>
                        <td class="fw-medium">{{ $issue->title }}</td>
                        <td>{{ $issue->center?->name ?? '—' }}</td>
                        <td>{{ $issue->project?->name ?? '—' }}</td>
                        <td>
                            @switch($issue->status)
                                @case('open') <span class="badge bg-primary">مفتوحة</span> @break
                                @case('in_progress') <span class="badge bg-warning text-dark">قيد التنفيذ</span> @break
                                @case('completed') <span class="badge bg-success">مكتملة</span> @break
                                @case('blocked') <span class="badge bg-danger">مغلقة</span> @break
                            @endswitch
                        </td>
                        <td>
                            @switch($issue->priority)
                                @case('low') <span class="badge bg-secondary">منخفضة</span> @break
                                @case('medium') <span class="badge bg-info">متوسطة</span> @break
                                @case('high') <span class="badge bg-warning text-dark">مرتفعة</span> @break
                                @case('urgent') <span class="badge bg-danger">عاجلة</span> @break
                            @endswitch
                        </td>
                        <td>{{ $issue->reporter?->name ?? '—' }}</td>
                        <td>{{ $issue->assignee?->name ?? '—' }}</td>
                        <td class="small">{{ $issue->created_at->locale('ar')->translatedFormat('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.tech.issues.show', $issue) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.tech.issues.edit', $issue) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.tech.issues.destroy', $issue) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه التذكرة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد تذاكر
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $issues->total() }} تذكرة
        </div>
        <div>
            {{ $issues->links() }}
        </div>
    </div>
</div>
@endsection
