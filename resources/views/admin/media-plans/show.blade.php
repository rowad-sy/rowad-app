@extends('admin.layouts.master')

@section('title', 'الخطة الإعلامية - ' . $plan->month_date->locale('ar')->translatedFormat('F Y'))

@section('content')
@php
    $conflicts = $plan->conflicts();
    $user = auth()->user();
    $uid = $user->id;
    $isSuper = $user->type === 'super-admin';
    $isCreator = $uid === (int) $plan->created_by;
    $isDirector = $uid === (int) $plan->refer_to_direct_manager_id;
    $isPm2 = $uid === (int) $plan->refer_to_pm2_id;
    $isMediaManager = $uid === (int) $plan->refer_to_media_manager_id;
    $isOfficer = $uid === (int) $plan->refer_to_media_officer_id;
    $isCurrentHolder = $plan->isCurrentRecipient($uid);

    $statusColors = [
        'review' => 'warning text-dark', 'manager_approved' => 'info',
        'pm2_approved' => 'primary', 'media_manager_approved' => 'primary',
        'executing' => 'primary', 'executed' => 'success', 'rejected' => 'danger',
    ];
    $statusLabel = \App\Models\Admin\MediaPlan::STATUSES[$plan->status] ?? $plan->status;
    $locked = $plan->isLocked();
@endphp

