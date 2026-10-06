@extends('admin.layouts.master')

@section('title', 'روادنا — لوحة القسم')

@section('content')
@php
    $uid = $user->id;
    $reviewerDefault = null;
@endphp

<style>
    .rw-hero { background: linear-gradient(135deg, #121E40 0%, #1B2B5A 55%, #1E3266 100%); border: 1px solid #2D437D; border-radius: 16px; color: #fff; padding: 22px 24px; }
    .rw-hero .rw-badge { display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; border-radius: 16px; background: #FF4B3E; font-weight: 800; font-size: 15px; box-shadow: 0 8px 24px rgba(255, 75, 62, .35); }
    .rw-hero a.btn { background: #FF4B3E; border: 0; color: #fff; font-weight: 700; }
    .rw-hero a.btn:hover { background: #E03E32; color: #fff; }
    .rw-hero a.btn.outline { background: transparent; border: 1px solid #2D437D; color: #fff; font-weight: 500; }
    .rw-hero a.btn.outline:hover { background: #1E3266; }
    .rw-card { border: 1px solid #2D437D; }
    .rw-card .card-head { background: #1B2B5A; color: #fff; border-radius: 0 !important; }
    .rw-card .card-head .bi { color: #FF4B3E; }
    .rw-table thead { background: #121E40; color: #fff; }
    .rw-table thead th { border-color: #2D437D; font-size: 12px; }
    .rw-pill { background: #1E3266; color: #fff; border: 1px solid #2D437D; border-radius: 999px; padding: 2px 12px; font-size: 12px; }
    .rw-kpi { border-left: 4px solid #FF4B3E; }
</style>

<div class="rw-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div class="d-flex align-items-center gap-3">
        <span class="rw-badge">روادنا</span>
        <div>
            <h3 class="mb-0 fw-bold">روادنا</h3>
            <div class="small" style="color:#A0AEC0">قسم الإنتاج والمتابعة الإعلامية — التغطيات، التصاميم، والنشر</div>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @canPermission('App\Models\Admin\MediaPlan', 'create')
        <a href="{{ route('admin.media-plans.create') }}" class="btn btn-sm"><i class="bi bi-megaphone me-1"></i> خطة إعلامية</a>
        @endcanPermission
        @canPermission('App\Models\Admin\AdDesignRequest', 'create')
        <a href="{{ route('admin.ad-design-requests.create') }}" class="btn btn-sm"><i class="bi bi-brush me-1"></i> طلب تصميم</a>
        @endcanPermission
        @canPermission('App\Models\Admin\MediaPlan', 'view')
        <a href="{{ route('admin.media-plans.index') }}" class="btn btn-sm outline"><i class="bi bi-list-ul me-1"></i> كل الخطط</a>
        @endcanPermission
        @canPermission('App\Models\Admin\AdDesignRequest', 'view')
        <a href="{{ route('admin.ad-design-requests.index') }}" class="btn btn-sm outline"><i class="bi bi-collection me-1"></i> كل التصاميم</a>
        @endcanPermission
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($kpis as $kpi)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="table-container rw-card rw-kpi p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-3 fw-bold" style="color:#1B2B5A">{{ $kpi['value'] }}</div>
                    <div class="small text-muted">{{ $kpi['label'] }}</div>
                </div>
                <i class="bi {{ $kpi['icon'] }} fs-2" style="color:#FF4B3E"></i>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">

    @if ($plansAwaitingAssign->isNotEmpty() || $plansInProgress->isNotEmpty())
    <div class="col-12">
        <div class="table-container rw-card">
            <div class="p-3 border-bottom card-head d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>خطط ضمن مسؤولية روادنا</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 rw-table">
                    <thead>
                        <tr>
                            <th>الشهر</th><th>المركز</th><th>المشروع</th><th>الفعاليات</th><th>الحالة</th><th>إجراءات روادنا</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plansAwaitingAssign->concat($plansInProgress) as $plan)
                            <tr>
                                <td class="fw-medium">{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td>
                                <td>{{ $plan->center?->name ?? '—' }}</td>
                                <td>{{ $plan->project?->name ?? '—' }}</td>
                                <td><span class="rw-pill">{{ $plan->events_count }}</span></td>
                                <td><span class="rw-pill">{{ \App\Models\Admin\MediaPlan::STATUSES[$plan->status] ?? $plan->status }}</span></td>
                                <td>
                                    <a href="{{ route('admin.media-plans.show', $plan) }}" class="btn btn-sm btn-outline-info">إسناد ومتابعة</a>
                                    <a href="{{ route('admin.media-plans.print', array_merge([$plan], ['theme' => 'rowaduna'])) }}" class="btn btn-sm btn-outline-dark" target="_blank" title="طباعة بهوية روادنا"><i class="bi bi-printer"></i></a>
                                    <a href="{{ route('admin.media-plans.export', array_merge([$plan], ['theme' => 'rowaduna'])) }}" class="btn btn-sm btn-outline-success" title="Excel بهوية روادنا"><i class="bi bi-file-earmark-spreadsheet"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($canMedia && $myCoverageEvents->isNotEmpty())
    <div class="col-lg-6">
        <div class="table-container rw-card h-100">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-camera-video me-2"></i>تغطيات بانتظاري (مراسل)</h5></div>
            <div class="p-2">
                @foreach ($myCoverageEvents as $event)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div>
                            <strong>{{ $event->event_name }}</strong>
                            <div class="text-muted">{{ $event->event_date->format('Y-m-d') }} {{ substr((string) $event->event_time, 0, 5) }} — {{ $event->plan?->month_date?->locale('ar')->translatedFormat('F Y') }}</div>
                        </div>
                        <a href="{{ route('admin.media-plans.show', ['plan' => $event->media_plan_id]) }}#ev{{ $event->id }}" class="btn btn-sm btn-outline-primary">تسجيل التغطية</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($canMedia && $myPublishEvents->isNotEmpty())
    <div class="col-lg-6">
        <div class="table-container rw-card h-100">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-scissors me-2"></i>طابور المونتير/الناشر</h5></div>
            <div class="p-2">
                @foreach ($myPublishEvents as $event)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div>
                            <strong>{{ $event->event_name }}</strong>
                            <div class="text-muted">{{ \App\Models\Admin\MediaPlanEvent::PUBLISH_STATUSES[$event->publish_status] ?? '' }}</div>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            @if ($event->media_items_url)
                                <a href="{{ $event->media_items_url }}" target="_blank" class="rw-pill text-decoration-none" title="مواد التغطية">المواد</a>
                            @endif
                            <a href="{{ route('admin.media-plans.show', ['plan' => $event->media_plan_id]) }}" class="btn btn-sm btn-outline-primary">متابعة</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($canMedia && $myReviewEvents->isNotEmpty())
    <div class="col-lg-6">
        <div class="table-container rw-card h-100">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-eye me-2"></i>مراجعات معاينة بانتظاري</h5></div>
            <div class="p-2">
                @foreach ($myReviewEvents as $event)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div>
                            <strong>{{ $event->event_name }}</strong>
                            <div class="text-muted">{{ $event->event_date->format('Y-m-d') }} — {{ $event->plan?->month_date?->locale('ar')->translatedFormat('F Y') }}</div>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            @if ($event->preview_url)
                                <a href="{{ $event->preview_url }}" target="_blank" class="rw-pill text-decoration-none">معاينة</a>
                            @endif
                            <a href="{{ route('admin.media-plans.show', ['plan' => $event->media_plan_id]) }}" class="btn btn-sm btn-outline-success">قرار</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($canDesign && $adAwaitingMe->isNotEmpty())
    <div class="col-lg-6">
        <div class="table-container rw-card h-100">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-brush me-2"></i>تصاميم بانتظار تنفيذي</h5></div>
            <div class="p-2">
                @foreach ($adAwaitingMe as $ad)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div>
                            <strong>{{ $ad->title }}</strong>
                            <div class="text-muted">
                                {{ $ad->creator?->name ?? '—' }}
                                @if ($ad->due_date) — قبل {{ $ad->due_date->format('Y-m-d') }}@endif
                            </div>
                        </div>
                        <a href="{{ route('admin.ad-design-requests.show', $ad) }}" class="btn btn-sm btn-outline-primary">
                            {{ $ad->revision_note ? 'إعادة تنفيذ' : 'تنفيذ ورفع' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($canDesign && $myAdRequests->isNotEmpty())
    <div class="col-lg-6">
        <div class="table-container rw-card h-100">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-inbox me-2"></i>طلبات أنشأتها (نشطة)</h5></div>
            <div class="p-2">
                @foreach ($myAdRequests as $ad)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div>
                            <strong>{{ $ad->title }}</strong>
                            <div class="text-muted">{{ $ad->statusLabel() }}</div>
                        </div>
                        @if ($ad->design_url && $ad->status === 'ready_for_review' && $ad->created_by === $uid)
                            <a href="{{ $ad->design_url }}" target="_blank" class="rw-pill text-decoration-none">رؤية التصميم</a>
                        @endif
                        <a href="{{ route('admin.ad-design-requests.show', $ad) }}" class="btn btn-sm btn-outline-info">متابعة</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($canDesign && $adReviewQueue->isNotEmpty())
    <div class="col-12">
        <div class="table-container rw-card">
            <div class="p-3 border-bottom card-head"><h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>طلبات تصميم قيد الاعتماد</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 rw-table small">
                    <thead><tr><th>الطلب</th><th>صاحب الطلب</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($adReviewQueue as $ad)
                            <tr>
                                <td class="fw-medium">{{ $ad->title }}</td>
                                <td>{{ $ad->creator?->name ?? '—' }}</td>
                                <td><span class="rw-pill">{{ $ad->statusLabel() }}</span></td>
                                <td><a href="{{ route('admin.ad-design-requests.show', $ad) }}" class="btn btn-sm btn-outline-info">فتح</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
