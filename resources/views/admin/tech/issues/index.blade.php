@extends('admin.layouts.master')

@section('title', 'التذاكر الفنية')

@section('content')
<x-page-header :title="'التذاكر الفنية'" :description="'إدارة طلبات الدعم الفني'"
               :breadcrumb="[['label' => 'التقنية'], ['label' => 'التذاكر الفنية']]">
    @canPermission('App\Models\Admin\Tech\TechIssue', 'create')
    <a href="{{ route('admin.tech.issues.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة تذكرة
    </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالعنوان..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="open" {{ ($status ?? '') === 'open' ? 'selected' : '' }}>مفتوحة</option>
                    <option value="in_progress" {{ ($status ?? '') === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                    <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>مكتملة</option>
                    <option value="blocked" {{ ($status ?? '') === 'blocked' ? 'selected' : '' }}>مغلقة</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الأولوية</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="all" {{ ($priority ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="low" {{ ($priority ?? '') === 'low' ? 'selected' : '' }}>منخفضة</option>
                    <option value="medium" {{ ($priority ?? '') === 'medium' ? 'selected' : '' }}>متوسطة</option>
                    <option value="high" {{ ($priority ?? '') === 'high' ? 'selected' : '' }}>مرتفعة</option>
                    <option value="urgent" {{ ($priority ?? '') === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                                @case('open') <x-status-badge tone="brand">مفتوحة</x-status-badge> @break
                                @case('in_progress') <x-status-badge tone="warning">قيد التنفيذ</x-status-badge> @break
                                @case('completed') <x-status-badge tone="success">مكتملة</x-status-badge> @break
                                @case('blocked') <x-status-badge tone="danger">مغلقة</x-status-badge> @break
                            @endswitch
                        </td>
                        <td>
                            @switch($issue->priority)
                                @case('low') <x-status-badge>منخفضة</x-status-badge> @break
                                @case('medium') <x-status-badge tone="info">متوسطة</x-status-badge> @break
                                @case('high') <x-status-badge tone="warning">مرتفعة</x-status-badge> @break
                                @case('urgent') <x-status-badge tone="danger">عاجلة</x-status-badge> @break
                            @endswitch
                        </td>
                        <td>{{ $issue->reporter?->name ?? '—' }}</td>
                        <td>{{ $issue->assignee?->name ?? '—' }}</td>
                        <td class="small">{{ $issue->created_at->locale('ar')->translatedFormat('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.tech.issues.show', $issue) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @canPermission('App\Models\Admin\Tech\TechIssue', 'edit')
                            <a href="{{ route('admin.tech.issues.edit', $issue) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Tech\TechIssue'" :model-id="$issue->id" />
                            @canPermission('App\Models\Admin\Tech\TechIssue', 'delete')
                            <form method="POST" action="{{ route('admin.tech.issues.destroy', $issue) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه التذكرة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="10" icon="bi-inbox" title="لا توجد تذاكر" />
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
