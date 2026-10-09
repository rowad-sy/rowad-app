@extends('admin.layouts.master')

@section('title', 'طلب تصميم — ' . $ad->title)

@section('content')
@php
    $user = auth()->user();
    $uid = $user->id;
    $isPm2 = $uid === (int) $ad->refer_to_pm2_id;
    $isRowaduna = $uid === (int) $ad->refer_to_rowaduna_id;
    $isDesigner = $uid === (int) $ad->refer_to_designer_id;
    $isRequester = $uid === (int) $ad->created_by;
    $isPublisher = $uid === (int) $ad->refer_to_publisher_id;

    $tone = match ($ad->status) {
        'published' => 'success', 'rejected' => 'danger',
        'ready_for_review', 'to_publish' => 'brand',
        default => 'warning',
    };
    $platforms = \App\Models\Admin\MediaPlanEvent::PLATFORMS;
@endphp

@php $tone2 = ($tone === 'brand') ? 'brand' : $tone; @endphp
<x-page-header :title="'طلب تصميم — ' . $ad->title"
               :breadcrumb="[['label' => 'طلبات التصميم', 'url' => route('admin.ad-design-requests.index')], ['label' => '#'.$ad->id]]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone2">{{ $ad->statusLabel() }}</x-status-badge></div></x-slot:meta>
    <x-audit-history :model="'App\Models\Admin\AdDesignRequest'" :model-id="$ad->id" />
    @if (! $ad->isLocked())
        @canPermission('App\Models\Admin\AdDesignRequest', 'edit')
        <a href="{{ route('admin.ad-design-requests.edit', $ad) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
        @endcanPermission
    @endif
    <a href="{{ route('admin.ad-design-requests.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</x-page-header>

@if ($errors->any())
<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
@endif

