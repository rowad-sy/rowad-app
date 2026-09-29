@extends('admin.logistics.layouts.master')

@section('title', 'طلب شراء #' . $purchaseRequest->id)

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: var(--color-surface-muted); border: 1px solid var(--color-border); color: var(--color-text-main); border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: var(--color-text-muted); display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
    .timeline-item { position: relative; padding-right: 1.5rem; padding-bottom: 1.25rem; }
    .timeline-item::before { content: ''; position: absolute; right: 4px; top: 8px; bottom: 0; width: 2px; background: var(--color-border); }
    .timeline-item:last-child::before { display: none; }
    .timeline-item .dot { position: absolute; right: 0; top: 4px; width: 10px; height: 10px; border-radius: 50%; }
    .signature-img { max-height: 100px; border: 1px solid var(--color-border); border-radius: 6px; padding: 4px; }
</style>
@endpush

@section('logistics-content')
@php
    $user = auth()->user();
    $isSuper = $user->type === 'super-admin';
    $isLogistics = $user->id === $purchaseRequest->refer_to_logistics_id;
    $isDirectManager = $user->id === $purchaseRequest->refer_to_direct_manager_id;
    $isPm2 = $user->id === $purchaseRequest->refer_to_pm2_id;
    $isFinance = $user->id === $purchaseRequest->refer_to_finance_id;
    $isExecutive = $user->id === $purchaseRequest->refer_to_executive_id;
    $isCurrentHolder = $purchaseRequest->isCurrentRecipient($user->id)
        || ($purchaseRequest->status === 'pending' && $isLogistics)
        || ($purchaseRequest->status === 'priced' && $isDirectManager)
        || ($purchaseRequest->status === 'pm_approved' && $isPm2)
        || ($purchaseRequest->status === 'pm2_approved' && $isFinance)
        || ($purchaseRequest->status === 'finance_approved' && $isExecutive);

    $statusColors = [
        'pending' => 'warning text-dark', 'priced' => 'info', 'pm_approved' => 'primary',
        'pm2_approved' => 'primary', 'finance_approved' => 'primary', 'approved' => 'success',
        'rejected' => 'danger', 'executed' => 'dark',
    ];
    $statusLabel = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$purchaseRequest->status] ?? $purchaseRequest->status;
    $stepColor = [
        'create' => 'secondary', 'priced' => 'info', 'pm_approved' => 'primary',
        'pm2_approved' => 'primary', 'finance_approved' => 'primary', 'approved' => 'success',
        'rejected' => 'danger', 'executed' => 'dark',
    ];
@endphp

