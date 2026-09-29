@extends('admin.layouts.master')

@section('title', 'إدارة المهام')

@section('content')
<x-page-header :title="'إدارة المهام'" :description="'عرض وإدارة مهام المشاريع'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'إدارة المهام']]">
    @canPermission('App\Models\Admin\ProjectTask', 'create')
    <div>
        <a href="{{ route('admin.projects.tasks.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مهمة
        </a>
    </div>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (['pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'منفذة', 'delayed' => 'متأخرة', 'cancelled' => 'ملغاة'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الشهر</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int)request('month') === $m ? 'selected' : '' }}>{{ now()->month($m)->locale('ar')->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">السنة</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ (int)request('year') === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)request('center_id') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                            <a href="{{ route('admin.projects.tasks.show', $task) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @canPermission('App\Models\Admin\ProjectTask', 'edit')
                            <a href="{{ route('admin.projects.tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\ProjectTask'" :model-id="$task->id" />
                            @canPermission('App\Models\Admin\ProjectTask', 'delete')
                            <form method="POST" action="{{ route('admin.projects.tasks.destroy', $task) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا توجد مهام" />
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
    display: none; position: absolute; z-index: 1000; background: var(--color-surface); color: var(--color-text-main); border: 1px solid var(--color-border);
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
