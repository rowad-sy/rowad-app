@extends('admin.layouts.master')

@section('title', $eventCard->name)

@php
    $user = auth()->user();
    $isApprover = $eventCard->status === 'review' && ($user->type === 'super-admin' || $eventCard->isCurrentRecipient($user->id) || (int) $eventCard->referred_user_id === $user->id);
    $isFinalizer = $eventCard->status === 'approved' && ($user->type === 'super-admin' || $eventCard->isCurrentRecipient($user->id) || \App\Helpers\PermissionHelper::can($user, 'page:admin.projects-manager.dashboard', 'view'));
@endphp

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-calendar-event me-2 text-danger"></i>{{ $eventCard->name }}</h4>
        <p>
            <a href="{{ route('admin.event-cards.index') }}" class="text-decoration-none">بطاقات الفعاليات</a>
            / <span class="badge {{ $eventCard->statusBadgeClass() }}">{{ $eventCard->statusLabel() }}</span>
        </p>
    </div>
    <div class="d-flex gap-2">
        @if (! $eventCard->isLocked())
            @canPermission('App\Models\Admin\EventCard', 'edit')
                <a href="{{ route('admin.event-cards.edit', $eventCard) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>تعديل</a>
            @endcanPermission
        @endif
    </div>
</div>

@if ($eventCard->status === 'rejected' && $eventCard->reason)
    <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> سبب الرفض: {{ $eventCard->reason }}</div>
@endif

