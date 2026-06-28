@extends('admin.logistics.layouts.master')

@section('title', 'الأصول')

@section('logistics-content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الأصول</h4>
        <p>إدارة الأصول والممتلكات</p>
    </div>
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
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">النوع</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="أثاث" {{ ($type ?? '') === 'أثاث' ? 'selected' : '' }}>أثاث</option>
                    <option value="أجهزة" {{ ($type ?? '') === 'أجهزة' ? 'selected' : '' }}>أجهزة</option>
                    <option value="أخرى" {{ ($type ?? '') === 'أخرى' ? 'selected' : '' }}>أخرى</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($filterStatus ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="جيد" {{ ($filterStatus ?? '') === 'جيد' ? 'selected' : '' }}>جيد</option>
                    <option value="تالف" {{ ($filterStatus ?? '') === 'تالف' ? 'selected' : '' }}>تالف</option>
                    <option value="صيانة" {{ ($filterStatus ?? '') === 'صيانة' ? 'selected' : '' }}>صيانة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers ?? [] as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
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
                                <span class="badge bg-success">جيد</span>
                            @elseif ($asset->status === 'تالف')
                                <span class="badge bg-danger">تالف</span>
                            @elseif ($asset->status === 'صيانة')
                                <span class="badge bg-warning text-dark">صيانة</span>
                            @else
                                <span class="badge bg-secondary">{{ $asset->status }}</span>
                            @endif
                        </td>
                        <td>{{ $asset->recipient?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.logistics.assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @canPermission('App\Models\Admin\Logistics\Asset', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.assets.destroy', $asset) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الأصل؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد أصول
                        </td>
                    </tr>
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
