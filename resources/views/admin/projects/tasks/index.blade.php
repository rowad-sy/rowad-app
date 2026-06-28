@extends('admin.layouts.master')

@section('title', 'إدارة المهام')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>إدارة المهام</h4>
        <p>عرض وإدارة مهام المشاريع</p>
    </div>
    @canPermission('App\Models\Admin\ProjectTask', 'create')
    <div>
        <a href="{{ route('admin.projects.tasks.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مهمة
        </a>
    </div>
    @endcanPermission
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach (['pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'منفذة', 'delayed' => 'متأخرة', 'cancelled' => 'ملغاة'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الشهر</label>
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int)request('month') === $m ? 'selected' : '' }}>{{ now()->month($m)->locale('ar')->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">السنة</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach (range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ (int)request('year') === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)request('center_id') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
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
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>المهمة</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>المسند إلى</th>
                    <th>المركز</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>{{ $task->id }}</td>
                        <td class="fw-medium position-relative">
                            <span class="task-hint-trigger" data-task-id="{{ $task->id }}">{{ Str::limit($task->title, 40) }}</span>
                            <div class="task-hint-popup" id="hint-{{ $task->id }}">
                                <div class="fw-bold mb-1">{{ $task->title }}</div>
                                <div><small class="text-muted">الغاية:</small> {{ Str::limit($task->purpose, 80) }}</div>
                                <div><small class="text-muted">المسند إلى:</small> {{ $task->assignedTo?->name }}</div>
                                <div><small class="text-muted">المدة:</small> {{ $task->start_date->format('Y-m-d') }} → {{ $task->end_date->format('Y-m-d') }}</div>
                                @if ($task->executed !== null)
                                    <div><small class="text-muted">منفذة:</small> {{ $task->executed ? 'نعم' : 'لا' }}</div>
                                @endif
                            </div>
                        </td>
                        <td>{{ $task->start_date->format('Y-m-d') }}</td>
                        <td>{{ $task->end_date->format('Y-m-d') }}</td>
                        <td>{{ $task->assignedTo?->name ?? '—' }}</td>
                        <td>{{ $task->center?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusMap = ['pending' => ['bg-warning text-dark', 'قيد الانتظار'], 'in_progress' => ['bg-info', 'قيد التنفيذ'], 'completed' => ['bg-success', 'منفذة'], 'delayed' => ['bg-danger', 'متأخرة'], 'cancelled' => ['bg-secondary', 'ملغاة']];
                                $s = $statusMap[$task->status] ?? ['bg-secondary', $task->status];
                            @endphp
                            <span class="badge {{ $s[0] }}">{{ $s[1] }}</span>
                        </td>
                        <td>{{ $task->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.projects.tasks.show', $task) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                            @canPermission('App\Models\Admin\ProjectTask', 'edit')
                            <a href="{{ route('admin.projects.tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\ProjectTask', 'delete')
                            <form method="POST" action="{{ route('admin.projects.tasks.destroy', $task) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>لا توجد مهام</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $tasks->total() }} مهمة</div>
        <div>{{ $tasks->links() }}</div>
    </div>
</div>
@endsection

@push('styles')
<style>
.task-hint-popup {
    display: none; position: absolute; z-index: 1000; background: #fff; border: 1px solid #dee2e6;
    border-radius: 8px; padding: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    min-width: 260px; top: 100%; left: 0; margin-top: 4px;
}
.task-hint-trigger {
    cursor: pointer; border-bottom: 1px dashed #aaa;
}
.task-hint-trigger:hover + .task-hint-popup,
.task-hint-popup:hover { display: block; }
</style>
@endpush
