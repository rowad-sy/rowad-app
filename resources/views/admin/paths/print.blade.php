<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شجرة المسارات والمشاريع</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tajawal', sans-serif; color: #1F2937; padding: 4mm; }
        .print-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #F37021; padding-bottom: 10px; margin-bottom: 14px; }
        .print-header .title h1 { font-size: 20px; color: #D95300; }
        .print-header .title p { font-size: 12px; color: #64748b; }
        .print-header img { height: 78px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 9px; text-align: right; }
        thead th { background: #F37021; color: #fff; border-color: #D95300; }
        tbody tr:nth-child(even) { background: #FFF6EE; }
        .path-cell { font-weight: 700; color: #B45309; }
        .st { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .st-active { background: #d1fae5; color: #059669; }
        .st-studying { background: #fef3c7; color: #B45309; }
        .st-closed { background: #fee2e2; color: #DC2626; }
        .st-pending { background: #e5e7eb; color: #6B7280; }
        .st-internal { background: #dbeafe; color: #2563EB; }
        .footer { margin-top: 12px; font-size: 11px; color: #64748b; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="print-header">
        <div class="title">
            <h1>شجرة المسارات والمشاريع</h1>
            <p>مؤسسة الرواد للتعاون والتنمية</p>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">م/ت</th>
                <th style="width: 180px;">المسار</th>
                <th>اسم المشروع</th>
                <th style="width: 130px;">كود المشروع</th>
                <th style="width: 130px;">حالة المشروع</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 0; @endphp
            @foreach ($paths as $path)
                @foreach ($path->projects as $project)
                    <tr>
                        <td>{{ ++$n }}</td>
                        <td class="path-cell">{{ $path->name }}</td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->code ?? '—' }}</td>
                        <td><span class="st st-{{ $project->status }}">{{ $project->statusLabel() }}</span></td>
                    </tr>
                @endforeach
            @endforeach
            @foreach ($orphanProjects as $project)
                <tr>
                    <td>{{ ++$n }}</td>
                    <td class="path-cell">بدون مسار</td>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->code ?? '—' }}</td>
                    <td><span class="st st-{{ $project->status }}">{{ $project->statusLabel() }}</span></td>
                </tr>
            @endforeach
            @if ($n === 0)
                <tr><td colspan="5" style="text-align:center; color:#94a3b8;">لا توجد بيانات للتصدير</td></tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <span>عدد المشاريع: {{ $n }}</span>
        <span>تاريخ الإصدار: {{ now()->translatedFormat('d F Y') }}</span>
    </div>

    <script>window.onload = function () { setTimeout(function () { window.print(); }, 300); };</script>
</body>
</html>