@php $tone = (function ($c) { foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info', 'primary' => 'brand'] as $k => $t) { if (str_contains((string) $c, $k)) return $t; } return 'neutral'; })($statusColors[$purchaseRequest->status] ?? ''); @endphp
<x-page-header :title="'طلب شراء #' . $purchaseRequest->id" :breadcrumb="[['label' => 'طلبات الشراء', 'url' => route('admin.logistics.purchase-requests.index')], ['label' => '#' . $purchaseRequest->id]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone">{{ $statusLabel }}</x-status-badge></div></x-slot:meta>
    <x-audit-history :model="'App\Models\Admin\Logistics\PurchaseRequest'" :model-id="$purchaseRequest->id" />
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</x-page-header>

<div class="row g-3">
    {{-- Main Info Card --}}
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-receipt me-1"></i> بيانات طلب الشراء</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">رقم الطلب</span>
                        <span class="value"><code>#{{ $purchaseRequest->id }}</code></span>
                    </div>
                    <div class="info-item">
                        <span class="label">المركز</span>
                        <span class="value">{{ $purchaseRequest->center?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المشروع</span>
                        <span class="value">{{ $purchaseRequest->project?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مقدم الطلب</span>
                        <span class="value">{{ $purchaseRequest->user?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">رقم الميزانية</span>
                        <span class="value">{{ $purchaseRequest->budget_number ?: '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">تاريخ الإنشاء</span>
                        <span class="value">{{ $purchaseRequest->created_at?->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

                @if ($purchaseRequest->isLocked())
                    <div class="mt-3 alert alert-warning py-2 small mb-0">
                        <i class="bi bi-lock-fill me-1"></i>
                        الطلب <strong>مقفول نهائياً</strong> بعد الاعتماد —
                        @if ($purchaseRequest->locked_at)
                            أُغلق بواسطة {{ $purchaseRequest->lockedByUser?->name ?? '—' }} بتاريخ {{ $purchaseRequest->locked_at->format('Y-m-d H:i') }}
                        @else
                            لا تقبل أي تعديلات أو حذف.
                        @endif
                    </div>
                @endif

                @if ($purchaseRequest->notes)
                    <div class="mt-2 p-2" style="background:var(--status-warning-bg);color:var(--color-text-main);border-radius:6px;">
                        <small class="text-muted d-block">ملاحظات</small>
                        <span>{{ $purchaseRequest->notes }}</span>
                    </div>
                @endif

                <div class="mt-3">
                    <h6 class="mb-2">البنود</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الوصف</th>
                                    <th>الكمية</th>
                                    <th>الوحدة</th>
                                    <th>خط الميزانية</th>
                                    <th>سعر الوحدة</th>
                                    <th>الإجمالي</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseRequest->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>{{ $item->budget_line !== null ? number_format($item->budget_line, 0) : '—' }}</td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>
                                        <td>{{ $item->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="6" class="text-start">الإجمالي الكلي</td>
                                    <td>{{ number_format($purchaseRequest->total_price, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cycle & Signature Card --}}
    <div class="col-lg-4">
        <div class="table-container mb-3">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> دورة الموافقات</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">اللوجستي للتسعير</span>
                        <span class="value">{{ $purchaseRequest->logisticsStaff?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المدير المباشر</span>
                        <span class="value">{{ $purchaseRequest->directManager?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مدير المشاريع</span>
                        <span class="value">{{ $purchaseRequest->pm2User?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مدير المالية</span>
                        <span class="value">{{ $purchaseRequest->financeUser?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المدير التنفيذي</span>
                        <span class="value">{{ $purchaseRequest->executiveUser?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($isCurrentHolder && ! in_array($purchaseRequest->status, ['approved', 'executed', 'rejected'], true))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-send me-1"></i> إعادة إحالة</h5>
            </div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.logistics.purchase-requests.refer', $purchaseRequest) }}">
                    @csrf
                    <input type="hidden" name="step"
                           value="{{ $purchaseRequest->status === 'pending' ? 'logistics' : ($purchaseRequest->status === 'priced' ? 'direct_manager' : ($purchaseRequest->status === 'pm_approved' ? 'pm2' : ($purchaseRequest->status === 'pm2_approved' ? 'finance' : 'executive'))) }}">
                    <label class="form-label small fw-bold">إحالة إلى</label>
                    <select name="to_user_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— اختر —</option>
                        @foreach ($candidates ?? [] as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-send me-1"></i> إعادة إحالة</button>
                </form>
            </div>
        </div>
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-pen me-1"></i> التوقيع</h5>
            </div>
            <div class="p-3 text-center">
                @if ($purchaseRequest->signature_path)
                    @if (str_starts_with($purchaseRequest->signature_path, 'data:image'))
                        <img src="{{ $purchaseRequest->signature_path }}" alt="التوقيع" class="signature-img">
                    @else
                        <img src="{{ asset('storage/' . $purchaseRequest->signature_path) }}" alt="التوقيع" class="signature-img">
                    @endif
                @else
                    <p class="text-muted small mb-0">لا يوجد توقيع</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Step Action --}}
    @php
        $canAct = $isSuper
            || ($purchaseRequest->status === 'pending' && $isLogistics)
            || ($purchaseRequest->status === 'priced' && $isDirectManager)
            || ($purchaseRequest->status === 'pm_approved' && $isPm2)
            || ($purchaseRequest->status === 'pm2_approved' && $isFinance)
            || ($purchaseRequest->status === 'finance_approved' && $isExecutive)
            || ($purchaseRequest->status === 'approved' && $isLogistics);
    @endphp
    @if ($canAct)
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-person-check me-1"></i> الإجراء المتاح لك</h5>
            </div>
            <div class="p-3">
                @if ($purchaseRequest->status === 'pending')
                    <a href="{{ route('admin.logistics.purchase-requests.price-form', $purchaseRequest) }}" class="btn btn-primary">
                        <i class="bi bi-tags me-1"></i> تسعير الطلب
                    </a>
                @elseif ($purchaseRequest->status === 'priced')
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.manager-decide', $purchaseRequest) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">القرار <span class="text-danger">*</span></label>
                                <select name="decision" class="form-select" required>
                                    <option value="approve">موافقة وتوقيع وقفل الطلب</option>
                                    <option value="reject">رفض الطلب</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">إحالة إلى مدير المشاريع <span class="text-danger">*</span></label>
                                <select name="refer_to_pm2_id" class="form-select" required>
                                    @foreach ($candidates ?? [] as $candidate)
                                        <option value="{{ $candidate->id }}"
                                            {{ old('refer_to_pm2_id', $purchaseRequest->refer_to_pm2_id ?? $tentativePm2Id ?? '') == $candidate->id ? 'selected' : '' }}>
                                            {{ $candidate->name }} — {{ $candidate->jobTitle?->title_ar ?? ($candidate->type === 'super-admin' ? 'إدارة' : 'موظف') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">ملاحظات</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">
                            <i class="bi bi-check-lg me-1"></i> اعتماد التوقيع
                        </button>
                    </form>
                @elseif ($purchaseRequest->status === 'pm_approved')
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.pm2-decide', $purchaseRequest) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">القرار <span class="text-danger">*</span></label>
                                <select name="decision" class="form-select" required>
                                    <option value="approve">موافقة مدير المشاريع</option>
                                    <option value="reject">رفض الطلب</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">إحالة إلى مدير المالية <span class="text-danger">*</span></label>
                                <select name="refer_to_finance_id" class="form-select" required>
                                    @foreach ($candidates ?? [] as $candidate)
                                        <option value="{{ $candidate->id }}"
                                            {{ old('refer_to_finance_id', $purchaseRequest->refer_to_finance_id ?? $tentativeFinanceId ?? '') == $candidate->id ? 'selected' : '' }}>
                                            {{ $candidate->name }} — {{ $candidate->jobTitle?->title_ar ?? ($candidate->type === 'super-admin' ? 'إدارة' : 'موظف') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">ملاحظات</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">
                            <i class="bi bi-check-lg me-1"></i> اعتماد
                        </button>
                    </form>
                @elseif ($purchaseRequest->status === 'pm2_approved')
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.finance-decide', $purchaseRequest) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">القرار <span class="text-danger">*</span></label>
                                <select name="decision" class="form-select" required>
                                    <option value="approve">موافقة المالية</option>
                                    <option value="reject">رفض الطلب</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">إحالة إلى المدير التنفيذي <span class="text-danger">*</span></label>
                                <select name="refer_to_executive_id" class="form-select" required>
                                    @foreach ($candidates ?? [] as $candidate)
                                        <option value="{{ $candidate->id }}"
                                            {{ old('refer_to_executive_id', $purchaseRequest->refer_to_executive_id ?? $tentativeExecutiveId ?? '') == $candidate->id ? 'selected' : '' }}>
                                            {{ $candidate->name }} — {{ $candidate->jobTitle?->title_ar ?? ($candidate->type === 'super-admin' ? 'إدارة' : 'موظف') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">ملاحظات</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">
                            <i class="bi bi-check-lg me-1"></i> اعتماد ومدير المالية
                        </button>
                    </form>
                @elseif ($purchaseRequest->status === 'finance_approved')
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.executive-decide', $purchaseRequest) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">القرار <span class="text-danger">*</span></label>
                                <select name="decision" class="form-select" required>
                                    <option value="approve">اعتماد نهائي وقفل الطلب</option>
                                    <option value="reject">رفض الطلب</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">ملاحظات</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">
                            <i class="bi bi-check-lg me-1"></i> اعتماد الطلب نهائياً
                        </button>
                    </form>
                @elseif ($purchaseRequest->status === 'approved')
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.execute', $purchaseRequest) }}"
                          onsubmit="return confirm('هل أنت متأكد من تنفيذ هذا الطلب؟')">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small mb-1">ملاحظات التنفيذ</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="اختياري"></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check2-all me-1"></i> تنفيذ الطلب
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Workflow Timeline --}}
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-timeline me-1"></i> سجل دورة الشراء</h5>
            </div>
            <div class="p-3">
                @forelse ($purchaseRequest->workflowActions as $action)
                    <div class="timeline-item">
                        <div class="dot bg-{{ $stepColor[$action->status] ?? 'secondary' }}"></div>
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $action->fromUser?->name ?? 'النظام' }}</strong>
                                <span class="text-muted mx-1"><i class="bi bi-arrow-left"></i></span>
                                <strong>{{ $action->toUser?->name ?? ($action->action === 'create' ? 'دورة الموافقات' : '—') }}</strong>
                                <span class="badge bg-{{ $stepColor[$action->status] ?? 'secondary' }} me-1">
                                    {{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$action->status] ?? $action->status }}
                                </span>
                            </div>
                            <small class="text-muted">{{ $action->created_at?->format('Y-m-d H:i') }}</small>
                        </div>
                        @if ($action->note)
                            <p class="text-muted small mb-0 mt-1">{{ $action->note }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center py-3 mb-0">لا يوجد سجل بعد</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection