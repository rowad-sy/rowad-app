@extends('admin.layouts.master')

@section('title', 'لوحة مدير المشاريع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>لوحة مدير المشاريع</h4>
        <p>
            نظرة شاملة على جميع المشاريع ومدراء المشاريع
            <span class="badge bg-light text-dark border me-1">
                <i class="bi bi-globe2"></i> كل المشاريع والمراكز
            </span>
        </p>
    </div>
    <a href="{{ route('admin.projects.calendar') }}" class="btn btn-outline-primary">
        <i class="bi bi-calendar3 me-1"></i> التقويم الزمني
    </a>
</div>

@include('admin.partials._shortcuts_projects_manager')

{{-- ملخص عام --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-primary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">ملخص عام</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-primary"><i class="bi bi-diagram-3"></i></div>
            <div class="fs-4 fw-bold">{{ $projectsCount }}</div>
            <small class="text-muted">المشاريع</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-info"><i class="bi bi-building"></i></div>
            <div class="fs-4 fw-bold">{{ $centersCount }}</div>
            <small class="text-muted">المراكز</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-success"><i class="bi bi-mortarboard"></i></div>
            <div class="fs-4 fw-bold">{{ $studentsCount }}</div>
            <small class="text-muted">الطلاب</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-3 fw-bold text-warning"><i class="bi bi-people"></i></div>
            <div class="fs-4 fw-bold">{{ $projectManagersCount }}</div>
            <small class="text-muted">مدراء المشاريع</small>
        </div>
    </div>
</div>

{{-- المهام --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-info" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">المهام</h5>
    <a href="{{ route('admin.projects.tasks.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-dark">{{ $tasksTotal }}</div>
            <small class="text-muted">إجمالي المهام</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $tasksByStatus['pending'] ?? 0 }}</div>
            <small class="text-muted">قيد الانتظار</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-danger">{{ $tasksDelayed }}</div>
            <small class="text-muted">متأخرة</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ $tasksByStatus['completed'] ?? 0 }}</div>
            <small class="text-muted">منجزة</small>
        </div>
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
    <div class="bg-primary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">خطط الحركة</h5>
    <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-sm btn-outline-primary ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-2">
    @php $movementColor = ['review' => 'warning', 'approved' => 'info', 'assigned' => 'primary', 'completed' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary']; @endphp
    @foreach (\App\Models\Admin\MovementPlan::STATUSES as $key => $label)
        @if (($movementStatusCounts[$key] ?? 0) > 0)
            <div class="col-md-2 col-6">
                <div class="table-container text-center p-3">
                    <div class="fs-5 fw-bold text-{{ $movementColor[$key] ?? 'secondary' }}">{{ $movementStatusCounts[$key] }}</div>
                    <small class="text-muted">{{ Str::limit($label, 22) }}</small>
                </div>
            </div>
        @endif
    @endforeach
    <div class="col-md-2 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-5 fw-bold text-dark">{{ $movementTotal }}</div>
            <small class="text-muted">الإجمالي</small>
        </div>
    </div>
</div>

@if ($movementAwaitingReview->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-hourglass-split me-1 text-warning"></i> خطط حركة بانتظار مراجعة إدارة المشاريع</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الحركة</th>
                    <th>التاريخ</th>
                    <th>المسار</th>
                    <th>الغاية</th>
                    <th>المركز</th>
                    <th>أنشأها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movementAwaitingReview as $plan)
                    <tr>
                        <td><code>{{ $plan->request_number }}</code></td>
                        <td>{{ $plan->movement_date->format('Y-m-d') }}</td>
                        <td><small>{{ $plan->from_location ?: '—' }} ← {{ $plan->to_location ?: '—' }}</small></td>
                        <td>{{ Str::limit($plan->purpose, 40) }}</td>
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
@endif

{{-- الخطط الإعلامية --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-info" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">الخطط الإعلامية</h5>
    <a href="{{ route('admin.media-plans.index') }}" class="btn btn-sm btn-outline-info ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-dark">{{ $mediaPlansTotal }}</div>
            <small class="text-muted">إجمالي الخطط</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $mediaPlansThisMonth }}</div>
            <small class="text-muted">خطط هذا الشهر</small>
        </div>
    </div>
</div>

@if ($recentMediaPlans->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-megaphone me-1"></i> أحدث الخطط الإعلامية</h5>
    </div>
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
@endif

{{-- الوثائق --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-warning" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">وثائق المشاريع</h5>
    <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-sm btn-outline-warning ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-2">
    <div class="col-md-4 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $documentsUnderReviewCount }}</div>
            <small class="text-muted">وثائق بانتظار الاعتماد</small>
        </div>
    </div>
</div>

@if ($documentsUnderReview->isNotEmpty())
<div class="table-container mb-3">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-1"></i> وثائق قيد المراجعة</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الوثيقة</th>
                    <th>القالب</th>
                    <th>المشروع</th>
                    <th>المركز</th>
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
                        <td>{{ $doc->center?->name ?? '—' }}</td>
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
@endif

@if ($recentDocChanges->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i> آخر تغييرات الوثائق التي عملها مدراء المشاريع</h5>
    </div>
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
@endif

{{-- طلبات الشراء --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-success" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">طلبات الشراء</h5>
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-sm btn-outline-success ms-auto">عرض الكل</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-dark">{{ $prTotal }}</div>
            <small class="text-muted">إجمالي الطلبات</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="table-container text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $prAwaitingPm2Sign }}</div>
            <small class="text-muted">بموجودي للتوقيع</small>
        </div>
    </div>
</div>

@if ($recentPurchaseRequests->isNotEmpty())
<div class="table-container mb-4">
    <div class="p-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> آخر طلبات الشراء</h5>
    </div>
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
                                    'pending' => 'warning text-dark',
                                    'priced' => 'info',
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
@endif

{{-- فريق مدراء المشاريع --}}
@if ($projectManagers->isNotEmpty())
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-secondary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">فريق مدراء المشاريع</h5>
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