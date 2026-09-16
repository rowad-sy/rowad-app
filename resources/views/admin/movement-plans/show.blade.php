@extends('admin.layouts.master')

@section('title', 'خطة الحركة ' . $plan->request_number)

@section('content')
@php
    $colors = ['review' => 'bg-warning text-dark', 'approved' => 'bg-info', 'assigned' => 'bg-primary', 'completed' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary'];

    $user = auth()->user();
    $uid = $user->id;
    $isSuper = $user->type === 'super-admin';
    $isPm2 = $uid === (int) $plan->refer_to_pm2_id;
    $isOfficer = $uid === (int) $plan->refer_to_movement_officer_id;
    $isCurrentHolder = $plan->isCurrentRecipient($uid);
@endphp

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>خطة الحركة <code>{{ $plan->request_number }}</code></h4>
        <p>
            <a href="{{ route('admin.movement-plans.index') }}" class="text-decoration-none">خطة الحركة</a>
            / {{ $plan->request_number }}
            <span class="badge ms-2 {{ $colors[$plan->status] ?? 'bg-secondary' }}">{{ \App\Models\Admin\MovementPlan::STATUSES[$plan->status] ?? $plan->status }}</span>
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <x-audit-history :model="'App\Models\Admin\MovementPlan'" :model-id="$plan->id" />
        @canPermission('App\Models\Admin\MovementPlan', 'delete')
        <form method="POST" action="{{ route('admin.movement-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
        </form>
        @endcanPermission
        <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
    </div>
</div>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">تفاصيل الحركة</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0 small">
                    <tr><th style="width:190px">التاريخ</th><td>{{ $plan->movement_date->format('Y-m-d') }}</td></tr>
                    <tr><th>الوقت</th><td>{{ $plan->departure_time ? substr((string) $plan->departure_time, 0, 5) : '—' }} → {{ $plan->return_time ? substr((string) $plan->return_time, 0, 5) : '—' }}</td></tr>
                    <tr><th>المسار</th><td>{{ $plan->from_location ?: '—' }} ← {{ $plan->to_location ?: '—' }}</td></tr>
                    <tr><th>الغاية</th><td>{{ $plan->purpose }}</td></tr>
                    <tr><th>المركز</th><td>{{ $plan->center?->name ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $plan->project?->name ?? '—' }}</td></tr>
                    <tr><th>أنشأها</th><td>{{ $plan->creator?->name ?? '—' }}</td></tr>
                    <tr><th>إدارة المشاريع (المراجعة)</th><td>{{ $plan->projectsManager?->name ?? '—' }}</td></tr>
                    <tr><th>مسؤول الحركة</th><td>{{ $plan->movementOfficer?->name ?? '—' }}</td></tr>
                    <tr><th>وزّع المتابعة</th><td>{{ $plan->assigner?->name ?? '—' }} {{ $plan->assigned_at ? '— ' . $plan->assigned_at->format('Y-m-d H:i') : '' }}</td></tr>
                    @if ($plan->reason)
                        <tr><th>سبب الرفض/الإلغاء</th><td class="text-danger">{{ $plan->reason }}</td></tr>
                    @endif
                    <tr><th>ملاحظات</th><td>{{ $plan->notes ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">جهات المتابعة ({{ $plan->recipients->count() }})</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>المتابِع</th><th>الدور</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->recipients as $r)
                            <tr>
                                <td>{{ $r->user?->name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $r->role_label ?? 'متابعة' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center py-3 text-muted">لم تُحدَّد جهات المتابعة بعد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> سجل الحركة</h5></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>من</th><th>إلى</th><th>الإجراء</th><th>ملاحظة</th><th>الوقت</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($plan->workflowActions->sortBy('created_at') as $step)
                            <tr>
                                <td>{{ $step->fromUser?->name ?? 'النظام' }}</td>
                                <td>{{ $step->toUser?->name ?? '—' }}</td>
                                <td>{{ $step->action }}</td>
                                <td>{{ $step->note ?? '—' }}</td>
                                <td>{{ $step->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @canPermission('App\Models\Admin\MovementPlan', 'edit')
        @if ($plan->status === 'review')
            @if ($isSuper || $isPm2 || $isCurrentHolder || ($plan->refer_to_pm2_id === null && $uid !== (int) $plan->created_by))
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">إجراءات إدارة المشاريع</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.movement-plans.approve', $plan) }}" class="mb-3">
                        @csrf
                        <label class="form-label small fw-bold">الموافقة وتحويل لمسؤول الحركة</label>
                        <select name="movement_officer_id" class="form-select form-select-sm mb-2" required>
                            <option value="">— اختر مسؤول الحركة —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                        <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> اعتماد</button>
                    </form>

                    <form method="POST" action="{{ route('admin.movement-plans.reject', $plan) }}">
                        @csrf
                        <label class="form-label small fw-bold">رفض الخطة</label>
                        <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="سبب الرفض" required>
                        <button class="btn btn-sm btn-danger w-100"><i class="bi bi-x-lg me-1"></i> رفض</button>
                    </form>
                </div>
            </div>
            @endif
        @elseif ($plan->status === 'approved')
            @if ($isSuper || $isOfficer || $isCurrentHolder)
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">توزيع المتابعة (مسؤول الحركة)</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.movement-plans.assign', $plan) }}">
                        @csrf
                        <div id="recipientsContainer">
                            @include('admin.movement-plans._recipient_row', ['index' => 0, 'users' => $users])
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 mb-2" onclick="addRecipientRow()">
                            <i class="bi bi-plus-lg me-1"></i> إضافة متابِع
                        </button>
                        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-send me-1"></i> تعيين المتابِعين</button>
                    </form>
                </div>
            </div>
            @endif
        @elseif ($plan->status === 'assigned')
            @if ($isSuper || $isOfficer || $isCurrentHolder)
            <div class="table-container mb-3">
                <div class="p-3 border-bottom"><h5 class="mb-0">إكمال الخطة</h5></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.movement-plans.complete', $plan) }}" onsubmit="return confirm('تأكيد إنجاز خطة الحركة؟')">
                        @csrf
                        <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i> إنهاء كمُنجزة</button>
                    </form>
                </div>
            </div>
            @endif
        @endif
        @endcanPermission

        @if (! in_array($plan->status, ['completed', 'rejected', 'cancelled'], true))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إعادة إحالة</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.movement-plans.refer', $plan) }}">
                    @csrf
                    <input type="hidden" name="step" value="{{ $plan->status === 'review' ? 'pm2' : 'movement_officer' }}">
                    <label class="form-label small fw-bold">إحالة إلى</label>
                    <select name="to_user_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-send me-1"></i> إعادة إحالة</button>
                </form>
            </div>
        </div>
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">مسار الدورة</h5></div>
            <div class="p-3 small text-muted">
                <p>1) مدير المشروع ينشئ الخطة.</p>
                <p>2) إدارة المشاريع تعتمد وتحيلها لمسؤول الحركة.</p>
                <p>3) مسؤول الحركة يحدد المتابِعين (سائق / مدير مركز / لوجستي / الجميع).</p>
                <p>4) تُنجَز الحركة بعد المتابعة.</p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
let recipientIndex = 1;

function addRecipientRow() {
    const container = document.getElementById('recipientsContainer');
    container.insertAdjacentHTML('beforeend', {!! json_encode(view('admin.movement-plans._recipient_row', ['index' => '__INDEX__', 'users' => $users])->render()) !!}.replace(/__INDEX__/g, recipientIndex++));
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
@endpush