<div class="row g-3">
    {{-- البطاقة --}}
    <div class="col-lg-8">
        <div class="form-card mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-danger me-1"></i> معلومات الفعالية</h6>
            <div class="row small">
                <div class="col-md-6 mb-2"><span class="text-muted">المشروع:</span> <strong>{{ $eventCard->project?->name ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">كود المشروع:</span> <strong>{{ $eventCard->project?->code ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">التاريخ:</span> <strong>{{ $eventCard->event_date?->translatedFormat('l d F Y') ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">المكان:</span> <strong>{{ $eventCard->location ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">المنظم:</span> <strong>{{ $eventCard->organizer ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">مقدم الحفل:</span> <strong>{{ $eventCard->presenter ?? '—' }}</strong></div>
                <div class="col-md-6 mb-2"><span class="text-muted">عدد الحضور المتوقع:</span> <strong>{{ $eventCard->expected_attendance ?? '—' }}</strong></div>
            </div>
            @if ($eventCard->objectives)
                <div class="mt-2"><span class="text-muted small">الأهداف:</span><p class="mb-0">{{ $eventCard->objectives }}</p></div>
            @endif
        </div>

        <div class="form-card mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-clock-history text-danger me-1"></i> الجدول الزمني</h6>
            <p class="small mb-0">
                المكان: {{ $eventCard->schedule_place ?? '—' }} — التاريخ: {{ $eventCard->schedule_date?->format('Y-m-d') ?? '—' }} — الساعة: {{ $eventCard->schedule_time ?? '—' }}
            </p>
        </div>

        <div class="form-card mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-people text-danger me-1"></i> تقسيم المهام على الفريق</h6>
            <div class="row small">
                <div class="col-md-4"><div class="text-muted">إدارة المشاريع</div><div>{{ $eventCard->tasks_projects ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted">إدارة العمليات</div><div>{{ $eventCard->tasks_operations ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted">المراقبة والتقييم</div><div>{{ $eventCard->tasks_mel ?: '—' }}</div></div>
            </div>
        </div>

        @if (count($eventCard->content_items ?? []))
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-card-list text-danger me-1"></i> المحتوى والفقرات</h6>
                <table class="table table-sm">
                    <thead><tr><th>الفقرة</th><th>المحتوى</th><th>المسؤول</th><th>المدة</th></tr></thead>
                    <tbody>@foreach ($eventCard->content_items as $r)<tr><td>{{ $r['item'] ?? '' }}</td><td>{{ $r['content'] ?? '' }}</td><td>{{ $r['responsible'] ?? '' }}</td><td>{{ $r['duration'] ?? '' }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        @endif

        <div class="row g-3">
            @foreach ([
                'logistics_items' => ['اللوجستيات المطلوبة', 'bi-truck', ['item' => 'البند', 'responsible' => 'المسؤول']],
                'purchases_items' => ['المشتريات والمواد', 'bi-bag', ['item' => 'البند', 'responsible' => 'المسؤول']],
                'media_items' => ['الإعلام والتوثيق', 'bi-megaphone', ['coverage' => 'التغطية', 'responsible' => 'المسؤول']],
            ] as $key => [$title, $icon, $cols])
                @if (count($eventCard->{$key} ?? []))
                    <div class="col-md-6">
                        <div class="form-card h-100">
                            <h6 class="fw-bold mb-2"><i class="bi {{ $icon }} text-danger me-1"></i> {{ $title }}</h6>
                            <table class="table table-sm">
                                <thead><tr>@foreach ($cols as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
                                <tbody>@foreach ($eventCard->{$key} as $r)<tr>@foreach (array_keys($cols) as $k)<td>{{ $r[$k] ?? '' }}</td>@endforeach</tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if (count($eventCard->transport_items ?? []))
            <div class="form-card my-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-bus-front text-danger me-1"></i> المواصلات</h6>
                <table class="table table-sm">
                    <thead><tr><th>المواصلات المطلوبة</th><th>النوع</th><th>مسؤول التنسيق</th></tr></thead>
                    <tbody>@foreach ($eventCard->transport_items as $r)<tr><td>{{ $r['request'] ?? '' }}</td><td>{{ $r['type'] ?? '' }}</td><td>{{ $r['responsible'] ?? '' }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        @endif

        @if ($eventCard->hr_notes)
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-badge text-danger me-1"></i> الموارد البشرية</h6>
                <p class="mb-0 small">{{ $eventCard->hr_notes }}</p>
            </div>
        @endif

        @if (count($eventCard->budget_items ?? []))
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-cash-stack text-danger me-1"></i> موازنة الفعالية</h6>
                <table class="table table-sm">
                    <thead><tr><th>البند</th><th>شرح</th><th>الكلفة</th></tr></thead>
                    <tbody>@foreach ($eventCard->budget_items as $r)<tr><td>{{ $r['item'] ?? '' }}</td><td>{{ $r['description'] ?? '' }}</td><td>{{ number_format((float) ($r['cost'] ?? 0), 2) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><th colspan="2" class="text-end">الإجمالي</th><th>{{ number_format((float) $eventCard->budget_total, 2) }}</th></tr></tfoot>
                </table>
            </div>
        @endif

        @if ($eventCard->post_evaluation)
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-clipboard-check text-danger me-1"></i> تقييم بعد الفعالية</h6>
                <p class="mb-0 small">{{ $eventCard->post_evaluation }}</p>
            </div>
        @endif
    </div>

    {{-- شريط الإحالة --}}
    <div class="col-lg-4">
        <div class="form-card mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-signpost-2 text-danger me-1"></i> دورة الإحالة</h6>
            <ul class="list-unstyled small">
                <li class="mb-2"><span class="text-muted">أنشأها:</span> {{ $eventCard->creator?->name ?? '—' }}</li>
                <li class="mb-2"><span class="text-muted">أُحيلت إلى:</span> <strong>{{ $eventCard->referredUser?->name ?? '—' }}</strong></li>
                @if ($eventCard->approvedByUser)
                    <li class="mb-2"><span class="text-muted">وافق عليها:</span> {{ $eventCard->approvedByUser->name }} <span class="text-muted">({{ $eventCard->approved_at?->format('Y-m-d') }})</span></li>
                @endif
                @if ($eventCard->finalizedByUser)
                    <li class="mb-2"><span class="text-muted">اعتمدها:</span> {{ $eventCard->finalizedByUser->name }} <span class="text-muted">({{ $eventCard->finalized_at?->format('Y-m-d') }})</span></li>
                @endif
            </ul>
        </div>

        @if ($isApprover)
            @canPermission('App\Models\Admin\EventCard', 'edit')
                <div class="form-card mb-3 border-start border-4" style="border-color: var(--color-primary) !important;">
                    <h6 class="fw-bold mb-3">قرارك — الموافقة على البطاقة</h6>
                    <form method="POST" action="{{ route('admin.event-cards.approve', $eventCard) }}" class="mb-2">
                        @csrf
                        <textarea name="note" rows="2" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)"></textarea>
                        <button class="btn btn-brand btn-sm w-100"><i class="bi bi-check2-circle me-1"></i> موافقة وإحالة للاعتماد</button>
                    </form>
                    <form method="POST" action="{{ route('admin.event-cards.reject', $eventCard) }}">
                        @csrf
                        <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" placeholder="سبب الرفض" required></textarea>
                        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle me-1"></i> رفض</button>
                    </form>
                </div>
            @endcanPermission
        @endif

        @if ($isFinalizer)
            @canPermission('App\Models\Admin\EventCard', 'edit')
                <div class="form-card mb-3 border-start border-4" style="border-color: #059669 !important;">
                    <h6 class="fw-bold mb-2">الاعتماد النهائي</h6>
                    <form method="POST" action="{{ route('admin.event-cards.finalize', $eventCard) }}">
                        @csrf
                        <textarea name="note" rows="2" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)"></textarea>
                        <button class="btn btn-success btn-sm w-100"><i class="bi bi-patch-check me-1"></i> اعتماد البطاقة</button>
                    </form>
                </div>
            @endcanPermission
        @endif

        <div class="form-card">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-danger me-1"></i> سجل الإجراءات</h6>
            <ul class="list-unstyled small mb-0">
                @forelse ($eventCard->workflowActions()->latest()->get() as $action)
                    <li class="mb-2 pb-2 border-bottom">
                        <span class="badge text-bg-light border">{{ $action->action }}</span>
                        <div class="text-muted">{{ $action->fromUser?->name ?? 'النظام' }}{{ $action->toUser ? ' ← ' . $action->toUser->name : '' }}</div>
                        @if ($action->note)<div class="text-muted fst-italic">"{{ $action->note }}"</div>@endif
                    </li>
                @empty
                    <li class="text-muted">لا يوجد سجل بعد.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
