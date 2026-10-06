@extends('admin.layouts.master')

@section('title', ($purchaseRequest->typeLabel()) . ' ' . $purchaseRequest->request_number)

@section('content')
@php
    $pr = $purchaseRequest;
    $user = auth()->user();
    $typeLabel = $pr->typeLabel();
    $toneMap = ['review' => 'warning', 'approved1' => 'info', 'approved2' => 'info', 'approved' => 'brand', 'executed' => 'success', 'rejected' => 'danger'];
    $step = $pr->currentStep();
    $holderId = $pr->stepRecipientId();
    $isHolder = $holderId !== null && (int) $holderId === (int) $user->id;
    $isCreator = (int) $pr->user_id === (int) $user->id;
    $totals = $pr->totalsByCurrency();

    $chain = [
        ['label' => 'الطلب', 'who' => $pr->user?->name, 'done' => true, 'icon' => 'bi-pencil-square', 'step' => null],
        ['label' => 'المدير المباشر', 'who' => $pr->approver1User?->name, 'done' => !in_array($pr->status, ['review', 'rejected'], true), 'icon' => 'bi-person-check', 'step' => 'approver1'],
        ['label' => 'الموارد المالية', 'who' => $pr->approver2User?->name, 'done' => in_array($pr->status, ['approved', 'approved2', 'executed'], true), 'icon' => 'bi-cash-stack', 'step' => 'approver2'],
        ['label' => 'المدير التنفيذي', 'who' => $pr->approver3User?->name, 'done' => in_array($pr->status, ['approved', 'executed'], true), 'icon' => 'bi-briefcase', 'step' => 'approver3'],
        ['label' => 'تنفيذ اللوجستي', 'who' => $pr->logisticsStaff?->name, 'done' => $pr->status === 'executed', 'icon' => 'bi-truck', 'step' => 'logistics'],
    ];
@endphp

<x-page-header :title="$typeLabel.' '.$pr->request_number" :description="$pr->project?->name ?? ''"
               :breadcrumb="[['label' => 'طلبات الشراء والصيانة', 'url' => route('admin.logistics.purchase-requests.index', ['type' => $pr->request_type ?: 'purchase'])], ['label' => $pr->request_number]]">
    <x-slot:meta><div class="mt-2 d-flex gap-2 flex-wrap">
        <x-status-badge :tone="$pr->request_type === 'maintenance' ? 'info' : 'brand'">
            <i class="bi {{ $pr->request_type === 'maintenance' ? 'bi-tools' : 'bi-bag' }} me-1" aria-hidden="true"></i>{{ $typeLabel }}
        </x-status-badge>
        <x-status-badge :tone="$toneMap[$pr->status] ?? 'neutral'">{{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$pr->status] ?? $pr->status }}</x-status-badge>
    </div></x-slot:meta>
    <a href="{{ route('admin.logistics.purchase-requests.print', $pr) }}" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i> طباعة / PDF
    </a>
    <a href="{{ route('admin.logistics.purchase-requests.export', $pr) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> تصدير Excel
    </a>
    @if ($pr->status === 'review' && $isCreator)
        <a href="{{ route('admin.logistics.purchase-requests.edit', $pr) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
    @endif
    <x-audit-history :model="'App\Models\Admin\Logistics\PurchaseRequest'" :model-id="$pr->id" />
    @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'delete')
        @unless ($pr->isLocked())
        <form method="POST" action="{{ route('admin.logistics.purchase-requests.destroy', $pr) }}" class="d-inline" onsubmit="return confirm('حذف طلب الشراء؟')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
        </form>
        @endunless
    @endcanPermission
</x-page-header>

