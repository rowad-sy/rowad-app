@extends('admin.layouts.master')

@section('title', 'الخطة الإعلامية')

@section('content')
<x-page-header :title="'الخطة الإعلامية'" :description="'خطط الإعلام الشهرية وفعاليات التغطية'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'الخطة الإعلامية']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.media-plans.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\MediaPlan', 'create')
        <a href="{{ route('admin.media-plans.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة خطة
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            @if (request('project_id'))
                <input type="hidden" name="project_id" value="{{ request('project_id') }}">
            @endif
            <div class="col-md-3">
                <label class="form-label">الشهر</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int)request('month') === $m ? 'selected' : '' }}>{{ now()->month($m)->locale('ar')->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">السنة</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ (int)request('year') === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)request('center_id') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الشهر</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>عدد الفعاليات</th>
                    <th>أنشأها</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>{{ $plan->id }}</td>
                        <td>{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td>
                        <td>{{ $plan->center?->name ?? '—' }}</td>
                        <td>{{ $plan->project?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $plan->events_count > 0 ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $plan->events_count }}
                            </span>
                        </td>
                        <td>{{ $plan->creator?->name ?? '—' }}</td>
                        <td>{{ $plan->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.media-plans.show', $plan) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @canPermission('App\Models\Admin\MediaPlan', 'edit')
                            <a href="{{ route('admin.media-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\MediaPlan'" :model-id="$plan->id" />
                            @canPermission('App\Models\Admin\MediaPlan', 'delete')
                            <form method="POST" action="{{ route('admin.media-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="8" icon="bi-inbox" title="لا توجد خطط إعلامية" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $plans->total() }} خطة</div>
        <div>{{ $plans->links() }}</div>
    </div>
</div>
@endsection