@if ($ad->status === 'rejected' && $ad->reason)
<div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i> <strong>سبب الرفض:</strong> {{ $ad->reason }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">بيانات الطلب</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0 small">
                    <tr><th style="width:170px">العنوان</th><td>{{ $ad->title }}</td></tr>
                    <tr><th>المتطلبات</th><td>{{ $ad->description ?? '—' }}</td></tr>
                    <tr><th>المشروع</th><td>{{ $ad->project?->name ?? '—' }}</td></tr>
                    <tr><th>المراكز</th><td>
                        @forelse ($ad->centers as $c)
                            <x-status-badge tone="{{ $c->id === $ad->center_id ? 'brand' : 'neutral' }}">{{ $c->name }}</x-status-badge>
                        @empty
                            {{ $ad->center?->name ?? '—' }}
                        @endforelse
                    </td></tr>
                    <tr><th>المطلوب قبل</th><td>{{ optional($ad->due_date)->format('Y-m-d') ?? '—' }}</td></tr>
                    <tr><th>أنشأه</th><td>{{ $ad->creator?->name ?? '—' }}</td></tr>
                    <tr><th>عهدة الدورة</th>
                        <td>
                            مدير المشاريع: {{ $ad->pm2User?->name ?? '—' }} ·
                            مسؤول روادنا: {{ $ad->rowadunaUser?->name ?? '—' }} ·
                            المصمم: {{ $ad->designer?->name ?? '—' }} ·
                            الناشر: {{ $ad->publisher?->name ?? '—' }}
                        </td>
                    </tr>
                    @if ($ad->revision_note)
                    <tr><th>ملاحظات إعادة</th><td class="text-warning">{{ $ad->revision_note }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">التصميم والروابط</h5></div>
            <div class="p-3">
                @if ($ad->design_url)
                    <a href="{{ $ad->design_url }}" target="_blank" class="btn btn-outline-primary mb-2">
                        <i class="bi bi-folder2-open me-1"></i> فتح التصميم (جوجل درايف)
                    </a>
                @else
                    <div class="text-muted small mb-2">لم يرفع المصمم رابط التصميم بعد.</div>
                @endif
                @if ($ad->design_note)
                    <div class="small text-muted mb-2"><strong>ملاحظة المصمم:</strong> {{ $ad->design_note }}</div>
                @endif
                @if (! empty($ad->publish_links))
                    <table class="table table-sm small mb-0">
                        <thead><tr><th>المنصة</th><th>الرابط</th></tr></thead>
                        <tbody>
                            @foreach ($ad->publish_links as $link)
                                <tr>
                                    <td>{{ $platforms[$link['platform']] ?? $link['platform'] }}</td>
                                    <td><a href="{{ $link['url'] }}" target="_blank">{{ $link['url'] }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if ($ad->status === 'pm2_review' && $isPm2)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار مدير المشاريع</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.pm2-decide', $ad) }}">
                    @csrf
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">موافقة وتحويل لروادنا</option>
                        <option value="reject">رفض</option>
                    </select>
                    <label class="form-label small fw-bold">مسؤول روادنا</label>
                    <select name="refer_to_rowaduna_id" class="form-select form-select-sm mb-2 user-picker" required>
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('refer_to_rowaduna_id', (int) ($ad->refer_to_rowaduna_id ?? $tentativeRowadunaId ?? 0)) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if ($ad->status === 'rowaduna_review' && $isRowaduna)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إسناد المصمم (روادنا)</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.rowaduna-decide', $ad) }}">
                    @csrf
                    <select name="designer_user_id" class="form-select form-select-sm mb-2 user-picker" required>
                        <option value="">— اختر المصمم —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="توجيهات للمصمم (اختياري)">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-brush me-1"></i> إسناد التصميم</button>
                </form>
            </div>
        </div>
        @endif

        @if ($ad->status === 'designing' && $isDesigner)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">رفع التصميم</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.designer-submit', $ad) }}">
                    @csrf
                    <input type="url" name="design_url" class="form-control form-control-sm mb-2" placeholder="رابط التصميم على جوجل درايف" value="{{ old('design_url', $ad->design_url ?? '') }}" required>
                    <input type="text" name="design_note" class="form-control form-control-sm mb-2" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-cloud-upload me-1"></i> رفع وإحالة لصاحب الطلب</button>
                </form>
            </div>
        </div>
        @endif

        @if ($ad->status === 'ready_for_review' && $isRequester)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">قرار صاحب الطلب (مدير المشروع)</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.requester-decide', $ad) }}">
                    @csrf
                    <select name="decision" class="form-select form-select-sm mb-2" required>
                        <option value="approve">اعتماد التصميم ونشره</option>
                        <option value="return">إعادة للمصمم مع ملاحظات</option>
                        <option value="reject">رفض الطلب</option>
                    </select>
                    <label class="form-label small fw-bold">الناشر (بعد الاعتماد)</label>
                    <select name="publisher_user_id" class="form-select form-select-sm mb-2 user-picker">
                        <option value="">— اختر —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('publisher_user_id', (int) $tentativeRowadunaId ?? 0) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="ملاحظات الإعادة / الرفض">
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i> تنفيذ القرار</button>
                </form>
            </div>
        </div>
        @endif

        @if ($ad->status === 'to_publish' && $isPublisher)
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">روابط النشر النهائي</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.publish-final', $ad) }}">
                    @csrf
                    @foreach (['facebook', 'instagram', 'youtube'] as $pl)
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text">{{ $platforms[$pl] }}</span>
                            <input type="hidden" name="platforms[{{ $loop->index }}][platform]" value="{{ $pl }}">
                            <input type="url" name="platforms[{{ $loop->index }}][url]" class="form-control form-control-sm" placeholder="https://… (فارغ = تجاهل)">
                        </div>
                    @endforeach
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">أخرى</span>
                        <input type="hidden" name="platforms[3][platform]" value="other">
                        <input type="url" name="platforms[3][url]" class="form-control form-control-sm" placeholder="رابط إضافي">
                    </div>
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-broadcast me-1"></i> تثبيت النشر وإغلاق الطلب</button>
                </form>
            </div>
        </div>
        @endif

        @php $step = $ad->stepForStatus(); @endphp
        @if ($step && ! in_array($ad->status, ['published', 'rejected'], true) && $ad->isCurrentRecipient($uid))
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">إعادة إحالة الخطوة</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.ad-design-requests.refer', $ad) }}">
                    @csrf
                    <input type="hidden" name="step" value="{{ $step }}">
                    <select name="to_user_id" class="form-select form-select-sm mb-2 user-picker" required>
                        <option value="">— أحالة إلى —</option>
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
                <p>1) مدير المشروع ينشئ الطلب.</p>
                <p>2) مدير المشاريع يعتمد.</p>
                <p>3) مسؤول روادنا يُسند لمصمم.</p>
                <p>4) المصمم يرفع رابط التصميم (درايف).</p>
                <p>5) صاحب الطلب يعتمد أو يعيد مع ملاحظات.</p>
                <p>6) الناشر يدخل روابط النشر الدائم فيُغلق الطلب.</p>
            </div>
        </div>

        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">سجل الطلب</h5></div>
            <div class="p-3">
                @forelse ($ad->workflowActions()->with(['fromUser', 'toUser'])->latest()->limit(30)->get()->reverse() as $action)
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
