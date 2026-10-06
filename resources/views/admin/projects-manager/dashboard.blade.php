@extends('admin.layouts.master')

@section('title', 'لوحة مدير المشاريع')

@section('content')
<x-page-header title="لوحة مدير المشاريع" description="نظرة شاملة على جميع المشاريع ومدراء المشاريع"
               :breadcrumb="[['label' => 'لوحة مدير المشاريع']]">
    <x-slot:meta>
        <span class="badge-status is-brand mt-2"><i class="bi bi-globe2" aria-hidden="true"></i> كل المشاريع والمراكز</span>
    </x-slot:meta>
    <a href="{{ route('admin.projects.calendar') }}" class="btn btn-outline-primary">
        <i class="bi bi-calendar3 me-1" aria-hidden="true"></i> التقويم الزمني
    </a>
</x-page-header>

@php
    // الأولويات: إجراءات مستحقة للمستخدم (طلبات شراء محالة إليه للتوقيع) + قوائم مراجعة عامة قيد الانتظار
    $generalReviewCount = $movementAwaitingReview->count() + $documentsUnderReviewCount;
    $priorityTotal = $prAwaitingPm2Sign + $generalReviewCount;
@endphp
@if ($priorityTotal > 0)
<section class="dash-section" aria-labelledby="attn-title">
    <h2 class="section-title" id="attn-title">الأولويات
        <span class="count-pill is-attention">{{ $priorityTotal }}</span>
    </h2>

    @if ($prAwaitingPm2Sign > 0)
    {{-- مستحقة لك: status = review ومحالة إليك (refer_to_approver1_id) — نفس شرط عدّاد «بموجودي للتوقيع» --}}
    <div class="table-container attention-card mb-3">
        <div class="p-3 border-bottom d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold"><i class="bi bi-pen me-1" aria-hidden="true"></i> طلبات شراء بانتظار توقيعك</span>
            <span class="count-pill is-attention section-title-pill">{{ $prAwaitingPm2Sign }}</span>
            <a href="{{ route('admin.logistics.purchase-requests.index', ['status' => 'review']) }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الطلبات بحالة «{{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES['review'] ?? 'review' }}»</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>رقم الطلب</th><th>البنود</th><th>السعر المتوقع</th><th>المركز</th><th>المشروع</th><th>أنشأه</th><th><span class="visually-hidden">الإجراء</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($awaitingMyPm2Requests as $request)
                        <tr>
                            <td><code>#{{ $request->id }}</code></td>
                            <td>{{ $request->items->take(2)->pluck('description')->join('، ') ?: ($request->specifications ? Str::limit($request->specifications, 50) : '—') }}</td>
                            <td class="num">{{ number_format($request->total_price, 2) }}</td>
                            <td>{{ $request->center?->name ?? '—' }}</td>
                            <td>{{ $request->project?->name ?? '—' }}</td>
                            <td>{{ $request->user?->name ?? '—' }}</td>
                            <td><a href="{{ route('admin.logistics.purchase-requests.show', $request) }}" class="btn btn-sm btn-primary">مراجعة وتوقيع</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($prAwaitingPm2Sign > $awaitingMyPm2Requests->count())
            <div class="p-2 small text-muted border-top">يُعرض أقدم {{ $awaitingMyPm2Requests->count() }} من {{ $prAwaitingPm2Sign }} طلبًا.</div>
        @endif
    </div>
    @endif

    @if ($generalReviewCount > 0)
    {{-- قوائم مراجعة عامة (ليست بالضرورة موجّهة إليك)؛ الإجراء نفسه يتحقق منه النظام في صفحة السجل حسب صلاحياتك --}}
    <h3 class="h6 text-muted fw-bold mb-2">قوائم قيد المراجعة (عامة)</h3>
    @endif
