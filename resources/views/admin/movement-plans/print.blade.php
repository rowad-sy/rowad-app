<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($plan) && $plan ? 'خطة الحركة '.$plan->request_number : 'خطط الحركة' }}</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sakkal Majalla', 'Tajawal', sans-serif; color: #1F2937; padding: 4mm; }
        .print-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #F37021; padding-bottom: 10px; margin-bottom: 14px; }
        .print-header .title h1 { font-size: 20px; color: #D95300; }
        .print-header .title p { font-size: 12px; color: #64748b; }
        .print-header .meta { font-size: 12px; color: #475569; text-align: center; }
        .print-header img { height: 78px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; vertical-align: top; }
        thead th { background: #F37021; color: #fff; border-color: #D95300; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #FFF6EE; }
        tr.group-start td { border-top: 2px solid #D95300; }
        .plan-no { font-weight: 700; color: #B45309; white-space: nowrap; }
        .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .st { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; white-space: nowrap; }
        .st-review { background: #fef3c7; color: #B45309; }
        .st-approved { background: #e0f2fe; color: #0369A1; }
        .st-assigned { background: #fff3ec; color: #B34700; }
        .st-completed { background: #d1fae5; color: #059669; }
        .st-rejected { background: #fee2e2; color: #DC2626; }
        .footer { margin-top: 12px; font-size: 11px; color: #64748b; display: flex; justify-content: space-between; }
        @media print { .toolbar { display: none; } }
        .toolbar { margin-bottom: 12px; text-align: left; }
        .toolbar button { font-family: inherit; background: #F37021; color: #1F1300; border: 0; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>

    <div class="print-header">
        <div class="title">
            <h1>{{ isset($plan) && $plan ? 'خطة الحركة '.$plan->request_number : 'خطط الحركة' }}</h1>
            <p>مؤسسة الرواد للتعاون والتنمية</p>
        </div>
        <div class="meta">
            @if (isset($plan) && $plan)<div>المكتب: <strong>{{ $plan->center?->name ?? '—' }}</strong></div>@endif
            @if ($month)<div>الشهر: <strong>{{ $month }}</strong></div>@endif
            @if ($status)<div>الحالة: <strong>{{ $status }}</strong></div>@endif
            <div>عدد البنود: {{ $rows->count() }}</div>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:90px">رقم الخطة</th>
                <th style="width:60px">الشهر</th>
                <th style="width:85px">التاريخ</th>
                <th style="width:60px">اليوم</th>
                <th style="width:70px">الانطلاق</th>
                <th style="width:70px">العودة</th>
                <th style="width:120px">من</th>
                <th style="width:120px">إلى</th>
                <th>الغاية</th>
                <th style="width:90px">حالة الخطة</th>
                <th style="width:110px">مسؤول الحركة</th>
                <th style="width:130px">المتابِعون</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->first || $row['request_number'] !== $rows[$loop->index - 1]['request_number'] ? 'group-start' : '' }}">
                    <td class="plan-no">{{ $row['request_number'] }}</td>
                    <td class="num">{{ $row['plan_month'] }}</td>
                    <td class="num">{{ $row['movement_date'] }}</td>
                    <td>{{ $row['day_name'] }}</td>
                    <td class="num">{{ $row['departure_time'] ?: '—' }}</td>
                    <td class="num">{{ $row['return_time'] ?: '—' }}</td>
                    <td>{{ $row['from_location'] ?: '—' }}</td>
                    <td>{{ $row['to_location'] ?: '—' }}</td>
                    <td>{{ $row['purpose'] }}</td>
                    <td><span class="st st-{{ $row['status_key'] }}">{{ $row['status'] }}</span></td>
                    <td>{{ $row['officer'] ?: '—' }}</td>
                    <td>{{ $row['recipients'] ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;padding:24px;color:#64748b">لا توجد خطط حركة مطابقة</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>التاريخ: {{ now()->format('Y-m-d') }}</span>
        <span>طباعة من النظام — خطة الحركة الشهرية</span>
    </div>
</body>
</html>
