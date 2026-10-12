<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>محضر تسليم عهدة {{ $handoverNote->grn_number }}</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sakkal Majalla', 'Tajawal', sans-serif; color: #1F2937; font-size: 12px; }
        .toolbar { margin-bottom: 10px; text-align: left; }
        .toolbar button { font-family: inherit; background: #F37021; color: #1F1300; border: 0; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 700; cursor: pointer; }
        @media print { .toolbar { display: none; } }

        .doc-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #F37021; padding-bottom: 5px; margin-bottom: 6px; }
        .doc-head img { height: 46px; }
        .doc-head .t { text-align: center; }
        .doc-head .t h1 { font-size: 16px; color: #D95300; }
        .doc-head .t p { font-size: 10.5px; color: #64748b; }
        .form-code { font-size: 10px; color: #64748b; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #94a3b8; padding: 3px 6px; text-align: right; }

        .info { margin-bottom: 6px; }
        .info td { padding: 4px 6px; }
        .info .lab { background: #FFF3EC; font-weight: 700; font-size: 10.5px; width: 22%; }
        .info .val { width: 28%; }

        .items { margin-top: 4px; }
        .items thead th { background: #F37021; color: #fff; border-color: #D95300; font-size: 10.5px; white-space: nowrap; }
        .items td, .items th { text-align: center; }
        .items td.desc { text-align: right; }
        .items tbody td { height: 20px; line-height: 1.15; }
        .items .num { font-variant-numeric: tabular-nums; }

        .signs { margin-top: 8px; table-layout: fixed; }
        .signs th { background: #2B2D42; color: #fff; font-size: 10.5px; padding: 4px 6px; text-align: center; }
        .signs td { padding: 3px 6px; font-size: 10px; }
        .signs .role { font-weight: 700; background: #F8FAFC; width: 18%; }
        .signs .sigcell { height: 56px; vertical-align: middle; }
        .signs img { max-height: 48px; max-width: 95%; }

        .note-row { margin-top: 6px; font-size: 10.5px; padding: 4px 8px; border: 1px dashed #94a3b8; border-radius: 6px; }
        .footer { margin-top: 6px; font-size: 9px; color: #64748b; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>

    <div class="doc-head">
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
        <div class="t">
            <h1>تسليم عهدة — Goods Receipt Note (GRN)</h1>
            <p>مؤسسة الرواد للتعاون والتنمية</p>
        </div>
        <span class="form-code">RCD-OPR-PS-F04</span>
    </div>

    @php
        $pr = $handoverNote->purchaseRequest;
        $centerLabel = trim(($pr->center?->code ?? '').' - '.($pr->center?->name ?? ''), ' -');
        $projectLabel = trim(($pr->project?->code ?? '').' - '.($pr->project?->name ?? ''), ' -');
    @endphp
    <table class="info">
        <tr>
            <td class="lab">رقم محضر التسليم<br>GRN Reference No.</td>
            <td class="val"><strong>{{ $handoverNote->grn_number }}</strong></td>
            <td class="lab">الإدارة / القسم / البرنامج<br>Management / Dep. / Program</td>
            <td class="val">{{ $pr->management_unit ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lab">رقم طلب الشراء<br>PR Reference No.</td>
            <td class="val">{{ $pr->request_number }}</td>
            <td class="lab">اسم ورمز المشروع<br>Project Name and Code</td>
            <td class="val">{{ $projectLabel ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lab">اسم وكود المركز<br>Center Name and Code</td>
            <td class="val">{{ $centerLabel ?: '—' }}</td>
            <td class="lab">تاريخ الاستلام<br>Date of Receiving</td>
            <td class="val">{{ $handoverNote->handover_date->format('Y-m-d') }}</td>
        </tr>
    </table>

    @php $items = $handoverNote->items ?? []; @endphp
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>وصف البند والمواصفات / ITEM Description and Technical Specifications</th>
                <th style="width:14%">الرقم التسلسلي<br>Serial No.</th>
                <th style="width:9%">الوحدة<br>Unit</th>
                <th style="width:8%">الكمية<br>Qty</th>
                <th style="width:10%">الحالة<br>Status</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 20; $i++)
                @php $row = $items[$i] ?? null; @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="desc">{{ $row['description'] ?? '' }}</td>
                    <td class="num">{{ $row['serial'] ?? '' }}</td>
                    <td>{{ $row['unit'] ?? '' }}</td>
                    <td class="num">{{ $row['quantity'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    @if ($handoverNote->note)
        <div class="note-row"><strong>ملاحظات:</strong> {{ $handoverNote->note }}</div>
    @endif

    <table class="signs">
        <thead>
            <tr>
                <th style="width:18%">البند</th>
                <th>تم التسليم من قبل — GOODS DELIVERED BY</th>
                <th>تم الاستلام من قبل — GOODS RECEIVED BY</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="role">الاسم / Name</td>
                <td>{{ $handoverNote->delivered_by_name ?: '....................' }}</td>
                <td>{{ $handoverNote->received_by_name ?: '....................' }}</td>
            </tr>
            <tr>
                <td class="role">الصفة / Position</td>
                <td>{{ $handoverNote->delivered_by_position ?: '....................' }}</td>
                <td>{{ $handoverNote->received_by_position ?: '....................' }}</td>
            </tr>
            <tr>
                <td class="role">التاريخ / Date</td>
                <td>{{ $handoverNote->handover_date->format('Y-m-d') }}</td>
                <td>{{ $handoverNote->handover_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="role">التوقيع / Signature</td>
                <td class="sigcell"></td>
                <td class="sigcell"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <span>تاريخ الإصدار: {{ now()->format('Y-m-d') }}</span>
        <span>محضر تسليم عهدة رقم {{ $handoverNote->grn_number }} — من نظام مؤسسة الرواد</span>
    </div>
</body>
</html>
