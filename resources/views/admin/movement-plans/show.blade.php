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

@php $tone = (function ($c) { foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info', 'primary' => 'brand'] as $k => $t) { if (str_contains((string) $c, $k)) return $t; } return 'neutral'; })($colors[$plan->status] ?? ''); @endphp
<x-page-header :title="'خطة الحركة ' . $plan->request_number"
               :breadcrumb="[['label' => 'خطة الحركة', 'url' => route('admin.movement-plans.index')], ['label' => $plan->request_number]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone">{{ \App\Models\Admin\MovementPlan::STATUSES[$plan->status] ?? $plan->status }}</x-status-badge></div></x-slot:meta>
    @if ($plan->status === 'review' && ($isSuper || $uid === (int) $plan->created_by))
        <a href="{{ route('admin.movement-plans.edit', $plan) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
    @endif
    <x-audit-history :model="'App\Models\Admin\MovementPlan'" :model-id="$plan->id" />
    @canPermission('App\Models\Admin\MovementPlan', 'delete')
    <form method="POST" action="{{ route('admin.movement-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
    </form>
    @endcanPermission
    <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</x-page-header>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">رأس الخطة</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0 small">
                    <tr><th style="width:190px">شهر الخطة</th><td>{{ $plan->plan_month?->format('Y-m') ?? '—' }}</td></tr>
                    <tr><th>عدد الحركات</th><td>{{ $plan->entries->count() }}</td></tr>
                    <tr><th>المركز</th><td>{{ $plan->center?->name ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $plan->project?->name ?? '—' }}</td></tr>
                    <tr><th>أنشأها</th><td>{{ $plan->creator?->name ?? '—' }}</td></tr>
                    <tr><th>المُحال للمراجعة</th><td>{{ $plan->projectsManager?->name ?? '—' }}</td></tr>
                    <tr><th>مسؤول الحركة</th><td>{{ $plan->movementOfficer?->name ?? '—' }}</td></tr>
                    <tr><th>وزّع المتابعة</th><td>{{ $plan->assigner?->name ?? '—' }} {{ $plan->assigned_at ? '— ' . $plan->assigned_at->format('Y-m-d H:i') : '' }}</td></tr>
                    @if ($plan->reason)
                        <tr><th>سبب الرفض/الإلغاء</th><td class="text-danger">{{ $plan->reason }}</td></tr>
                    @endif
                    <tr><th>ملاحظات عامة</th><td>{{ $plan->notes ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">حركات الخطة ({{ $plan->entries->count() }})</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>الانطلاق</th>
                            <th>العودة</th>
                            <th>من ← إلى</th>
                            <th>الغاية</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->entries as $en)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td class="num">{{ $en->movement_date->format('Y-m-d') }}</td>
                                <td class="num">{{ $en->departure_time ? substr((string) $en->departure_time, 0, 5) : '—' }}</td>
                                <td class="num">{{ $en->return_time ? substr((string) $en->return_time, 0, 5) : '—' }}</td>
                                <td>{{ $en->from_location ?: '—' }} <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ $en->to_location ?: '—' }}</td>
                                <td style="min-width:180px">{{ $en->purpose }}</td>
                                <td class="text-muted">{{ $en->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <x-empty-row colspan="7" title="لا توجد حركات في هذه الخطة" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">جهات المتابعة ({{ $plan->recipients->count() }})</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr><th>المتابِع</th><th>الدور</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->recipients as $r)
                            <tr>
                                <td>{{ $r->user?->name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $r->role_label ?? 'متابعة' }}</span></td>
                            </tr>
                        @empty
                            <x-empty-row colspan="2" title="لم تُحدَّد جهات المتابعة بعد" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> سجل الحركة</h5></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead>
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
                        <select name="movement_officer_id" class="user-picker form-select form-select-sm mb-1 @error('movement_officer_id') is-invalid @enderror" required
                                data-placeholder="ابحث عن مسؤول الحركة...">
                            <option value="">— اختر مسؤول الحركة —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('movement_officer_id') === $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('movement_officer_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
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
                    <select name="to_user_id" class="user-picker form-select form-select-sm mb-1 @error('to_user_id') is-invalid @enderror" required
                            data-placeholder="ابحث عن المستخدم...">
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('to_user_id') === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('to_user_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-send me-1"></i> إعادة إحالة</button>
                </form>
            </div>
        </div>
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">مسار الدورة</h5></div>
            <div class="p-3 small text-muted">
                <p>1) مدير المشروع ينشئ خطة شهرية تحوي عدة حركات ويحيلها للمراجعة.</p>
                <p>2) المُحال له (عادةً إدارة المشاريع) يعتمد ويحيل لمسؤول الحركة.</p>
                <p>3) مسؤول الحركة يحدد المتابِعين (سائق / مدير مركز / لوجستي / الجميع).</p>
                <p>4) تُنجَز الخطة بعد متابعة كل الحركات.</p>
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