@extends('admin.layouts.master')

@section('title', 'المعدات التقنية')

@section('content')
<x-page-header :title="'المعدات التقنية'" :description="'إدارة أجهزة ومعدات التقنية'"
               :breadcrumb="[['label' => 'التقنية'], ['label' => 'المعدات التقنية']]">
    @canPermission('App\Models\Admin\Tech\TechEquipment', 'create')
    <a href="{{ route('admin.tech.equipment.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة معدة
    </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الرقم التسلسلي..." value="{{ $search }}">
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
                <label class="form-label">النوع</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    @php
                        $types = \App\Models\Admin\Tech\TechEquipment::select('type')->distinct()->orderBy('type')->pluck('type');
                    @endphp
                    @foreach ($types as $t)
                        <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select name="condition" class="form-select form-select-sm">
                    <option value="all" {{ ($condition ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="a" {{ ($condition ?? '') === 'a' ? 'selected' : '' }}>ممتاز</option>
                    <option value="b" {{ ($condition ?? '') === 'b' ? 'selected' : '' }}>جيد</option>
                    <option value="c" {{ ($condition ?? '') === 'c' ? 'selected' : '' }}>متوسط</option>
                    <option value="d" {{ ($condition ?? '') === 'd' ? 'selected' : '' }}>سيئ</option>
                    <option value="e" {{ ($condition ?? '') === 'e' ? 'selected' : '' }}>تالف</option>
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
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>الرقم التسلسلي</th>
                    <th>الحالة</th>
                    <th>الغرفة</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipment as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td class="fw-medium">{{ $item->name }}</td>
                        <td>{{ $item->type }}</td>
                        <td><code>{{ $item->serial_number ?? '—' }}</code></td>
                        <td>
                            @switch($item->condition)
                                @case('a') <x-status-badge tone="success">ممتاز</x-status-badge> @break
                                @case('b') <x-status-badge tone="brand">جيد</x-status-badge> @break
                                @case('c') <x-status-badge tone="warning">متوسط</x-status-badge> @break
                                @case('d') <x-status-badge tone="danger">سيئ</x-status-badge> @break
                                @case('e') <x-status-badge>تالف</x-status-badge> @break
                            @endswitch
                        </td>
                        <td>{{ $item->room ?? '—' }}</td>
                        <td>{{ $item->center?->name ?? '—' }}</td>
                        <td>{{ $item->project?->name ?? '—' }}</td>
                        <td>
                            @if (\App\Support\RecordAccess::allows(auth()->user(), 'App\Models\Admin\Tech\TechEquipment', 'edit', $item->center_id, $item->project_id, $item->id))
                            <a href="{{ route('admin.tech.equipment.edit', $item) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endif
                            <x-audit-history :model="'App\Models\Admin\Tech\TechEquipment'" :model-id="$item->id" />
                            @if (\App\Support\RecordAccess::allows(auth()->user(), 'App\Models\Admin\Tech\TechEquipment', 'delete', $item->center_id, $item->project_id, $item->id))
                            <form method="POST" action="{{ route('admin.tech.equipment.destroy', $item) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه المعدة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا توجد معدات" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $equipment->total() }} معدة
        </div>
        <div>
            {{ $equipment->links() }}
        </div>
    </div>
</div>
@endsection
