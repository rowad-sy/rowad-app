@extends('admin.layouts.master')

@section('title', 'التقويم الزمني للمهام')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>التقويم الزمني للمهام</h4>
        <p>عرض المهام مرتبة زمنياً</p>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="month" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach (range(1, 12) as $m)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ now()->month($m)->locale('ar')->translatedFormat('F') }}</option>
            @endforeach
        </select>
        <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach (range(now()->year - 2, now()->year + 2) as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Kanban Board --}}
<div class="kanban-board">
    @php
        $statusLabels = ['pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'منفذة', 'delayed' => 'متأخرة', 'cancelled' => 'ملغاة'];
        $statusColors = ['pending' => '#ffc107', 'in_progress' => '#0dcaf0', 'completed' => '#198754', 'delayed' => '#dc3545', 'cancelled' => '#6c757d'];
        $grouped = $tasks->groupBy('status');
    @endphp
    @foreach (['pending', 'in_progress', 'delayed', 'completed', 'cancelled'] as $status)
        @if ($grouped->has($status) || $status === 'pending')
        <div class="kanban-column">
            <div class="kanban-header" style="border-top-color: {{ $statusColors[$status] }}">
                <span>{{ $statusLabels[$status] }}</span>
                <span class="badge bg-secondary">{{ $grouped->get($status)?->count() ?? 0 }}</span>
            </div>
            <div class="kanban-body">
                @forelse (($grouped->get($status) ?? collect()) as $task)
                    <div class="kanban-card" onmouseover="showHint(this)" onmouseout="hideHint(this)">
                        <div class="fw-medium small">{{ Str::limit($task->title, 35) }}</div>
                        <div class="kanban-card-date"><small>{{ $task->start_date->format('d/m') }} - {{ $task->end_date->format('d/m') }}</small></div>
                        <div class="kanban-card-assignee"><small><i class="bi bi-person"></i> {{ $task->assignedTo?->name ?? '—' }}</small></div>
                        <div class="kanban-hint">
                            <div class="fw-bold mb-1">{{ $task->title }}</div>
                            <div><small class="text-muted">الغاية:</small> {{ Str::limit($task->purpose, 60) ?: '—' }}</div>
                            <div><small class="text-muted">المسند إلى:</small> {{ $task->assignedTo?->name }}</div>
                            <div><small class="text-muted">المدة:</small> {{ $task->start_date->format('Y-m-d') }} → {{ $task->end_date->format('Y-m-d') }}</div>
                            @if ($task->executed !== null)
                                <div><small class="text-muted">منفذة:</small> {{ $task->executed ? 'نعم' : 'لا' }}</div>
                            @endif
                            <a href="{{ route('admin.projects.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary mt-1 w-100">عرض التفاصيل</a>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">—</div>
                @endforelse
            </div>
        </div>
        @endif
    @endforeach
</div>
@endsection

@push('styles')
<style>
.kanban-board { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
.kanban-column { flex: 1; min-width: 220px; background: #f8f9fa; border-radius: 8px; display: flex; flex-direction: column; }
.kanban-header { padding: 0.75rem; font-weight: 700; font-size: 0.9rem; border-top: 3px solid; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0; background: #fff; }
.kanban-body { padding: 0.5rem; flex: 1; }
.kanban-card { position: relative; background: #fff; border-radius: 6px; padding: 0.5rem; margin-bottom: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: pointer; border-right: 3px solid #dee2e6; }
.kanban-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.kanban-card-date { color: #6c757d; }
.kanban-card-assignee { color: #6c757d; }
.kanban-hint { display: none; position: absolute; z-index: 1000; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem; box-shadow: 0 4px 16px rgba(0,0,0,0.15); min-width: 240px; top: 0; right: calc(100% + 8px); }
.kanban-card:hover .kanban-hint { display: block; }
</style>
@endpush