@php $tone = (function ($c) { foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info', 'primary' => 'brand'] as $k => $t) { if (str_contains((string) $c, $k)) return $t; } return 'neutral'; })($statusColors[$plan->status] ?? ''); @endphp
<x-page-header :title="'الخطة الإعلامية — ' . $plan->month_date->locale('ar')->translatedFormat('F Y')"
               :breadcrumb="[['label' => 'الخطة الإعلامية', 'url' => route('admin.media-plans.index')], ['label' => $plan->month_date->locale('ar')->translatedFormat('F Y')]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone">{{ $statusLabel }}</x-status-badge></div></x-slot:meta>
    <x-audit-history :model="'App\Models\Admin\MediaPlan'" :model-id="$plan->id" />
    @if (! $locked)
        @canPermission('App\Models\Admin\MediaPlan', 'edit')
        <a href="{{ route('admin.media-plans.edit', $plan) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
        @endcanPermission
        @canPermission('App\Models\Admin\MediaPlan', 'delete')
        <form method="POST" action="{{ route('admin.media-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الخطة؟')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> حذف</button>
        </form>
        @endcanPermission
    @endif
    <a href="{{ route('admin.media-plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</x-page-header>

@if ($locked && $plan->locked_at)
<div class="alert alert-warning py-2 small">
    <i class="bi bi-lock-fill me-1"></i> الخطة <strong>مقفلة</strong> بعد موافقة المدير المباشر — لا تُعدَّل ولا تُحذف.
    <span class="text-muted">(أغلقها {{ $plan->lockedByUser?->name ?? '—' }} بتاريخ {{ $plan->locked_at->format('Y-m-d H:i') }})</span>
</div>
@endif

@if ($plan->status === 'rejected' && $plan->reason)
<div class="alert alert-danger py-2 small">
    <i class="bi bi-x-circle me-1"></i> <strong>سبب الرفض:</strong> {{ $plan->reason }}
</div>
@endif

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
                    <tr><th>عهدة الإحالة</th>
                        <td>
                            المدير المباشر: {{ $plan->directManager?->name ?? '—' }} ·
                            مدير المشاريع: {{ $plan->pm2User?->name ?? '—' }} ·
                            مدير الإعلام: {{ $plan->mediaManager?->name ?? '—' }} ·
                            المسؤول الإعلامي: {{ $plan->mediaOfficer?->name ?? '—' }}
                        </td>
                    </tr>
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
                @if (! $locked)
                @canPermission('App\Models\Admin\MediaPlan', 'edit')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addEventForm">
                    <i class="bi bi-plus-lg me-1"></i> إضافة فعالية
                </button>
                @endcanPermission
                @endif
            </div>

            @if (! $locked)
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
            @endif

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
                            <th>تعليقات الإعلامي</th>
                            <th>التنفيذ</th>
                            @if (! $locked)
                            <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->events as $event)
                            @php
                                $conflictKey = $event->event_date->format('Y-m-d') . '|' . $event->event_time;
                                $isConflict = isset($conflicts[$conflictKey]);
                                $exStatus = $event->execution_status;
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
                                <td style="min-width:150px">
                                    @if ($exStatus)
                                        <span class="badge bg-{{ $exStatus === 'executed' ? 'success' : 'danger' }} mb-1 d-block">
                                            {{ \App\Models\Admin\MediaPlan::EXECUTION_STATUSES[$exStatus] ?? $exStatus }}
                                        </span>
                                        <small class="text-muted d-block">{{ $event->executionUser?->name ?? '—' }}
                                            @if ($event->execution_at) — {{ $event->execution_at->format('Y-m-d') }}@endif
                                        </small>
                                        @if ($event->execution_note)
                                            <small class="text-muted d-block">{{ $event->execution_note }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted small">لا يوجد</span>
                                    @endif

                                    @if ($isSuper || $isOfficer)
                                        @if (! $plan->isLocked() || in_array($plan->status, ['media_manager_approved', 'executing'], true))
                                        <form method="POST" action="{{ route('admin.media-plans.events.mark', $event) }}" class="mt-1">
                                            @csrf
                                            <div class="d-flex gap-1">
                                                <select name="execution_status" class="form-select form-select-sm" required>
                                                    <option value="executed" @selected($exStatus === 'executed')>نُفِّذت</option>
                                                    <option value="not_executed" @selected($exStatus === 'not_executed')>لم تُنفَّذ</option>
                                                </select>
                                                <button class="btn btn-sm btn-outline-success" aria-label="موافقة" title="موافقة"><i class="bi bi-check-lg" aria-hidden="true"></i></button>
                                            </div>
                                            <input type="text" name="execution_note" class="form-control form-control-sm mt-1" placeholder="ملاحظة التنفيذ (اختياري)">
                                        </form>
                                        @endif
                                    @endif
                                </td>
                                @if (! $locked)
                                <td>
                                    @canPermission('App\Models\Admin\MediaPlan', 'edit')
                                    <form method="POST" action="{{ route('admin.media-plans.events.destroy', $event) }}" onsubmit="return confirm('حذف هذه الفعالية؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                    @endcanPermission
                                </td>
                                @endif
                            </tr>
                        @empty
                            <x-empty-row colspan="12" icon="bi-inbox" title="لا توجد فعاليات في هذه الخطة" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if ($plan->status === 'review' && ($isDirector || $isSuper))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار المدير المباشر</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.direct-manager-decide', $plan) }}">
                    @csrf
                    <label class="form-label small fw-bold">القرار</label>
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">موافقة وقفل الخطة</option>
                        <option value="reject">رفض الخطة</option>
                    </select>
                    <label class="form-label small fw-bold">إحالة إلى مدير المشاريع</label>
                    <select name="refer_to_pm2_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('refer_to_pm2_id', (int) ($plan->refer_to_pm2_id ?? $tentativePm2Id ?? 0)) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if ($plan->status === 'manager_approved' && ($isPm2 || $isSuper))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار مدير المشاريع</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.pm2-decide', $plan) }}">
                    @csrf
                    <label class="form-label small fw-bold">القرار</label>
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">موافقة مدير المشاريع</option>
                        <option value="reject">رفض الخطة</option>
                    </select>
                    <label class="form-label small fw-bold">إحالة إلى مدير الإعلام</label>
                    <select name="refer_to_media_manager_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('refer_to_media_manager_id', (int) ($plan->refer_to_media_manager_id ?? 0)) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if ($plan->status === 'pm2_approved' && ($isMediaManager || $isSuper))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار مدير الإعلام</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.media-manager-decide', $plan) }}">
                    @csrf
                    <label class="form-label small fw-bold">القرار</label>
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">موافقة مدير الإعلام</option>
                        <option value="reject">رفض الخطة</option>
                    </select>
                    <label class="form-label small fw-bold">المسؤول الإعلامي (مركز الخطة)</label>
                    <select name="refer_to_media_officer_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('refer_to_media_officer_id', (int) ($plan->refer_to_media_officer_id ?? $tentativeOfficerId ?? 0)) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if (in_array($plan->status, ['media_manager_approved', 'executing'], true) && ($isOfficer || $isSuper))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">تنفيذ الخطة (المسؤول الإعلامي)</h5></div>
            <div class="p-3 small text-muted">
                <p><i class="bi bi-info-circle me-1"></i> حدّد على كل فعالية <strong>نُفِّذت / لم تُنفَّذ</strong> + ملاحظات من عمود «التنفيذ»، ثم أغلق الخطة.</p>
                <form method="POST" action="{{ route('admin.media-plans.finalize', $plan) }}" onsubmit="return confirm('تأكيد إغلاق الخطة كمنجزة؟')">
                    @csrf
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة الإغلاق (اختياري)">
                    <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i> إغلاق الخطة كمنجزة</button>
                </form>
            </div>
        </div>
        @endif

        @if (! in_array($plan->status, ['executed', 'rejected'], true) && ($isCurrentHolder || $isSuper))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إعادة إحالة</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.refer', $plan) }}">
                    @csrf
                    <input type="hidden" name="step"
                           value="{{ $plan->status === 'review' ? 'direct_manager' : ($plan->status === 'manager_approved' ? 'pm2' : ($plan->status === 'pm2_approved' ? 'media_manager' : 'media_officer')) }}">
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
                <p>1) مسؤول المشروع ينشئ الخطة.</p>
                <p>2) المدير المباشر يوافق — هنا تُقفل الخطة.</p>
                <p>3) مدير المشاريع يوافق.</p>
                <p>4) مدير الإعلام يوافق ويحيلها للمسؤول الإعلامي في المركز.</p>
                <p>5) المسؤول الإعلامي يحدد نُفِّذت/لم تُنفَّذ لكل فعالية ثم يُغلق الخطة.</p>
            </div>
        </div>
    </div>

</div>
@endsection