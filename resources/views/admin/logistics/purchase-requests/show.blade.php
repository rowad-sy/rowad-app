@extends('admin.logistics.layouts.master')

@section('title', 'طلب شراء #' . $purchaseRequest->id)

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: #f8f9fa; border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: #6c757d; display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
    .timeline-item { position: relative; padding-right: 1.5rem; padding-bottom: 1.25rem; }
    .timeline-item::before { content: ''; position: absolute; right: 4px; top: 8px; bottom: 0; width: 2px; background: #dee2e6; }
    .timeline-item:last-child::before { display: none; }
    .timeline-item .dot { position: absolute; right: 0; top: 4px; width: 10px; height: 10px; border-radius: 50%; }
    .signature-img { max-height: 100px; border: 1px solid #dee2e6; border-radius: 6px; padding: 4px; }
</style>
@endpush

@section('logistics-content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>طلب شراء #{{ $purchaseRequest->id }}</h4>
        <p>
            <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="text-decoration-none">طلبات الشراء</a>
            / #{{ $purchaseRequest->id }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <x-audit-history model="App\Models\Admin\Logistics\PurchaseRequest" :modelId="$purchaseRequest->id" />
        <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i> عودة
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Main Info Card --}}
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bi bi-receipt me-1"></i> بيانات طلب الشراء</h5>
                <span class="badge bg-{{ $purchaseRequest->status === 'pending' ? 'warning text-dark' : ($purchaseRequest->status === 'approved' ? 'info' : ($purchaseRequest->status === 'rejected' ? 'danger' : 'success')) }} fs-6">
                    {{ $purchaseRequest->status === 'pending' ? 'قيد الانتظار' : ($purchaseRequest->status === 'approved' ? 'تمت الموافقة' : ($purchaseRequest->status === 'rejected' ? 'مرفوض' : 'منفذ')) }}
                </span>
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
                        <span class="label">تاريخ الإنشاء</span>
                        <span class="value">{{ $purchaseRequest->created_at?->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

                @if ($purchaseRequest->notes)
                    <div class="mt-2 p-2" style="background:#fff3cd;border-radius:6px;">
                        <small class="text-muted d-block">ملاحظات</small>
                        <span>{{ $purchaseRequest->notes }}</span>
                    </div>
                @endif

                <div class="mt-3">
                    <h6 class="mb-2">البنود</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الوصف</th>
                                    <th>الكمية</th>
                                    <th>الوحدة</th>
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
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>
                                        <td>{{ $item->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="5" class="text-start">الإجمالي الكلي</td>
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

    {{-- Signature Card --}}
    <div class="col-lg-4">
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

    {{-- Approval Timeline --}}
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-timeline me-1"></i> سير الموافقات</h5>
            </div>
            <div class="p-3">
                    @forelse ($purchaseRequest->approvals as $approval)
                    <div class="timeline-item">
                        <div class="dot bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}"></div>
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $approval->user?->name ?? '—' }}</strong>
                                <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }} me-1">
                                    {{ $approval->status === 'approved' ? 'موافق' : ($approval->status === 'rejected' ? 'رافض' : 'معلق') }}
                                </span>
                            </div>
                            <small class="text-muted">{{ $approval->created_at?->format('Y-m-d H:i') }}</small>
                        </div>
                        @if ($approval->notes)
                            <p class="text-muted small mb-0 mt-1">{{ $approval->notes }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center py-3 mb-0">لا توجد موافقات بعد</p>
                @endforelse

                {{-- Approve/Reject Form --}}
                @php $hasPendingApproval = $purchaseRequest->approvals->contains(fn($a) => $a->user_id === auth()->id() && $a->status === 'pending'); @endphp
                @if ($purchaseRequest->status === 'pending' && $hasPendingApproval)
                    <hr>
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.approve', $purchaseRequest) }}" class="d-inline-block me-2">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">ملاحظات</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" style="min-width:250px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg me-1"></i> موافقة
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.logistics.purchase-requests.reject', $purchaseRequest) }}" class="d-inline-block">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">ملاحظات</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" style="min-width:250px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-x-lg me-1"></i> رفض
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
