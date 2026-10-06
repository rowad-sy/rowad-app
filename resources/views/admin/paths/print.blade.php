<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة المسارات والمشاريع</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sakkal Majalla', 'Tajawal', sans-serif; color: #1F2937; padding: 4mm; }
        .print-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #F37021; padding-bottom: 10px; margin-bottom: 14px; }
        .print-header .title h1 { font-size: 20px; color: #D95300; }
        .print-header .title p { font-size: 12px; color: #64748b; }
        .print-header img { height: 78px; }
        .columns { display: flex; gap: 8px; align-items: flex-start; }
        .p-col { flex: 1 1 0; min-width: 0; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; break-inside: avoid; }
        .p-col-head { background: #F37021; color: #fff; padding: 7px 9px; font-weight: 700; font-size: 12px; display: flex; justify-content: space-between; gap: 6px; align-items: baseline; }
        .p-col-head .code { background: #fff; color: #D95300; border-radius: 999px; padding: 1px 8px; font-size: 10.5px; }
        .p-col ul { list-style: none; }
        .p-col li { display: flex; gap: 6px; align-items: baseline; padding: 6px 9px; font-size: 11.5px; border-bottom: 1px dashed #e2e8f0; }
        .p-col li:last-child { border-bottom: 0; }
        .p-col .n { color: #B45309; font-weight: 700; min-width: 16px; }
        .p-col .empty { color: #94a3b8; font-style: italic; }
        .st { display: inline-block; margin-inline-start: auto; padding: 1px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .st-active { background: #d1fae5; color: #059669; }
        .st-studying { background: #fef3c7; color: #B45309; }
        .st-closed { background: #fee2e2; color: #DC2626; }
        .st-pending { background: #e5e7eb; color: #6B7280; }
        .st-internal { background: #dbeafe; color: #2563EB; }
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
            <h1>قائمة المسارات والمشاريع</h1>
            <p>مؤسسة الرواد للتعاون والتنمية</p>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
    </div>

    <div class="columns">
        @foreach ($columns as $column)
            @php
                // استخرج الحالة من آخر شارة في السطر للألوان — نبقيها بسيطة: الحالة مكتوبة داخل النص
            @endphp
            <div class="p-col">
                <div class="p-col-head">
                    <span>{{ $column['title'] }}</span>
                    <span class="code">{{ count($column['items']) }}</span>
                </div>
                <ul>
                    @forelse ($column['items'] as $item)
                        <li>
                            <span class="n">{{ $loop->iteration }}</span>
                            <span>{{ $item['name'] }}@if($item['code']) ({{ $item['code'] }}) @endif</span>
                            <span class="st st-{{ $item['status_key'] }}">{{ $item['status'] }}</span>
                        </li>
                    @empty
                        <li class="empty">لا مشاريع</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
        @if ($columns->isEmpty())
            <div class="p-col"><div class="p-col-head"><span>لا توجد بيانات</span></div><ul><li class="empty">—</li></ul></div>
        @endif
    </div>

    <div class="footer">
        <span>إجمالي المسارات: {{ $columns->count() }}</span>
        <span>تاريخ الإصدار: {{ now()->translatedFormat('d F Y') }}</span>
    </div>
</body>
</html>
