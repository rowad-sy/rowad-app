@extends('admin.layouts.master')

@section('title', 'الخطة الإعلامية - ' . $plan->month_date->locale('ar')->translatedFormat('F Y'))

@section('content')
@php
    $conflicts = $plan->conflicts();
@endphp

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الخطة الإعلامية — {{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</h4>
        <p>
            <a href="{{ route('admin.media-plans.index') }}" class="text-decoration-none">الخطة الإعلامية</a>
            / {{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <x-audit-history :model="'App\Models\Admin\MediaPlan'" :model-id="$plan->id" />
        @canPermission('App\Models\Admin\MediaPlan', 'edit')
        <a href="{{ route('admin.media-plans.edit', $plan) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
        @endcanPermission
        @canPermission('App\Models\Admin\MediaPlan', 'delete')
        <form method="POST" action="{{ route('admin.media-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الخطة؟')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
        </form>
        @endcanPermission
        <a href="{{ route('admin.media-plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
    </div>
</div>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">بيانات الخطة</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0 small">
                    <tr><th style="width:180px">الشهر</th><td>{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td></tr>
                    <tr><th>المركز</th><td>{{ $plan->center?->name ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $plan->project?->name ?? '—' }}</td></tr>
                    <tr><th>أنشأها</th><td>{{ $plan->creator?->name ?? '—' }}</td></tr>
                    <tr><th>ملاحظات</th><td>{{ $plan->note ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        @if (! empty($conflicts))
        <div class="alert alert-danger mt-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>تعارض في المواعيد:</strong> توجد فعاليات بنفس التاريخ والساعة. راجع الصفوف المظللة بالأحمر.
        </div>
        @endif

        <div class="table-container mt-3">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">فعاليات الخطة ({{ $plan->events->count() }})</h5>
                @canPermission('App\Models\Admin\MediaPlan', 'edit')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addEventForm">
                    <i class="bi bi-plus-lg me-1"></i> إضافة فعالية
                </button>
                @endcanPermission
            </div>

            @canPermission('App\Models\Admin\MediaPlan', 'edit')
            <div class="collapse p-3 border-bottom" id="addEventForm">
                <form method="POST" action="{{ route('admin.media-plans.events.store', $plan) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">المكتب</label>
                            <input type="text" name="office" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">اسم الفعالية <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">الساعة <span class="text-danger">*</span></label>
                            <input type="time" name="event_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">اليوم</label>
                            <input type="text" name="day" class="form-control form-control-sm" placeholder="الإثنين">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">موقع الفعالية</label>
                            <input type="text" name="location" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">المسؤول عن الفعالية</label>
                            <select name="responsible_user_id" class="form-select form-select-sm">
                                <option value="">— اختر —</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">نوع التغطية</label>
                            <input type="text" name="coverage_type" class="form-control form-control-sm" placeholder="تصوير/تقرير/بث">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">ملخص الفعالية</label>
                            <input type="text" name="summary" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">ملاحظات</label>
                            <input type="text" name="notes" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <button class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i> إضافة</button>
                        </div>
                    </div>
                </form>
            </div>
            @endcanPermission

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>التاريخ</th>
                            <th>اليوم</th>
                            <th>الساعة</th>
                            <th>المكتب</th>
                            <th>اسم الفعالية</th>
                            <th>موقع الفعالية</th>
                            <th>المسؤول</th>
                            <th>التغطية</th>
                            <th>ملخص</th>
                            <th>ملاحظات</th>
                            <th>تعليقات الإعلامي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->events as $event)
                            @php
                                $conflictKey = $event->event_date->format('Y-m-d') . '|' . $event->event_time;
                                $isConflict = isset($conflicts[$conflictKey]);
                            @endphp
                            <tr class="{{ $isConflict ? 'table-danger' : '' }}">
                                <td>{{ $event->event_date->format('Y-m-d') }}</td>
                                <td>{{ $event->day ?? '—' }}</td>
                                <td>{{ substr((string) $event->event_time, 0, 5) }}</td>
                                <td>{{ $event->office ?? '—' }}</td>
                                <td class="fw-medium">{{ $event->event_name }}</td>
                                <td>{{ $event->location ?? '—' }}</td>
                                <td>{{ $event->responsible?->name ?? '—' }}</td>
                                <td>{{ $event->coverage_type ?? '—' }}</td>
                                <td>{{ $event->summary ?? '—' }}</td>
                                <td>{{ $event->notes ?? '—' }}</td>
                                <td>
                                    @if ($event->comments->count())
                                        <ul class="list-unstyled small mb-1">
                                            @foreach ($event->comments as $comment)
                                                <li class="mb-1">
                                                    <span class="text-muted">{{ $comment->user?->name ?? '—' }}:</span>
                                                    {{ $comment->comment }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif

                                    @canPermission('App\Models\Admin\MediaPlan', 'edit')
                                    <form method="POST" action="{{ route('admin.media-plans.events.comment', $event) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="comment" class="form-control form-control-sm" placeholder="رأي الإعلامي بالموعد..." required>
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots"></i></button>
                                    </form>
                                    @endcanPermission
                                </td>
                                <td>
                                    @canPermission('App\Models\Admin\MediaPlan', 'edit')
                                    <form method="POST" action="{{ route('admin.media-plans.events.destroy', $event) }}" onsubmit="return confirm('حذف هذه الفعالية؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    @endcanPermission
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>لا توجد فعاليات في هذه الخطة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">ملاحظة</h5></div>
            <div class="p-3 small text-muted">
                <p><i class="bi bi-info-circle me-1"></i> لا يجوز أن تتزامن أكثر من فعالية في نفس التاريخ والساعة حتى لا يتعارض عمل الإعلامي.</p>
                <p><i class="bi bi-chat-dots me-1"></i> يمكن للمسؤول الإعلامي إبداء رأيه على أي موعد من خلال خانة التعليق.</p>
            </div>
        </div>
    </div>

</div>
@endsection