@if ($movementAwaitingReview->isNotEmpty())
<x-fold title="خطط حركة قيد المراجعة" icon="bi-hourglass-split" :count="count($movementAwaitingReview)" open>
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الخطة</th>
                    <th>الشهر</th>
                    <th>الحركات</th>
                    <th>المركز</th>
                    <th>أنشأها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movementAwaitingReview as $plan)
                    <tr>
                        <td><code>{{ $plan->request_number }}</code></td>
                        <td class="num">{{ $plan->plan_month?->format('Y-m') ?? '—' }}</td>
                        <td class="num">{{ $plan->entries_count }}</td>
                        <td>{{ $plan->center?->name ?? '—' }}</td>
                        <td>{{ $plan->creator?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.movement-plans.show', $plan) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> مراجعة</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endif
@if ($documentsUnderReview->isNotEmpty())
<x-fold title="وثائق قيد المراجعة" icon="bi-file-earmark-text" :count="count($documentsUnderReview)" open>
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الوثيقة</th>
                    <th>القالب</th>
                    <th>المشروع</th>
                    <th>أنشأها</th>
                    <th>آخر تحديث</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documentsUnderReview as $doc)
                    <tr>
                        <td>{{ $doc->title ?: $doc->template?->title_ar }}</td>
                        <td>{{ $doc->template?->title_ar ?? '—' }}</td>
                        <td>{{ $doc->project?->name ?? '—' }}</td>
                        <td>{{ $doc->creator?->name ?? '—' }}</td>
                        <td>{{ $doc->updated_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.project-docs.documents.show', $doc) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> مراجعة</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endif
</section>
@endif

@include('admin.partials._shortcuts_projects_manager')


{{-- ملخص عام --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">ملخص عام</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="المشاريع" :value="$projectsCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="المراكز" :value="$centersCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="الطلاب" :value="$studentsCount" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="مدراء المشاريع" :value="$projectManagersCount" />
    </div>
</div>

{{-- المهام --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">المهام</h2>
    <a href="{{ route('admin.projects.tasks.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="إجمالي المهام" :value="$tasksTotal" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="قيد الانتظار" :value="$tasksByStatus['pending'] ?? 0" tone="warning" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="متأخرة" :value="$tasksDelayed" tone="danger" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="منجزة" :value="$tasksByStatus['completed'] ?? 0" tone="success" />
    </div>
</div>

@if ($upcomingTasks->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-calendar3 me-1"></i> مهام قادمة (التقويم الزمني)</h5>
        <a href="{{ route('admin.projects.calendar') }}" class="btn btn-sm btn-outline-warning">التقويم الكامل</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>المهمة</th>
                    <th>المسؤول</th>
                    <th>المركز</th>
                    <th>البداية</th>
                    <th>النهاية</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($upcomingTasks as $task)
                    <tr>
                        <td>{{ Str::limit($task->title, 40) }}</td>
                        <td>{{ $task->assignedTo?->name ?? '—' }}</td>
                        <td>{{ $task->center?->name ?? '—' }}</td>
                        <td>{{ $task->start_date->format('Y-m-d') }}</td>
                        <td>{{ $task->end_date->format('Y-m-d') }}</td>
                        <td><span class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'delayed' ? 'danger' : ($task->status === 'in_progress' ? 'primary' : 'secondary')) }}">{{ $task->status }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- خطط الحركة --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">خطط الحركة</h2>
    <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    @php $movementColor = ['review' => 'warning', 'approved' => 'info', 'assigned' => 'primary', 'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary']; @endphp
    @foreach (\App\Models\Admin\MovementPlan::STATUSES as $key => $label)
        @if (($movementStatusCounts[$key] ?? 0) > 0)
            <div class="col-md-2 col-6">
                <x-kpi :label="$label" :value="$movementStatusCounts[$key]" :tone="['warning' => 'warning', 'info' => 'info', 'primary' => 'brand', 'success' => 'success', 'danger' => 'danger'][$movementColor[$key] ?? ''] ?? null" />
            </div>
        @endif
    @endforeach
    <div class="col-md-2 col-6">
        <x-kpi label="الإجمالي" :value="$movementTotal" />
    </div>
</div>


{{-- الخطط الإعلامية --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">الخطط الإعلامية</h2>
    <a href="{{ route('admin.media-plans.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <x-kpi label="إجمالي الخطط" :value="$mediaPlansTotal" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="خطط هذا الشهر" :value="$mediaPlansThisMonth" tone="brand" />
    </div>
</div>

@if ($recentMediaPlans->isNotEmpty())
<x-fold title="أحدث الخطط الإعلامية" icon="bi-megaphone" :count="count($recentMediaPlans)">
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الشهر</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>عدد الفعاليات</th>
                    <th>أنشأها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentMediaPlans as $plan)
                    <tr>
                        <td>{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td>
                        <td>{{ $plan->center?->name ?? '—' }}</td>
                        <td>{{ $plan->project?->name ?? '—' }}</td>
                        <td><span class="badge {{ $plan->events_count > 0 ? 'bg-primary' : 'bg-secondary' }}">{{ $plan->events_count }}</span></td>
                        <td>{{ $plan->creator?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.media-plans.show', $plan) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endif

{{-- الوثائق --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">وثائق المشاريع</h2>
    <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-6">
        <x-kpi label="وثائق بانتظار الاعتماد" :value="$documentsUnderReviewCount" tone="warning" />
    </div>
</div>


@if ($recentDocChanges->isNotEmpty())
<x-fold title="آخر تغييرات الوثائق التي عملها مدراء المشاريع" icon="bi-arrow-repeat" :count="count($recentDocChanges)">
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الوثيقة</th>
                    <th>الإجراء</th>
                    <th>بواسطة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentDocChanges as $action)
                    @php
                        $doc = $action->workable;
                        $labels = ['update' => 'تحديث', 'submit' => 'إرسال للمراجعة'];
                    @endphp
                    <tr>
                        <td>{{ $doc?->title ?: ($doc?->template?->title_ar ?? 'وثيقة') }}</td>
                        <td><span class="badge bg-{{ $action->action === 'submit' ? 'primary' : 'secondary' }}">{{ $labels[$action->action] ?? $action->action }}</span></td>
                        <td>{{ $action->fromUser?->name ?? '—' }}</td>
                        <td>{{ $action->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endif

{{-- طلبات الشراء --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">طلبات الشراء</h2>
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <x-kpi label="إجمالي الطلبات" :value="$prTotal" />
    </div>
    <div class="col-md-3 col-6">
        <x-kpi label="بموجودي للتوقيع" :value="$prAwaitingPm2Sign" tone="brand" />
    </div>
</div>

@if ($recentPurchaseRequests->isNotEmpty())
<x-fold title="آخر طلبات الشراء" icon="bi-clock-history" :count="count($recentPurchaseRequests)">
    <div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الطلب</th>
                    <th>البنود</th>
                    <th>السعر المتوقع</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentPurchaseRequests as $request)
                    <tr>
                        <td><code>#{{ $request->id }}</code></td>
                        <td>{{ $request->items->take(2)->pluck('description')->join('، ') ?: ($request->specifications ? Str::limit($request->specifications, 50) : '—') }}</td>
                        <td>{{ number_format($request->total_price, 2) }}</td>
                        <td>{{ $request->center?->name ?? '—' }}</td>
                        <td>{{ $request->project?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusLabel = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$request->status] ?? $request->status;
                                $prColor = match ($request->status) {
                                    'review' => 'warning text-dark',
                                    'approved1', 'approved2' => 'info',
                                    'rejected' => 'danger',
                                    'executed' => 'dark',
                                    default => 'success',
                                };
                            @endphp
                            <span class="badge bg-{{ $prColor }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.logistics.purchase-requests.show', $request) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</x-fold>
@endif

{{-- فريق مدراء المشاريع --}}
@if ($projectManagers->isNotEmpty())
<div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="section-title mb-0">فريق مدراء المشاريع</h2>
</div>

<div class="table-container mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الاسم</th>
                    <th>المشروع</th>
                    <th>المركز</th>
                    <th>الرقم الوظيفي</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projectManagers as $manager)
                    <tr>
                        <td>{{ $manager->first_name_ar }} {{ $manager->last_name_ar }}</td>
                        <td>{{ $manager->project?->name ?? '—' }}</td>
                        <td>{{ $manager->center?->name ?? '—' }}</td>
                        <td><code>{{ $manager->employee_code }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection