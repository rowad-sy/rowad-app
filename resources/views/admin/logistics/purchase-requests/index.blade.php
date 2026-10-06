@extends('admin.logistics.layouts.master')

@section('title', 'طلبات الشراء والصيانة')

@section('logistics-content')
@php
    $currentType = $type ?? 'all';
@endphp
<x-page-header :title="'طلبات الشراء والصيانة'" :description="'دورة موحّدة: إنشاء بتوقيع ← موافقات موقعة ← تنفيذ لوجستي'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'طلبات الشراء والصيانة']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.logistics.purchase-requests.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'create')
        <div class="btn-group">
            <a href="{{ route('admin.logistics.purchase-requests.create', ['type' => $currentType !== 'all' ? $currentType : 'purchase']) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> طلب جديد
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" aria-label="اختيار نوع الطلب">
                <span class="visually-hidden">أنواع الطلب</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('admin.logistics.purchase-requests.create', ['type' => 'purchase']) }}">
                    <i class="bi bi-bag me-2" aria-hidden="true"></i>طلب شراء جديد
                </a></li>
                <li><a class="dropdown-item" href="{{ route('admin.logistics.purchase-requests.create', ['type' => 'maintenance']) }}">
                    <i class="bi bi-tools me-2" aria-hidden="true"></i>طلب صيانة جديد
                </a></li>
            </ul>
        </div>
        @endcanPermission
        <a href="{{ route('admin.logistics.export.purchase-requests', array_filter(['type' => $currentType !== 'all' ? $currentType : null])) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> تصدير
        </a>
        <form method="POST" action="{{ route('admin.logistics.import.purchase-requests') }}" enctype="multipart/form-data" class="d-inline">
            @csrf
            <label class="btn btn-outline-secondary mb-0">
                <i class="bi bi-upload me-1"></i> استيراد
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="d-none" onchange="this.form.submit()">
            </label>
        </form>
    </div>
</x-page-header>

<div class="table-container">
    <div class="p-3 border-bottom d-flex flex-wrap gap-2" role="navigation" aria-label="تبويبات نوع الطلب">
        @php
            $tabs = [
                'all' => ['label' => 'الكل', 'icon' => 'bi-collection'],
                'purchase' => ['label' => 'طلبات الشراء', 'icon' => 'bi-bag'],
                'maintenance' => ['label' => 'طلبات الصيانة', 'icon' => 'bi-tools'],
            ];
        @endphp
        @foreach ($tabs as $tabKey => $tab)
            @php
                $tabQuery = array_filter([
                    'type' => $tabKey !== 'all' ? $tabKey : null,
                    'status' => ($status ?? 'all') !== 'all' ? $status : null,
                    'center_id' => $centerId ?? null,
                    'project_id' => $projectId ?? null,
                ]);
            @endphp
            <a href="{{ route('admin.logistics.purchase-requests.index', $tabQuery) }}"
               class="btn btn-sm {{ $currentType === $tabKey ? 'btn-primary' : 'btn-outline-secondary' }}"
               @if ($currentType === $tabKey) aria-current="page" @endif>
                <i class="bi {{ $tab['icon'] }} me-1" aria-hidden="true"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
    <x-filter-bar>
            @if ($currentType !== 'all')
                <input type="hidden" name="type" value="{{ $currentType }}">
            @endif
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    @foreach ($statuses ?? [] as $statusKey)
                        <option value="{{ $statusKey }}" {{ ($status ?? '') === $statusKey ? 'selected' : '' }}>
                            {{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$statusKey] ?? $statusKey }}
                        </option>
                    @endforeach
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
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach ($projects ?? [] as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
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
                    <th>رقم الطلب</th>
                    @if (($type ?? 'all') === 'all')<th>النوع</th>@endif
                    <th>الوصف</th>
                    <th>عدد البنود</th>
                    <th>السعر الإجمالي</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseRequests ?? [] as $request)
                    <tr>
                        <td><code>{{ $request->request_number }}</code></td>
                        @if (($type ?? 'all') === 'all')
                        <td>
                            <x-status-badge :tone="$request->request_type === 'maintenance' ? 'info' : 'brand'">
                                <i class="bi {{ $request->request_type === 'maintenance' ? 'bi-tools' : 'bi-bag' }} me-1" aria-hidden="true"></i>{{ $request->typeLabel() }}
                            </x-status-badge>
                        </td>
                        @endif
                        <td>{{ Str::limit($request->items->first()?->description ?? $request->specifications, 50) }}</td>
                        <td>{{ $request->items_count ?? $request->items->count() }}</td>
                        <td>{{ number_format($request->total_price, 2) }}</td>
                        <td>{{ $request->center?->name ?? '—' }}</td>
                        <td>{{ $request->project?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusLabel = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$request->status] ?? $request->status;
                                $statusColors = [
                                    'review' => 'warning text-dark', 'approved1' => 'info', 'approved2' => 'primary',
                                    'approved' => 'success', 'executed' => 'dark', 'rejected' => 'danger',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$request->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $request->created_at?->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.logistics.purchase-requests.show', $request) }}" class="btn btn-sm btn-outline-info" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <x-audit-history :model="'App\Models\Admin\Logistics\PurchaseRequest'" :model-id="$request->id" />
                            @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'delete')
                            <form method="POST" action="{{ route('admin.logistics.purchase-requests.destroy', $request) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row :colspan="(($type ?? 'all') === 'all') ? 10 : 9" icon="bi-inbox" :title="$currentType === 'maintenance' ? 'لا توجد طلبات صيانة' : 'لا توجد طلبات شراء'" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $purchaseRequests->total() ?? 0 }} طلب
        </div>
        <div>
            {{ ($purchaseRequests ?? collect())->links() }}
        </div>
    </div>
</div>
@endsection
