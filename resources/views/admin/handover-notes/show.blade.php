@extends('admin.layouts.master')

@section('title', 'محضر تسليم '.$handoverNote->grn_number)

@section('content')
@php
    $note = $handoverNote;
    $pr = $note->purchaseRequest;
    $me = auth()->user();
@endphp
<x-page-header :title="'محضر تسليم عهدة '.$note->grn_number"
               :description="'بموجب طلب الشراء '.$pr->request_number.' — نموذج RCD-OPR-PS-F04'"
               :breadcrumb="[['label' => 'طلبات الشراء', 'url' => route('admin.logistics.purchase-requests.index')], ['label' => $pr->request_number, 'url' => route('admin.logistics.purchase-requests.show', $pr)], ['label' => 'محضر '.$note->grn_number]]">
    <a href="{{ route('admin.logistics.handover-notes.print', $note) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> طباعة / PDF</a>
    <a href="{{ route('admin.logistics.handover-notes.export', $note) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> تصدير Excel</a>
    @if ($me->type === 'super-admin' || (int) $note->created_by === (int) $me->id)
    <form method="POST" action="{{ route('admin.logistics.handover-notes.destroy', $note) }}" class="d-inline" onsubmit="return confirm('حذف محضر التسليم؟')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
    </form>
    @endif
    <a href="{{ route('admin.logistics.purchase-requests.show', $pr) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة للطلب</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">رأس المحضر</h5></div>
            <div class="p-3">
                <div class="row small g-3">
                    <div class="col-md-4"><span class="text-muted">رقم المحضر:</span> <code>{{ $note->grn_number }}</code></div>
                    <div class="col-md-4"><span class="text-muted">تاريخ التسليم:</span> <span class="num">{{ $note->handover_date->format('Y-m-d') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">الطلب المرتبط:</span> <a href="{{ route('admin.logistics.purchase-requests.show', $pr) }}" class="text-decoration-none">{{ $pr->request_number }}</a></div>
                    <div class="col-md-4"><span class="text-muted">المركز:</span> {{ $pr->center?->code ? $pr->center->code.' - ' : '' }}{{ $pr->center?->name ?? '—' }}</div>
                    <div class="col-md-4"><span class="text-muted">المشروع:</span> {{ $pr->project?->code ? $pr->project->code.' - ' : '' }}{{ $pr->project?->name ?? '—' }}</div>
                    <div class="col-md-4"><span class="text-muted">الإدارة/القسم:</span> {{ $pr->management_unit ?? '—' }}</div>
                    @if ($note->note)
                        <div class="col-12"><span class="text-muted">ملاحظات:</span> {{ $note->note }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">بنود العهدة ({{ count($note->items ?? []) }})</h5>
                <span class="small text-muted">الرقم التسلسلي/الوحدة/الكمية/الحالة</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr><th>#</th><th>البند</th><th class="num">تسلسلي</th><th>الوحدة</th><th class="num">الكمية</th><th>الحالة</th></tr>
                    </thead>
                    <tbody>
                        @foreach (($note->items ?? []) as $row)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td>{{ $row['description'] ?? '' }}</td>
                                <td class="num ltr-cell">{{ $row['serial'] ?: '—' }}</td>
                                <td>{{ $row['unit'] ?: '—' }}</td>
                                <td class="num">{{ $row['quantity'] !== '' && $row['quantity'] !== null ? $row['quantity'] : '—' }}</td>
                                <td><x-status-badge tone="success">{{ $row['status'] ?: '—' }}</x-status-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">طرفا التسليم</h5></div>
            <div class="p-3 small">
                <div class="mb-3">
                    <div class="text-muted">تم الاستلام من قبل — GOODS RECEIVED BY</div>
                    <div class="fw-bold">{{ $note->received_by_name }}</div>
                    <div class="text-muted">{{ $note->received_by_position ?: '—' }} @if ($note->receivedByUser) · <span class="badge bg-light text-dark border">مستخدم مسجل</span> @endif</div>
                </div>
                <div>
                    <div class="text-muted">تم التسليم من قبل — GOODS DELIVERED BY</div>
                    <div class="fw-bold">{{ $note->delivered_by_name }}</div>
                    <div class="text-muted">{{ $note->delivered_by_position ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> سجل المحضر</h5></div>
            <div class="p-3">
                <ul class="list-unstyled small mb-0">
                    @foreach ($note->workflowActions->sortBy('created_at') as $step)
                        <li class="mb-2 pb-2 border-bottom">
                            <span class="fw-semibold">{{ $step->fromUser?->name ?? 'النظام' }}</span>
                            <span class="badge bg-light text-dark border">{{ $step->action }}</span>
                            <div class="text-muted">{{ $step->note ?? '—' }} · {{ $step->created_at->format('Y-m-d H:i') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