<div class="table-container mb-3">
    <div class="pr-chain">
        @foreach ($chain as $i => $node)
            <div class="pr-node {{ $node['done'] ? 'is-done' : '' }} {{ $node['step'] !== null && $node['step'] === $step && ! $node['done'] ? 'is-current' : '' }}">
                <span class="pr-node-icon"><i class="bi {{ $node['icon'] }}" aria-hidden="true"></i></span>
                <div class="fw-semibold small">{{ $i + 1 }}. {{ $node['label'] }}</div>
                <div class="text-muted tiny">{{ $node['who'] ?? '—' }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">رأس الطلب</h5></div>
            <div class="p-3">
                <div class="row small g-3">
                    <div class="col-md-4"><span class="text-muted">رقم الطلب:</span> <code>{{ $pr->request_number }}</code></div>
                    <div class="col-md-4"><span class="text-muted">تاريخ الطلب:</span> <span class="num">{{ optional($pr->pr_date)->format('Y-m-d') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">تاريخ التنفيذ المطلوب:</span> <span class="num">{{ optional($pr->required_date)->format('Y-m-d') ?? '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">المكتب:</span> {{ $pr->center?->code ? $pr->center->code.' - ' : '' }}{{ $pr->center?->name ?? '—' }}</div>
                    <div class="col-md-4"><span class="text-muted">المشروع:</span> {{ $pr->project?->name ?? '—' }} <span class="badge bg-light text-dark border">{{ $pr->project?->code ?? '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">الإدارة/القسم:</span> {{ $pr->management_unit ?? '—' }}</div>
                    <div class="col-md-4"><span class="text-muted">أنشأه:</span> {{ $pr->user?->name ?? '—' }}</div>
                    <div class="col-md-4"><span class="text-muted">ملاحظات:</span> {{ $pr->notes ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">بنود الطلب ({{ $pr->items->count() }})</h5>
                @if ($pr->status === 'approved')
                    <span class="small text-muted">نفّذ {{ $pr->executedItemsCount() }} من {{ $pr->items->count() }}</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr>
                            @if ($pr->status === 'approved' && $isHolder)<th style="width:36px"></th>@endif
                            <th>#</th><th>المنتج</th><th class="num">الكمية</th><th>الوحدة</th><th class="num">العملة</th>
                            <th class="num">تكلفة الوحدة</th><th class="num">الإجمالي</th><th class="num">خط الميزانية</th><th>التنفيذ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pr->items as $item)
                            <tr>
                                @if ($pr->status === 'approved' && $isHolder)
                                    <td><input type="checkbox" form="prExecuteForm" name="executed_ids[]" value="{{ $item->id }}" {{ $item->executed_at ? 'checked' : '' }}></td>
                                @endif
                                <td class="num">{{ $loop->iteration }}</td>
                                <td style="min-width:160px">{{ $item->description }}</td>
                                <td class="num">{{ $item->quantity }}</td>
                                <td>{{ $item->unit }}</td>
                                <td class="num">{{ $item->currency }}</td>
                                <td class="num ltr-cell">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="num ltr-cell">{{ number_format((float) $item->total_price, 2) }}</td>
                                <td class="num ltr-cell">{{ $item->budget_line !== null && $item->budget_line !== '' ? $item->budget_line : '—' }}</td>
                                <td>
                                    @if ($item->executed_at)
                                        <x-status-badge tone="success"><i class="bi bi-check-lg me-1"></i>منفَّذ</x-status-badge>
                                        <div class="text-muted" style="font-size:.68rem">{{ $item->executor?->name }} · {{ $item->executed_at->format('Y-m-d') }}</div>
                                    @elseif (in_array($pr->status, ['approved','executed'], true))
                                        <x-status-badge tone="warning">لم ينفَّذ</x-status-badge>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="7" class="text-end">الإجمالي (دولار):</td>
                            <td class="ltr-cell">{{ number_format($totals['USD'], 2) }} $</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="7" class="text-end">الإجمالي (ليرة سورية):</td>
                            <td class="ltr-cell">{{ number_format($totals['SYP'], 2) }} SYP</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($pr->status === 'approved' && $isHolder)
            <form method="POST" action="{{ route('admin.logistics.purchase-requests.execute-items', $pr) }}" id="prExecuteForm" class="mt-2">
                @csrf
                <div class="d-flex justify-content-end">
                    <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> حفظ حالات التنفيذ</button>
                </div>
            </form>
        @endif

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-quill me-1"></i> بطاقات التوقيع الإلكتروني</h5></div>
            <div class="p-3">
                <div class="row g-3">
                    @foreach ([
                        ['requested_by', 'تم الطلب من قبل'],
                        ['direct_manager', 'موافقة المدير المباشر'],
                        ['finance', 'موافقة الموارد المالية'],
                        ['ceo', 'موافقة المدير التنفيذي'],
                    ] as [$key, $label])
                        @php
                            $sig = $key === 'requested_by'
                                ? $pr->signatures->firstWhere('role', 'requested_by')
                                : $pr->signatures->firstWhere('role', ['direct_manager' => 'approver1', 'finance' => 'approver2', 'ceo' => 'approver3'][$key]);
                        @endphp
                        <div class="col-md-6 col-xl-3">
                            <div class="sig-block">
                                <div class="sig-role">{{ $label }}</div>
                                <div class="sig-line"><b>الاسم:</b> {{ $sig?->name ?? '—' }}</div>
                                <div class="sig-line"><b>الصفة:</b> {{ $sig?->position ?? '—' }}</div>
                                <div class="sig-line"><b>التاريخ:</b> {{ $sig?->signed_at?->format('Y-m-d') ?? '—' }}</div>
                                <div class="sig-img">
                                    @if ($sig?->signature_path)
                                        <img src="{{ asset('storage/'.$sig->signature_path) }}" alt="توقيع {{ $sig?->name }}">
                                    @else
                                        <span class="text-muted">بانتظار التوقيع</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if ($isHolder && in_array($pr->status, ['review', 'approved1', 'approved2'], true))
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">الموافقة والتوقيع</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.approve', $pr) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label small fw-bold">توقيعك الإلكتروني (صورة) <span class="text-danger">*</span></label>
                        <input type="file" name="signature_image" accept="image/png,image/jpeg" class="form-control form-control-sm mb-2 @error('signature_image') is-invalid @enderror" required>
                        @error('signature_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <label class="form-label small fw-bold">{{ $pr->status === 'approved1' ? 'إحالة بعد الموافقة إلى — المدير التنفيذي' : 'إحالة بعد الموافقة إلى — الموارد المالية' }} <span class="text-danger">*</span></label>
                        <select name="next_approver_id" class="user-picker form-select form-select-sm mb-1 @error('next_approver_id') is-invalid @enderror" required data-placeholder="ابحث عن المستخدم...">
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('next_approver_id') === $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('next_approver_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)" value="{{ old('note') }}">
                        <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> موافقة وتوقيع وإحالة</button>
                    </form>

                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.reject', $pr) }}" class="mt-3">
                        @csrf
                        <label class="form-label small fw-bold">رفض الطلب</label>
                        <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="سبب الرفض" required>
                        <button class="btn btn-sm btn-danger w-100"><i class="bi bi-x-lg me-1"></i> رفض</button>
                    </form>
                </div>
            </div>
        @elseif ($isHolder && $pr->status === 'approved2')
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">الموافقة النهائية (المدير التنفيذي)</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.approve', $pr) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label small fw-bold">توقيعك الإلكتروني (صورة) <span class="text-danger">*</span></label>
                        <input type="file" name="signature_image" accept="image/png,image/jpeg" class="form-control form-control-sm mb-2 @error('signature_image') is-invalid @enderror" required>
                        @error('signature_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <label class="form-label small fw-bold">بعد الموافقة ينفَّذ بواسطة — مدير قسم اللوجستي <span class="text-danger">*</span></label>
                        <select name="logistics_user_id" class="user-picker form-select form-select-sm mb-1 @error('logistics_user_id') is-invalid @enderror" required data-placeholder="ابحث عن المستخدم...">
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('logistics_user_id') === $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('logistics_user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)" value="{{ old('note') }}">
                        <button class="btn btn-sm btn-success w-100"><i class="bi bi-shield-check me-1"></i> اعتماد نهائي وتوقيع</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($isHolder && !in_array($pr->status, ['executed', 'rejected'], true))
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">تحويل عني إلى...</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.refer', $pr) }}">
                        @csrf
                        <select name="to_user_id" class="user-picker form-select form-select-sm mb-1 @error('to_user_id') is-invalid @enderror" required data-placeholder="ابحث عن المستخدم...">
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('to_user_id') === $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('to_user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="سبب التحويل (اختياري)">
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-share me-1"></i> إعادة إحالة</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($pr->status === 'review' && !$isHolder && !$isCreator)
            <div class="alert alert-warning small py-2">هذا الطلب بانتظار موافقة <strong>{{ $pr->approver1User?->name ?? 'المحال إليه' }}</strong> — لا يمكن لغيره اعتماده.</div>
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> سجل الطلب</h5></div>
            <div class="p-3">
                <ul class="list-unstyled small mb-0">
                    @foreach ($pr->workflowActions->sortBy('created_at') as $stepLog)
                        <li class="mb-2 pb-2 border-bottom">
                            <span class="fw-semibold">{{ $stepLog->fromUser?->name ?? 'النظام' }}</span>
                            <span class="badge bg-light text-dark border">{{ $stepLog->action }}</span>
                            @if ($stepLog->toUser) <i class="bi bi-arrow-left mx-1 text-muted"></i><span class="fw-semibold">{{ $stepLog->toUser->name }}</span> @endif
                            <div class="text-muted">{{ $stepLog->note ?? '—' }} · {{ $stepLog->created_at->format('Y-m-d H:i') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
