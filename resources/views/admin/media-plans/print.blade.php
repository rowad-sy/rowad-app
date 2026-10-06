<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الخطة الإعلامية — {{ $plan->month_date->locale('ar')->translatedFormat('F Y') }} ({{ $theme === 'rowaduna' ? 'روادنا' : 'مؤسسة الرواد' }})</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    @php
        $brand = $theme === 'rowaduna'
            ? ['accent' => '#FF4B3E', 'deep' => '#E03E32', 'bg' => '#121E40', 'card' => '#1E3266', 'line' => '#2D437D', 'head1' => '#FFFFFF', 'name' => 'روادنا — RAWADUNA']
            : ['accent' => '#F37021', 'deep' => '#D95300', 'bg' => '#FFF3EC', 'card' => '#FFF6EE', 'line' => '#94a3b8', 'head1' => '#1F2937', 'name' => 'مؤسسة الرواد للتعاون والتنمية'];
    @endphp
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sakkal Majalla', 'Tajawal', sans-serif; color: {{ $theme === 'rowaduna' ? '#FFFFFF' : '#1F2937' }}; padding: 4mm; font-size: 12px; @if($theme === 'rowaduna') background: {{ $brand['bg'] }}; @endif }
        .toolbar { margin-bottom: 10px; text-align: left; }
        .toolbar button { font-family: inherit; background: {{ $brand['accent'] }}; color: #fff; border: 0; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 700; cursor: pointer; }
        @media print { .toolbar { display: none; } body { background: {{ $theme === 'rowaduna' ? $brand['bg'] : '#fff' }}; } }

        .doc-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid {{ $brand['accent'] }}; padding-bottom: 8px; margin-bottom: 10px; }
        .doc-head img { height: 64px; }
        .doc-head .t { text-align: center; }
        .doc-head .t h1 { font-size: 19px; color: {{ $theme === 'rowaduna' ? $brand['head1'] : $brand['deep'] }}; }
        .doc-head .t p { font-size: 11px; color: {{ $theme === 'rowaduna' ? '#A0AEC0' : '#64748b' }}; }
        .rw-badge { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 14px; background: {{ $brand['accent'] }}; color: #fff; font-weight: 800; font-size: 15px; box-shadow: 0 0 0 3px {{ $brand['line'] }}; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid {{ $brand['line'] }}; padding: 5px 7px; text-align: right; }

        .info td { padding: 7px; }
        .info .lab { background: {{ $brand['card'] }}; font-weight: 700; width: 16%; font-size: 10.5px; color: {{ $theme === 'rowaduna' ? '#fff' : 'inherit' }}; }
        .info .val { width: 17.3%; }

        .events thead th { background: {{ $brand['accent'] }}; color: #fff; border-color: {{ $brand['deep'] }}; font-size: 10px; white-space: nowrap; }
        .events td { font-size: 10.5px; }
        .cover-ok { color: #059669; font-weight: 700; }
        .cover-no { color: {{ $theme === 'rowaduna' ? '#FFB4AB' : '#B45309' }}; }
        .st-pending { color: #64748b; }
        a { color: inherit; text-decoration: none; }

        .custody { margin-top: 10px; table-layout: fixed; }
        .custody th { background: {{ $theme === 'rowaduna' ? $brand['card'] : '#2B2D42' }}; color: #fff; font-size: 10.5px; padding: 7px 6px; }
        .custody td { font-size: 10.5px; height: 40px; }

        .status-stamp { margin-top: 10px; display: inline-block; border: 2px solid {{ $brand['accent'] }}; color: {{ $theme === 'rowaduna' ? '#fff' : $brand['deep'] }}; font-weight: 700; padding: 4px 16px; border-radius: 999px; }
        .footer { margin-top: 10px; font-size: 9.5px; color: {{ $theme === 'rowaduna' ? '#A0AEC0' : '#64748b' }}; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>

    <div class="doc-head">
        @if ($theme === 'rowaduna')
            <span class="rw-badge">روادنا</span>
        @else
            <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
        @endif
        <div class="t">
            <h1>{{ $brand['name'] }}</h1>
            <p>Media Plan — الخطة الإعلامية</p>
        </div>
        @if ($theme === 'rowaduna')
            <span class="rw-badge" style="visibility:hidden">روادنا</span>
        @else
            <img src="{{ asset('images/logo.png') }}" alt="" style="visibility:hidden">
        @endif
    </div>

    <table class="info">
        <tr>
            <td class="lab">الشهر</td><td class="val">{{ $plan->month_date->locale('ar')->translatedFormat('F Y') }}</td>
            <td class="lab">المركز / المكتب</td><td class="val">{{ $plan->center?->name ?? '—' }}</td>
            <td class="lab">المشروع</td><td class="val">{{ $plan->project?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lab">أنشأها</td><td class="val">{{ $plan->creator?->name ?? '—' }}</td>
            <td class="lab">المدير المباشر</td><td class="val">{{ $plan->directManager?->name ?? '—' }}</td>
            <td class="lab">مدير المشاريع</td><td class="val">{{ $plan->pm2User?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lab">مسؤول روادنا</td><td class="val">{{ $plan->rowadunaUser?->name ?? '—' }}</td>
            <td class="lab">عدد الفعاليات</td><td class="val">{{ $plan->events->count() }}</td>
            <td class="lab">الملاحظات</td><td class="val">{{ $plan->note ?? '—' }}</td>
        </tr>
    </table>

    <table class="events" style="margin-top:10px">
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>اليوم</th>
                <th>الساعة</th>
                <th>المكتب</th>
                <th>اسم الفعالية</th>
                <th>الموقع</th>
                <th>المسؤول</th>
                <th>نوع التغطية</th>
                <th>الملخص</th>
                <th>المراسل</th>
                <th>التغطية</th>
                <th>رابط المواد</th>
                <th>الناشر</th>
                <th>النشر</th>
                <th>معاينة</th>
                <th>روابط النشر الدائم</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plan->events as $event)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $event->event_date->format('Y-m-d') }}</td>
                    <td>{{ $event->day ?? '—' }}</td>
                    <td>{{ substr((string) $event->event_time, 0, 5) }}</td>
                    <td>{{ $event->office ?? '—' }}</td>
                    <td style="font-weight:700">{{ $event->event_name }}</td>
                    <td>{{ $event->location ?? '—' }}</td>
                    <td>{{ $event->responsible?->name ?? '—' }}</td>
                    <td>{{ $event->coverage_type ?? '—' }}</td>
                    <td>{{ $event->summary ?? '—' }}</td>
                    <td>{{ $event->reporter?->name ?? '—' }}</td>
                    <td class="{{ $event->coverage_status === 'covered' ? 'cover-ok' : ($event->coverage_status === 'not_covered' ? 'cover-no' : 'st-pending') }}">
                        {{ \App\Models\Admin\MediaPlanEvent::COVERAGE_STATUSES[$event->coverage_status] ?? $event->coverage_status }}
                        @if ($event->not_covered_reason)<br><small>({{ $event->not_covered_reason }})</small>@endif
                    </td>
                    <td>@if ($event->media_items_url)<a href="{{ $event->media_items_url }}">{{ \Illuminate\Support\Str::limit($event->media_items_url, 28) }}</a>@else — @endif</td>
                    <td>{{ $event->publisher?->name ?? '—' }}</td>
                    <td>{{ \App\Models\Admin\MediaPlanEvent::PUBLISH_STATUSES[$event->publish_status] ?? $event->publish_status }}</td>
                    <td>@if ($event->preview_url)<a href="{{ $event->preview_url }}">معاينة</a>@else — @endif</td>
                    <td>
                        @forelse ($event->publish_links ?? [] as $link)
                            <a href="{{ $link['url'] }}">{{ \App\Models\Admin\MediaPlanEvent::PLATFORMS[$link['platform']] ?? $link['platform'] }}</a>@if (! $loop->last) · @endif
                        @empty
                            —
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:8px">
        <span class="status-stamp">
            {{ \App\Models\Admin\MediaPlan::STATUSES[$plan->status] ?? $plan->status }}
            @if ($plan->locked_at) — أقفلها {{ $plan->lockedByUser?->name ?? '' }} في {{ $plan->locked_at->format('Y-m-d') }} @endif
        </span>
    </div>

    <div class="footer">
        <span>التاريخ: {{ now()->format('Y-m-d') }}</span>
        <span>طباعة من نظام {{ $brand['name'] }} — الخطة الإعلامية {{ $plan->month_date->format('Y-m') }}</span>
    </div>
</body>
</html>
