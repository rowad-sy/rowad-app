@extends('admin.layouts.master')

@section('title', 'الخطة الإعلامية — ' . $plan->month_date->locale('ar')->translatedFormat('F Y'))

@section('content')
@php
    $statusLabels = \App\Models\Admin\MediaPlan::STATUSES;
    $statusColors = ['simple_review' => 'warning text-dark', 'simple_rowaduna' => 'info', 'simple_in_progress' => 'primary', 'simple_completed' => 'success', 'simple_rejected' => 'danger'];
    $statusTone = (function ($c) { foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info', 'primary' => 'brand'] as $key => $tone) { if (str_contains((string) $c, $key)) return $tone; } return 'neutral'; })($statusColors[$plan->status] ?? '');
    $allReported = $plan->events->isNotEmpty() && $plan->events->every(fn ($event) => in_array($event->coverage_status, ['covered', 'not_covered'], true));
    $themeQuery = request('theme') === 'rowaduna' ? ['theme' => 'rowaduna'] : [];
@endphp

<x-page-header :title="'الخطة الإعلامية — ' . $plan->month_date->locale('ar')->translatedFormat('F Y')"
               :breadcrumb="[['label' => 'الخطة الإعلامية', 'url' => route('admin.media-plans.index')], ['label' => $plan->month_date->locale('ar')->translatedFormat('F Y')]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$statusTone">{{ $statusLabels[$plan->status] ?? $plan->status }}</x-status-badge></div></x-slot:meta>
    <x-audit-history :model="'App\Models\Admin\MediaPlan'" :model-id="$plan->id" />
    <a href="{{ route('admin.media-plans.print', array_merge([$plan], $themeQuery)) }}" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer me-1" aria-hidden="true"></i> طباعة / PDF</a>
    <a href="{{ route('admin.media-plans.export', array_merge([$plan], $themeQuery)) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i> Excel</a>
    @if ($plan->status === 'simple_review' && (int) $plan->created_by === (int) auth()->id())
        @canPermission('App\Models\Admin\MediaPlan', 'edit')
        <a href="{{ route('admin.media-plans.edit', $plan) }}" class="btn btn-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i> تعديل</a>
        @endcanPermission
    @endif
    <a href="{{ route('admin.media-plans.index') }}" class="btn btn-outline-secondary">عودة</a>
</x-page-header>

@if ($plan->isLocked() && $plan->locked_at)
<div class="alert alert-warning py-2 small"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i> اعتُمدت الخطة وأصبحت مقفلة؛ لا يمكن تعديل بياناتها.</div>
@endif
@if ($plan->status === 'simple_rejected' && $plan->reason)
<div class="alert alert-danger py-2"><strong>سبب الرفض:</strong> {{ $plan->reason }}</div>
@endif
@if ($errors->any()) <div class="alert alert-danger py-2">{{ $errors->first() }}</div> @endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">بيانات الخطة</h5></div>
            <div class="p-3">
                <table class="table table-bordered table-sm mb-0">
                    <tr><th>الشهر</th><td>{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td></tr>
                    <tr><th>المركز</th><td>{{ $plan->center?->name ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $plan->project?->name ?? '—' }}</td></tr>
                    <tr><th>أنشأها</th><td>{{ $plan->creator?->name ?? '—' }}</td></tr>
                    <tr><th>مسؤول روادنا</th><td>{{ $plan->rowadunaUser?->name ?? '—' }}</td></tr>
                    <tr><th>ملاحظات عامة</th><td>{{ $plan->note ?: '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">الفعاليات والتغطية ({{ $plan->events->count() }})</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>التاريخ</th><th>الوقت</th><th>الفعالية</th><th>الموقع / نوع التغطية</th><th>الحالة والنتيجة</th><th>المراسل</th></tr></thead>
                    <tbody>
                    @forelse ($plan->events as $event)
                        @php
                            $isReporter = (int) $event->refer_to_reporter_id === (int) auth()->id();
                            $coverageLabel = \App\Models\Admin\MediaPlanEvent::COVERAGE_STATUSES[$event->coverage_status] ?? $event->coverage_status;
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ $event->event_date->format('Y-m-d') }}<br><small class="text-muted">{{ $event->day }}</small></td>
                            <td class="text-nowrap">{{ substr((string) $event->event_time, 0, 5) }}–{{ $event->event_end_time ? substr((string) $event->event_end_time, 0, 5) : '—' }}</td>
                            <td>{{ $event->event_name }}@if($event->notes)<div class="small text-muted">{{ $event->notes }}</div>@endif</td>
                            <td>{{ $event->location ?: '—' }}@if($event->coverage_type)<div class="small text-muted">{{ $event->coverage_type }}</div>@endif</td>
                            <td style="min-width:230px">
                                <span class="badge bg-{{ $event->coverage_status === 'covered' ? 'success' : ($event->coverage_status === 'not_covered' ? 'danger' : ($event->coverage_status === 'assigned' ? 'warning text-dark' : 'secondary')) }}">{{ $coverageLabel }}</span>
                                @if ($event->coverage_note)<div class="small mt-1">{{ $event->coverage_note }}</div>@endif
                                @if ($event->not_covered_reason)<div class="small text-danger mt-1">السبب: {{ $event->not_covered_reason }}</div>@endif

                                @if ($isRowadunaOwner && in_array($plan->status, ['simple_rowaduna', 'simple_in_progress'], true) && in_array($event->coverage_status, ['pending', 'assigned'], true))
                                <form method="POST" action="{{ route('admin.media-plans.events.assign', $event) }}" class="mt-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <select name="reporter_user_id" class="form-select user-picker" required>
                                            <option value="">إسناد إلى مراسل…</option>
                                            @foreach ($reporters as $reporter)
                                                <option value="{{ $reporter->id }}" @selected((int) $event->refer_to_reporter_id === (int) $reporter->id)>{{ $reporter->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-primary" aria-label="إسناد المراسل"><i class="bi bi-person-check" aria-hidden="true"></i></button>
                                    </div>
                                    @if ($reporters->isEmpty()) <div class="small text-danger mt-1">لا يوجد مستخدم نشط مخول لتسجيل التغطية.</div> @endif
                                </form>
                                @endif

                                @if ($isReporter && $event->coverage_status === 'assigned' && $plan->status === 'simple_in_progress')
                                <form method="POST" action="{{ route('admin.media-plans.events.reporter-decide', $event) }}" class="mt-2 border-top pt-2">
                                    @csrf
                                    <label class="form-label small">ملاحظتك (اختياري)</label>
                                    <input type="text" name="coverage_note" class="form-control form-control-sm mb-2" value="{{ old('coverage_note') }}">
                                    <label class="form-label small">سبب عدم التغطية (إلزامي عند اختيارها)</label>
                                    <input type="text" name="not_covered_reason" class="form-control form-control-sm mb-2" value="{{ old('not_covered_reason') }}">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-success flex-fill" name="decision" value="covered">تمت التغطية</button>
                                        <button class="btn btn-sm btn-outline-danger flex-fill" name="decision" value="not_covered">لم تتم</button>
                                    </div>
                                </form>
                                @endif
                            </td>
                            <td>{{ $event->reporter?->name ?? 'لم يُسند بعد' }}</td>
                        </tr>
                    @empty
                        <x-empty-row colspan="6" title="لا توجد فعاليات" />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if ($isProjectsManager && $plan->status === 'simple_review')
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار مدير المشاريع</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.pm2-decide', $plan) }}">
                    @csrf
                    <label class="form-label small">إحالة اختيارية إلى مسؤول روادنا</label>
                    <select name="refer_to_rowaduna_id" class="form-select form-select-sm user-picker mb-2">
                        <option value="">— اعتماد وإنهاء دون إحالة —</option>
                        @foreach ($rowadunaUsers as $rowadunaUser)
                            <option value="{{ $rowadunaUser->id }}" @selected((int) old('refer_to_rowaduna_id') === (int) $rowadunaUser->id)>{{ $rowadunaUser->name }}</option>
                        @endforeach
                    </select>
                    @if ($rowadunaUsers->isEmpty()) <div class="small text-muted mb-2">لا يوجد مستخدم مؤهل من روادنا؛ يمكنك اعتماد الخطة وإنهاؤها.</div> @endif
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-success w-100 mb-2" name="decision" value="approve">اعتماد الخطة</button>
                </form>
                <form method="POST" action="{{ route('admin.media-plans.pm2-decide', $plan) }}" onsubmit="return confirm('رفض الخطة الإعلامية؟')">
                    @csrf
                    <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="سبب الرفض" required>
                    <button class="btn btn-outline-danger w-100" name="decision" value="reject">رفض الخطة</button>
                </form>
            </div>
        </div>
        @endif

        @if ($isRowadunaOwner && in_array($plan->status, ['simple_rowaduna', 'simple_in_progress'], true))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إغلاق مسؤول روادنا</h5></div>
            <div class="p-3">
                @if ($allReported)
                    <p class="small text-muted">سُجلت نتيجة جميع الفعاليات. يمكنك إغلاق الخطة.</p>
                    <form method="POST" action="{{ route('admin.media-plans.finalize', $plan) }}">
                        @csrf
                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة الإغلاق (اختياري)">
                        <button class="btn btn-success w-100">إغلاق الخطة</button>
                    </form>
                @else
                    <p class="small text-muted mb-0">لا تُغلق الخطة حتى يسجل المراسل نتيجة كل فعالية.</p>
                @endif
            </div>
        </div>
        @endif

        @if ($isProjectsManager && $plan->status === 'simple_completed' && $plan->refer_to_rowaduna_id === null)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إعادة فتح الخطة</h5></div>
            <div class="p-3">
                <p class="small text-muted">ستعود الخطة إلى روادنا للإسناد، مع بقاء بياناتها مقفلة.</p>
                <form method="POST" action="{{ route('admin.media-plans.reopen', $plan) }}">
                    @csrf
                    <select name="refer_to_rowaduna_id" class="form-select form-select-sm user-picker mb-2" required>
                        <option value="">اختر مسؤول روادنا…</option>
                        @foreach ($rowadunaUsers as $rowadunaUser)
                            <option value="{{ $rowadunaUser->id }}">{{ $rowadunaUser->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="سبب إعادة الفتح (اختياري)">
                    <button class="btn btn-outline-primary w-100" @disabled($rowadunaUsers->isEmpty())>إعادة فتح وإحالة إلى روادنا</button>
                </form>
            </div>
        </div>
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">مسار الخطة</h5></div>
            <div class="p-3 small text-muted">
                <p>1) مدير المشروع ينشئ الخطة، وتصل إلى دور مدير المشاريع.</p>
                <p>2) مدير المشاريع يعتمدها وينهيها، أو يحيلها اختيارياً إلى روادنا.</p>
                <p>3) مسؤول روادنا يعيّن مراسلاً لكل فعالية.</p>
                <p>4) المراسل يسجل التغطية أو سبب عدمها؛ ثم يغلق مسؤول روادنا الخطة.</p>
            </div>
        </div>
        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">سجل الخطة</h5></div>
            <div class="p-3">
                @forelse ($plan->workflowActions()->with(['fromUser', 'toUser'])->latest()->limit(30)->get()->reverse() as $action)
                    <div class="small border-bottom pb-1 mb-2">
                        <span class="text-muted">{{ $action->created_at?->format('Y-m-d H:i') }}</span>
                        <strong>{{ $action->fromUser?->name ?? '—' }}</strong>
                        {{ \App\Models\WorkflowAction::ACTIONS[$action->action] ?? $action->action }}
                        @if ($action->toUser) ← {{ $action->toUser->name }} @endif
                        @if ($action->note)<div class="text-muted">{{ $action->note }}</div>@endif
                    </div>
                @empty
                    <div class="small text-muted">لا توجد إجراءات بعد.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
