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
    $isRowaduna = $uid === (int) $plan->refer_to_rowaduna_id || $plan->activeReferrals()->where('step', 'rowaduna')->where('to_user_id', $uid)->exists();
    $canReschedule = $isCreator || $isDirector || $isRowaduna;

    $statusColors = [
        'review' => 'warning text-dark', 'pm2_review' => 'warning text-dark',
        'manager_approved' => 'info', 'pm2_approved' => 'info',
        'rowaduna_review' => 'primary', 'in_progress' => 'primary',
        'executed' => 'success', 'rejected' => 'danger',
    ];
    $statusLabel = \App\Models\Admin\MediaPlan::STATUSES[$plan->status] ?? $plan->status;
    $locked = $plan->isLocked();

    $themeQuery = request('theme') === 'rowaduna' ? ['theme' => 'rowaduna'] : [];
    $cover = \App\Models\Admin\MediaPlanEvent::COVERAGE_STATUSES;
    $pub = \App\Models\Admin\MediaPlanEvent::PUBLISH_STATUSES;
    $platforms = \App\Models\Admin\MediaPlanEvent::PLATFORMS;
    $reviewerDefault = $plan->refer_to_direct_manager_id ?? $plan->created_by;
@endphp

@php $tone = (function ($c) { foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info', 'primary' => 'brand'] as $k => $t) { if (str_contains((string) $c, $k)) return $t; } return 'neutral'; })($statusColors[$plan->status] ?? ''); @endphp
<x-page-header :title="'الخطة الإعلامية — ' . $plan->month_date->locale('ar')->translatedFormat('F Y')"
               :breadcrumb="[['label' => 'الخطة الإعلامية', 'url' => route('admin.media-plans.index')], ['label' => $plan->month_date->locale('ar')->translatedFormat('F Y')]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone">{{ $statusLabel }}</x-status-badge></div></x-slot:meta>
    <x-audit-history :model="'App\Models\Admin\MediaPlan'" :model-id="$plan->id" />
    <a href="{{ route('admin.media-plans.print', array_merge([$plan], $themeQuery)) }}" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer me-1"></i> طباعة / PDF</a>
    <a href="{{ route('admin.media-plans.export', array_merge([$plan], $themeQuery)) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i> تصدير Excel</a>
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
    <i class="bi bi-lock-fill me-1"></i> الخطة <strong>مقفلة</strong> بعد أول موافقة — لا تُعدَّل ولا تُحذف.
    <span class="text-muted">(أغلقها {{ $plan->lockedByUser?->name ?? '—' }} بتاريخ {{ $plan->locked_at->format('Y-m-d H:i') }})</span>
</div>
@endif

@if ($plan->status === 'rejected' && $plan->reason)
<div class="alert alert-danger py-2 small">
    <i class="bi bi-x-circle me-1"></i> <strong>سبب الرفض:</strong> {{ $plan->reason }}
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
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
                    <tr><th>عهدة الدورة</th>
                        <td>
                            المدير المباشر: {{ $plan->directManager?->name ?? (in_array($plan->status, ['pm2_review'], true) ? 'تُخطّا (المنشئ هو مدير المشروع)' : '—') }} ·
                            مدير المشاريع: {{ $plan->pm2User?->name ?? '—' }} ·
                            <span class="text-danger fw-bold">مسؤول روادنا: {{ $plan->rowadunaUser?->name ?? '—' }}</span>
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
                <h5 class="mb-0">فعاليات الخطة وخط أنابيب التغطية ({{ $plan->events->count() }})</h5>
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
                            <th>الفعالية</th>
                            <th>الموقع/الساعة</th>
                            <th>التغطية</th>
                            <th>خط الأنابيب</th>
                            <th>المراسل</th>
                            <th>حالة النشر</th>
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
                                $isEventReporter = $uid === (int) $event->refer_to_reporter_id;
                                $isEventPublisher = $uid === (int) $event->refer_to_publisher_id;
                                $isEventReviewer = $uid === (int) $event->refer_to_reviewer_id;
                            @endphp
                            <tr id="ev{{ $event->id }}" class="{{ $isConflict ? 'table-danger' : '' }}">
                                <td class="small">{{ $event->event_date->format('Y-m-d') }}<br><span class="text-muted">{{ $event->day ?? '' }} {{ substr((string) $event->event_time, 0, 5) }}</span></td>
                                <td class="fw-medium small">
                                    {{ $event->event_name }}
                                    @if ($event->coverage_type)<div class="text-muted fw-normal">{{ $event->coverage_type }}</div>@endif
                                    @if ($event->summary)<div class="text-muted fw-normal">{{ Str::limit($event->summary, 60) }}</div>@endif
                                </td>
                                <td class="small">{{ $event->location ?? '—' }}</td>
                                <td class="small" style="min-width:170px">
                                    <span class="badge bg-{{ $event->coverage_status === 'covered' ? 'success' : ($event->coverage_status === 'not_covered' ? 'danger' : ($event->coverage_status === 'assigned' ? 'warning text-dark' : 'secondary')) }} d-block mb-1">
                                        {{ $cover[$event->coverage_status] ?? $event->coverage_status }}
                                    </span>
                                    @if ($event->media_items_url)
                                        <a href="{{ $event->media_items_url }}" target="_blank" class="small"><i class="bi bi-folder2-open me-1"></i>مواد التغطية (درايف)</a>
                                    @endif
                                    @if ($event->not_covered_reason)
                                        <div class="small text-danger mt-1">السبب: {{ $event->not_covered_reason }}</div>
                                    @endif
                                    @if ($event->coverage_note)
                                        <div class="small text-muted">{{ $event->coverage_note }}</div>
                                    @endif

                                    {{-- (أ) مسؤول روادنا: إسناد مراسل --}}
                                    @if ($isRowaduna && in_array($plan->status, ['rowaduna_review', 'in_progress', 'pm2_approved'], true) && in_array($event->coverage_status, ['pending', 'assigned'], true))
                                    <form method="POST" action="{{ route('admin.media-plans.events.assign', $event) }}" class="mt-1">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <select name="reporter_user_id" class="form-select user-picker" required>
                                                <option value="">إسناد لمراسل…</option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}" @selected((int) $event->refer_to_reporter_id === (int) $u->id)>{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-primary"><i class="bi bi-person-check"></i></button>
                                        </div>
                                    </form>
                                    @endif

                                    {{-- (ب) المراسل: سجل التغطية --}}
                                    @if ($isEventReporter && $event->coverage_status === 'assigned')
                                    <form method="POST" action="{{ route('admin.media-plans.events.reporter-decide', $event) }}" class="mt-1 border-top pt-1">
                                        @csrf
                                        <div class="small fw-bold mb-1">تقرير المراسل:</div>
                                        <input type="url" name="media_items_url" class="form-control form-control-sm mb-1" placeholder="رابط المواد على جوجل درايف">
                                        <input type="text" name="coverage_note" class="form-control form-control-sm mb-1" placeholder="ملاحظة التغطية (اختياري)">
                                        <div class="input-group input-group-sm mb-1">
                                            <select name="publisher_user_id" class="form-select user-picker">
                                                <option value="">المونتير/الناشر…</option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}" @selected((int) ($event->refer_to_publisher_id ?? $tentativeRowadunaId ?? 0) === (int) $u->id)>{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-success btn-sm" name="decision" value="covered"><i class="bi bi-check-lg"></i> تمت التغطية</button>
                                        </div>
                                        <button class="btn btn-outline-danger btn-sm w-100" name="decision" value="not_covered"
                                                onclick="return confirm('تأكيد تسجيل عدم حدوث التغطية؟ يجب إدخال السبب في حقل السبب أولاً.')">
                                            <i class="bi bi-x-lg"></i> لم تتم التغطية
                                        </button>
                                        <input type="text" name="not_covered_reason" class="form-control form-control-sm mt-1" placeholder="سبب عدم التغطية (إلزامي عند الرفض)">
                                    </form>
                                    @endif

                                    {{-- (ج) إعادة جدولة فعالية لم تُغطَّ --}}
                                    @if ($event->coverage_status === 'not_covered' && $canReschedule && ! in_array($plan->status, ['executed', 'rejected'], true))
                                    <form method="POST" action="{{ route('admin.media-plans.events.reschedule', $event) }}" class="mt-1 border-top pt-1">
                                        @csrf
                                        <div class="small fw-bold mb-1"><i class="bi bi-arrow-repeat me-1"></i>إعادة جدولة:</div>
                                        <div class="d-flex gap-1 mb-1">
                                            <input type="date" name="event_date" class="form-control form-control-sm" value="{{ $event->event_date->format('Y-m-d') }}" required>
                                            <input type="time" name="event_time" class="form-control form-control-sm" value="{{ substr((string) $event->event_time, 0, 5) }}" required>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <select name="reporter_user_id" class="form-select user-picker" required>
                                                <option value="">مراسل الموعد الجديد…</option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}" @selected((int) $event->refer_to_reporter_id === (int) $u->id)>{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-warning"><i class="bi bi-send"></i></button>
                                        </div>
                                    </form>
                                    @endif

                                    {{-- تعليقات الفريق --}}
                                    @if ($event->comments->count())
                                        <ul class="list-unstyled small mb-1 mt-1">
                                            @foreach ($event->comments as $comment)
                                                <li class="mb-1"><span class="text-muted">{{ $comment->user?->name ?? '—' }}:</span> {{ $comment->comment }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @canPermission('App\Models\Admin\MediaPlan', 'edit')
                                    <form method="POST" action="{{ route('admin.media-plans.events.comment', $event) }}" class="d-flex gap-1 mt-1">
                                        @csrf
                                        <input type="text" name="comment" class="form-control form-control-sm" placeholder="تعليق…">
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots"></i></button>
                                    </form>
                                    @endcanPermission
                                </td>
                                <td class="small" style="min-width:120px">
                                    @if ($event->coverage_status === 'covered')
                                        <div>{{ $event->reporter?->name ?? '—' }}</div>
                                    @else
                                        <span class="text-muted">{{ $event->reporter?->name ?? 'لم يُسند بعد' }}</span>
                                    @endif
                                </td>
                                <td class="small" style="min-width:190px">
                                    <span class="badge bg-{{ $event->publish_status === 'published' ? 'success' : ($event->publish_status === 'none' ? 'secondary' : 'info text-dark') }} d-block mb-1">
                                        {{ $pub[$event->publish_status] ?? $event->publish_status }}
                                    </span>
                                    @if ($event->preview_url)
                                        <a href="{{ $event->preview_url }}" target="_blank" class="small d-block mb-1"><i class="bi bi-play-btn me-1"></i>معاينة (مؤقت)</a>
                                    @endif
                                    @if ($event->preview_feedback)
                                        <div class="small text-warning mb-1"><i class="bi bi-pencil-square me-1"></i>{{ $event->preview_feedback }}</div>
                                    @endif
                                    @foreach ($event->publish_links ?? [] as $link)
                                        <a href="{{ $link['url'] }}" target="_blank" class="small d-block text-success"><i class="bi bi-share me-1"></i>{{ $platforms[$link['platform']] ?? $link['platform'] }}</a>
                                    @endforeach

                                    {{-- (د) الناشر: نشر مؤقت --}}
                                    @if ($isEventPublisher && in_array($event->publish_status, ['to_publish', 'rework'], true))
                                    <form method="POST" action="{{ route('admin.media-plans.events.publisher-preview', $event) }}" class="mt-1 border-top pt-1">
                                        @csrf
                                        <div class="small fw-bold mb-1"><i class="bi bi-cloud-upload me-1"></i>نشر مؤقت للمعاينة:</div>
                                        <input type="url" name="preview_url" class="form-control form-control-sm mb-1" placeholder="رابط النشر المؤقت (فيديو/خبر)" required>
                                        <div class="input-group input-group-sm">
                                            <select name="refer_to_reviewer_id" class="form-select user-picker" required>
                                                <option value="">مراجعة مدير المشروع…</option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}" @selected((int) ($event->refer_to_reviewer_id ?? $reviewerDefault) === (int) $u->id)>{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-primary"><i class="bi bi-send"></i></button>
                                        </div>
                                    </form>
                                    @endif

                                    {{-- (هـ) مدير المشروع: مراجعة المعاينة --}}
                                    @if ($isEventReviewer && $event->publish_status === 'to_review')
                                    <form method="POST" action="{{ route('admin.media-plans.events.reviewer-decide', $event) }}" class="mt-1 border-top pt-1">
                                        @csrf
                                        <div class="small fw-bold mb-1">قرار المراجعة:</div>
                                        <input type="text" name="preview_feedback" class="form-control form-control-sm mb-1" placeholder="ملاحظات الإرجاع (إلزامية عند الإعادة)">
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-success flex-fill" name="decision" value="approve"><i class="bi bi-check-lg"></i> سليم — انشر نهائياً</button>
                                            <button class="btn btn-sm btn-outline-danger flex-fill" name="decision" value="return"><i class="bi bi-arrow-return-left"></i> إعادة</button>
                                        </div>
                                    </form>
                                    @endif

                                    {{-- (و) الناشر: روابط النشر الدائم --}}
                                    @if ($isEventPublisher && $event->publish_status === 'to_final')
                                    <form method="POST" action="{{ route('admin.media-plans.events.publish-final', $event) }}" class="mt-1 border-top pt-1">
                                        @csrf
                                        <div class="small fw-bold mb-1"><i class="bi bi-broadcast me-1"></i>روابط النشر الدائم:</div>
                                        @foreach (['facebook', 'instagram', 'youtube'] as $pl)
                                            <div class="input-group input-group-sm mb-1">
                                                <span class="input-group-text">{{ $platforms[$pl] }}</span>
                                                <input type="hidden" name="platforms[{{ $loop->index }}][platform]" value="{{ $pl }}">
                                                <input type="url" name="platforms[{{ $loop->index }}][url]" class="form-control form-control-sm" placeholder="https://… (اتركه فارغاً لتجاهله)">
                                            </div>
                                        @endforeach
                                        <div class="input-group input-group-sm mb-1">
                                            <span class="input-group-text">أخرى</span>
                                            <input type="hidden" name="platforms[3][platform]" value="other">
                                            <input type="url" name="platforms[3][url]" class="form-control form-control-sm" placeholder="رابط إضافي">
                                        </div>
                                        <button class="btn btn-sm btn-success w-100"><i class="bi bi-check2-circle me-1"></i> تثبيت النشر الدائم</button>
                                    </form>
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
                            <x-empty-row colspan="7" icon="bi-inbox" title="لا توجد فعاليات في هذه الخطة" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if ($plan->status === 'review' && $isDirector)
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
                    <select name="refer_to_pm2_id" class="form-select form-select-sm mb-2 user-picker" required>
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

        @if (in_array($plan->status, ['pm2_review', 'manager_approved'], true) && $isPm2)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار مدير المشاريع</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.pm2-decide', $plan) }}">
                    @csrf
                    <label class="form-label small fw-bold">القرار</label>
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">موافقة وتحويل لقسم روادنا</option>
                        <option value="reject">رفض الخطة</option>
                    </select>
                    <label class="form-label small fw-bold">مسؤول روادنا</label>
                    <select name="refer_to_rowaduna_id" class="form-select form-select-sm mb-2 user-picker" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('refer_to_rowaduna_id', (int) ($plan->refer_to_rowaduna_id ?? $tentativeRowadunaId ?? 0)) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if ($isRowaduna && in_array($plan->status, ['rowaduna_review', 'in_progress'], true))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">متابعة روادنا</h5></div>
            <div class="p-3 small text-muted">
                <p><i class="bi bi-info-circle me-1"></i> أسند كل فعالية إلى <strong>مراسل</strong> من عمود «التغطية»، وتابع خط الأنابيب حتى النشر الدائم.</p>
                <form method="POST" action="{{ route('admin.media-plans.finalize', $plan) }}" onsubmit="return confirm('تأكيد إغلاق الخطة كمنجزة؟')">
                    @csrf
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة الإغلاق (اختياري)">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" name="force" id="forceClose">
                        <label class="form-check-label" for="forceClose">إغلاق إجباري رغم وجود فعاليات غير مكتملة</label>
                    </div>
                    <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i> إغلاق الخطة كمنجزة</button>
                </form>
            </div>
        </div>
        @endif

        @if (! in_array($plan->status, ['executed', 'rejected'], true))
        @php
            $planStep = $plan->stepForStatus();
            $isCurrentHolder = $planStep !== null
                && ($plan->isCurrentRecipient($uid) || (int) $plan->activeReferrals()->where('step', $planStep)->where('to_user_id', $uid)->exists()
                    || (int) $plan->{'refer_to_' . ($planStep === 'direct_manager' ? 'direct_manager' : ($planStep === 'pm2' ? 'pm2' : 'rowaduna')) . '_id'} === $uid);
        @endphp
        @if ($isCurrentHolder)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إعادة إحالة</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.media-plans.refer', $plan) }}">
                    @csrf
                    <input type="hidden" name="step" value="{{ $planStep }}">
                    <label class="form-label small fw-bold">إحالة إلى</label>
                    <select name="to_user_id" class="form-select form-select-sm mb-2 user-picker" required>
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
        @endif

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">مسار الدورة</h5></div>
            <div class="p-3 small text-muted">
                <p>1) الإنشاء (مسؤول/مدير المشروع) ← المدير المباشر <em>أو مدير المشاريع مباشرة إذا أنشأها مدير المشروع</em>.</p>
                <p>2) المدير المباشر يوافق — <strong>تُقفل الخطة</strong>.</p>
                <p>3) مدير المشاريع يوافق ويحوّل لـ <strong>روادنا</strong>.</p>
                <p>4) مسؤول روادنا يُسند كل فعالية لمراسل (بلا تعارض مواعيد).</p>
                <p>5) المراسل: تمت التغطية + رابط درايف للمواد، أو لم تتم + السبب (قابلة لإعادة الجدولة).</p>
                <p>6) المونتير/الناشر: نشر مؤقت برابط معاينة ← مدير المشروع يراجع.</p>
                <p>7) الناشر: روابط النشر الدائم (فيسبوك/إنستغرام/يوتيوب/…) ← تُغلق الخطة.</p>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">سجل الدورة</h5></div>
            <div class="p-3">
                @forelse ($plan->workflowActions()->with(['fromUser', 'toUser'])->latest()->limit(30)->get()->reverse() as $action)
                    <div class="small border-bottom pb-1 mb-2">
                        <span class="text-muted">{{ $action->created_at?->format('Y-m-d H:i') }}</span>
                        <strong>{{ $action->fromUser?->name ?? '—' }}</strong>
                        {{ \App\Models\WorkflowAction::ACTIONS[$action->action] ?? $action->action }}
                        @if ($action->toUser) ← <span class="text-primary">{{ $action->toUser->name }}</span>@endif
                        @if ($action->note)<div class="text-muted">{{ $action->note }}</div>@endif
                    </div>
                @empty
                    <div class="small text-muted">لا سجلات بعد.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
