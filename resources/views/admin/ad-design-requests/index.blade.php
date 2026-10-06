@extends('admin.layouts.master')

@section('title', 'طلبات التصميم الإعلاني')

@section('content')
<x-page-header :title="'طلبات التصميم الإعلاني'" :description="'تصاميم إعلانات الدورات والأنشطة — دورة روادنا'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'طلبات التصميم']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.ad-design-requests.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\AdDesignRequest', 'create')
        <a href="{{ route('admin.ad-design-requests.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> طلب جديد
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Admin\AdDesignRequest::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                    <th>العنوان</th>
                    <th>المشروع</th>
                    <th>المطلوب قبل</th>
                    <th>الحالة</th>
                    <th>المصمم</th>
                    <th>أنشأه</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adRequests as $ad)
                    <tr>
                        <td>{{ $ad->id }}</td>
                        <td class="fw-medium">{{ $ad->title }}</td>
                        <td>{{ $ad->project?->name ?? '—' }}</td>
                        <td>{{ optional($ad->due_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            @php
                                $tone = match ($ad->status) {
                                    'published' => 'success', 'rejected' => 'danger',
                                    'ready_for_review', 'to_publish' => 'primary',
                                    default => 'warning',
                                };
                            @endphp
                            <span class="badge bg-{{ $tone }}">{{ $ad->statusLabel() }}</span>
                        </td>
                        <td>{{ $ad->designer?->name ?? '—' }}</td>
                        <td>{{ $ad->creator?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.ad-design-requests.show', $ad) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @if (! $ad->isLocked())
                                @canPermission('App\Models\Admin\AdDesignRequest', 'edit')
                                <a href="{{ route('admin.ad-design-requests.edit', $ad) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @endcanPermission
                            @endif
                            <x-audit-history :model="'App\Models\Admin\AdDesignRequest'" :model-id="$ad->id" />
                            @canPermission('App\Models\Admin\AdDesignRequest', 'delete')
                            @if (! $ad->isLocked())
                            <form method="POST" action="{{ route('admin.ad-design-requests.destroy', $ad) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endif
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="8" icon="bi-inbox" title="لا توجد طلبات تصميم" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $adRequests->total() }} طلب</div>
        <div>{{ $adRequests->links() }}</div>
    </div>
</div>
@endsection
