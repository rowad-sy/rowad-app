@extends('admin.logistics.layouts.master')

@section('title', 'الأصول')

@section('logistics-content')
<x-page-header :title="'الأصول'" :description="'إدارة الأصول والممتلكات'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'الأصول']]">
    <div class="d-flex gap-2">
        @canPermission('App\Models\Admin\Logistics\Asset', 'create')
        <a href="{{ route('admin.logistics.assets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة أصل
        </a>
        @endcanPermission
        <a href="{{ route('admin.logistics.export.assets') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> تصدير
        </a>
        <form method="POST" action="{{ route('admin.logistics.import.assets') }}" enctype="multipart/form-data" class="d-inline">
            @csrf
            <label class="btn btn-outline-secondary mb-0">
                <i class="bi bi-upload me-1"></i> استيراد
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="d-none" onchange="this.form.submit()">
            </label>
        </form>
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">النوع</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="أثاث" {{ ($type ?? '') === 'أثاث' ? 'selected' : '' }}>أثاث</option>
                    <option value="أجهزة" {{ ($type ?? '') === 'أجهزة' ? 'selected' : '' }}>أجهزة</option>
                    <option value="أخرى" {{ ($type ?? '') === 'أخرى' ? 'selected' : '' }}>أخرى</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($filterStatus ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="جيد" {{ ($filterStatus ?? '') === 'جيد' ? 'selected' : '' }}>جيد</option>
                    <option value="تالف" {{ ($filterStatus ?? '') === 'تالف' ? 'selected' : '' }}>تالف</option>
                    <option value="صيانة" {{ ($filterStatus ?? '') === 'صيانة' ? 'selected' : '' }}>صيانة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($centers ?? [] as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>كود الأصل</th>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الغرفة</th>
                    <th>الحالة</th>
                    <th>المستلم</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets ?? [] as $asset)
                    <tr>
                        <td><code>{{ $asset->asset_code }}</code></td>
                        <td class="fw-medium">{{ $asset->name }}</td>
                        <td>{{ $asset->type }}</td>
                        <td>{{ $asset->center?->name ?? '—' }}</td>
                        <td>{{ $asset->project?->name ?? '—' }}</td>
                        <td>{{ $asset->room_number ?? '—' }}</td>
                        <td>
                            @if ($asset->status === 'جيد')
                                <x-status-badge tone="success">جيد</x-status-badge>
                            @elseif ($asset->status === 'تالف')
                                <x-status-badge tone="danger">تالف</x-status-badge>
                            @elseif ($asset->status === 'صيانة')
                                <x-status-badge tone="warning">صيانة</x-status-badge>
                            @else
                                <x-status-badge>{{ $asset->status }}</x-status-badge>
                            @endif
                        </td>
                        <td>{{ $asset->recipient?->name ?? '—' }}</td>
                        <td>
                            @canPermission('App\Models\Admin\Logistics\Asset', 'edit')
                            <a href="{{ route('admin.logistics.assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Logistics\Asset'" :model-id="$asset->id" />
                            @canPermission('App\Models\Admin\Logistics\Asset', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.assets.destroy', $asset) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الأصل؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" icon="bi-inbox" title="لا توجد أصول" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $assets->total() ?? 0 }} أصل
        </div>
        <div>
            {{ ($assets ?? collect())->links() }}
        </div>
    </div>
</div>
@endsection
