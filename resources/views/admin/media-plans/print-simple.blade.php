<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>الخطة الإعلامية — {{ $plan->month_date->format('Y-m') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Tahoma, Arial, sans-serif; color: #1f2937; font-size: 12px; }
        header { border-bottom: 3px solid {{ $theme === 'rowaduna' ? '#ff4b3e' : '#f37021' }}; margin-bottom: 14px; padding-bottom: 10px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .meta { color: #475569; line-height: 1.8; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: right; vertical-align: top; }
        th { background: {{ $theme === 'rowaduna' ? '#1b2b5a' : '#d95300' }}; color: #fff; }
        .toolbar { margin-bottom: 10px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>
    <header>
        <h1>{{ $theme === 'rowaduna' ? 'روادنا — الخطة الإعلامية' : 'مؤسسة الرواد للتعاون والتنمية — الخطة الإعلامية' }}</h1>
        <div class="meta">
            الشهر: {{ $plan->month_date->locale('ar')->translatedFormat('F Y') }} · المركز: {{ $plan->center?->name ?? '—' }} · المشروع: {{ $plan->project?->name ?? '—' }}
            <br>أنشأها: {{ $plan->creator?->name ?? '—' }} · الحالة: {{ \App\Models\Admin\MediaPlan::STATUSES[$plan->status] ?? $plan->status }} · ملاحظات: {{ $plan->note ?? '—' }}
        </div>
    </header>
    <table>
        <thead><tr><th>#</th><th>التاريخ</th><th>من</th><th>إلى</th><th>الفعالية</th><th>الموقع</th><th>نوع التغطية</th><th>المراسل</th><th>الحالة</th><th>ملاحظة التغطية / سبب عدمها</th></tr></thead>
        <tbody>
        @foreach ($plan->events as $event)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $event->event_date->format('Y-m-d') }}</td>
                <td>{{ substr((string) $event->event_time, 0, 5) }}</td>
                <td>{{ $event->event_end_time ? substr((string) $event->event_end_time, 0, 5) : '—' }}</td>
                <td>{{ $event->event_name }}</td>
                <td>{{ $event->location ?? '—' }}</td>
                <td>{{ $event->coverage_type ?? '—' }}</td>
                <td>{{ $event->reporter?->name ?? '—' }}</td>
                <td>{{ \App\Models\Admin\MediaPlanEvent::COVERAGE_STATUSES[$event->coverage_status] ?? $event->coverage_status }}</td>
                <td>{{ $event->not_covered_reason ?: ($event->coverage_note ?: '—') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="meta">تاريخ الإصدار: {{ now()->format('Y-m-d') }}</p>
</body>
</html>
