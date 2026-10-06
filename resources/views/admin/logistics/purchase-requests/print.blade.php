<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php $typeEn = $purchaseRequest->request_type === 'maintenance' ? 'Maintenance Request' : 'Purchase Request'; @endphp
    <title>{{ $purchaseRequest->typeLabel() }} — {{ $purchaseRequest->request_number }}</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sakkal Majalla', 'Tajawal', sans-serif; color: #1F2937; padding: 4mm; font-size: 12.5px; }
        .toolbar { margin-bottom: 12px; text-align: left; }
        .toolbar button { font-family: inherit; background: #F37021; color: #1F1300; border: 0; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 700; cursor: pointer; }
        @media print { .toolbar { display: none; } }

        .doc-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #F37021; padding-bottom: 8px; margin-bottom: 10px; }
        .doc-head img { height: 70px; }
        .doc-head .t { text-align: center; }
        .doc-head .t h1 { font-size: 18px; color: #D95300; }
        .doc-head .t p { font-size: 11px; color: #64748b; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #94a3b8; padding: 6px 8px; text-align: right; }

        .info td { padding: 8px; }
        .info .lab { background: #FFF3EC; font-weight: 700; width: 24%; font-size: 11px; }
        .info .val { width: 26%; }

        .items thead th { background: #F37021; color: #fff; border-color: #D95300; font-size: 11px; white-space: nowrap; }
        .items .num { font-variant-numeric: tabular-nums; text-align: center; }
        .items td { height: 26px; }
        .items .totalrow td { background: #FFF6EE; font-weight: 700; }
        .exec-yes { color: #059669; font-weight: 700; }
        .exec-no { color: #B45309; }

        .signs { margin-top: 12px; table-layout: fixed; }
        .signs th { background: #2B2D42; color: #fff; border-color: #1F2937; font-size: 11px; padding: 8px 6px; }
        .signs td { border-color: #94a3b8; font-size: 10.5px; }
        .signs .role { font-weight: 700; background: #F8FAFC; }
        .signs .sigcell { height: 64px; vertical-align: middle; }
        .signs img { max-height: 56px; max-width: 95%; }

        .status-stamp { margin-top: 10px; display: inline-block; border: 2px solid #D95300; color: #D95300; font-weight: 700; padding: 4px 16px; border-radius: 999px; }
        .footer { margin-top: 10px; font-size: 10px; color: #64748b; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>

    <div class="doc-head">
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
        <div class="t">
            <h1>مؤسسة الرواد للتعاون والتنمية</h1>
            <p>{{ $typeEn }} — {{ $purchaseRequest->typeLabel() }}</p>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="" style="visibility:hidden">
    </div>

    <table class="info">
        <tr>
            <td class="lab">رقم طلب الشراء<br><span style="font-weight:400;color:#64748b">PR Reference No.</span></td>
            <td class="val"><strong>{{ $purchaseRequest->request_number }}</strong></td>
            <td class="lab">اسم ورمز المشروع<br><span style="font-weight:400;color:#64748b">Project Name and Code</span></td>
            <td class="val">{{ $purchaseRequest->project?->code ? $purchaseRequest->project->code.' - ' : '' }}{{ $purchaseRequest->project?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lab">اسم وكود المكتب<br><span style="font-weight:400;color:#64748b">Office Name and Code</span></td>
            <td class="val">{{ $purchaseRequest->center?->code ? $purchaseRequest->center->code.' - ' : '' }}{{ $purchaseRequest->center?->name ?? '—' }}</td>
            <td class="lab">تاريخ الطلب<br><span style="font-weight:400;color:#64748b">PR Date</span></td>
            <td class="val">{{ optional($purchaseRequest->pr_date)->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td class="lab">الإدارة / القسم / الوحدة<br><span style="font-weight:400;color:#64748b">Management/Department/Unit</span></td>
            <td class="val">{{ $purchaseRequest->management_unit ?? '—' }}</td>
            <td class="lab">تاريخ التنفيذ المطلوب<br><span style="font-weight:400;color:#64748b">Date Items Required</span></td>
            <td class="val">{{ optional($purchaseRequest->required_date)->format('Y-m-d') ?? '—' }}</td>
        </tr>
    </table>

    @php
        $display = array_slice($rows, 0, 15);
        $executedCount = collect($rows)->where('executed', true)->count();
    @endphp
    <table class="items" style="margin-top:10px">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>المنتج / ITEM</th>
                <th style="width:8%">الكمية<br>Qty</th>
                <th style="width:9%">الوحدة<br>Unit</th>
                <th style="width:8%">العملة<br>Curr</th>
                <th style="width:13%">تكلفة الوحدة<br>Est. Unit Cost</th>
                <th style="width:14%">التكلفة الإجمالية<br>Est. Total Cost</th>
                <th style="width:12%">خط الميزانية<br>Budget Line</th>
                <th style="width:8%">منفَّذ؟<br>Executed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($display as $row)
                <tr>
                    <td class="num">{{ $row['n'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="num">{{ $row['quantity'] }}</td>
                    <td class="num">{{ $row['unit'] }}</td>
                    <td class="num">{{ $row['currency'] }}</td>
                    <td class="num">{{ number_format((float) $row['unit_price'], 2) }}</td>
                    <td class="num">{{ number_format((float) $row['total_price'], 2) }}</td>
                    <td class="num">{{ $row['budget_line'] !== null ? $row['budget_line'] : '' }}</td>
                    <td class="num {{ $row['executed'] ? 'exec-yes' : 'exec-no' }}">{{ $row['executed'] ? '✓' : ($row['description'] !== '' ? '—' : '') }}</td>
                </tr>
            @endforeach
            @for ($i = count($display); $i < 15; $i++)
                <tr><td class="num">{{ $i + 1 }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
            <tr class="totalrow">
                <td colspan="6" style="text-align:left">الإجمالي — دولار أمريكي / Total (USD)</td>
                <td class="num">{{ number_format($totals['USD'], 2) }} $</td>
                <td colspan="2"></td>
            </tr>
            <tr class="totalrow">
                <td colspan="6" style="text-align:left">الإجمالي — ليرة سورية / Total (SYP)</td>
                <td class="num">{{ number_format($totals['SYP'], 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:8px">
        <span class="status-stamp">
            {{ \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$purchaseRequest->status] ?? $purchaseRequest->status }}
            @if (in_array($purchaseRequest->status, ['approved', 'executed'], true))
                — البنود المنفذة: {{ $executedCount }} / {{ count($rows) }}
            @endif
        </span>
    </div>

    <table class="signs">
        <thead>
            <tr>
                <th>تم الطلب من قبل<br><span style="font-weight:400">Requested By</span></th>
                <th>موافقة المدير المباشر<br><span style="font-weight:400">Direct Manager Approval</span></th>
                <th>موافقة قسم الموارد المالية<br><span style="font-weight:400">Finance Dept. Approval</span></th>
                <th>موافقة المدير التنفيذي<br><span style="font-weight:400">CEO Approval</span></th>
            </tr>
        </thead>
        <tbody>
            @php $blocks = [$signatures['requested_by'], $signatures['direct_manager'], $signatures['finance'], $signatures['ceo']]; @endphp
            <tr>
                @foreach ($blocks as $b)
                    <td><span class="role">Name:</span> {{ $b['name'] ?: '....................' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($blocks as $b)
                    <td><span class="role">الاسم:</span> {{ $b['name'] ?: '....................' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($blocks as $b)
                    <td><span class="role">Position:</span> {{ $b['position'] ?: '................' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($blocks as $b)
                    <td><span class="role">الصفة:</span> {{ $b['position'] ?: '................' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($blocks as $b)
                    <td><span class="role">Date:</span> {{ $b['date'] ? \Illuminate\Support\Carbon::parse($b['date'])->format('Y-m-d') : '................' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($blocks as $b)
                    <td class="sigcell">
                        <span class="role">التوقيع / Signature:</span>
                        @if ($b['image'])
                            <img src="{{ asset('storage/'.$b['image']) }}" alt="توقيع">
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <span>التاريخ: {{ now()->format('Y-m-d') }}</span>
        <span>طباعة من نظام مؤسسة الرواد — {{ $purchaseRequest->typeLabel() }} رقم {{ $purchaseRequest->request_number }}</span>
    </div>
</body>
</html>
