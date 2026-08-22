<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الشهادات</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@2.0.4/dist/qrcode.min.js"></script>
    <style>
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Regular.ttf') format('truetype'); font-weight: 400; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Medium.ttf') format('truetype'); font-weight: 500; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Bold.ttf') format('truetype'); font-weight: 700; }
        @page { size: A4 landscape; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: '{{ $certificates->first()?->design?->font_family ?? 'Tajawal' }}', 'Tajawal', sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .certificate-page {
            width: 297mm;
            height: 210mm;
            position: relative;
            overflow: hidden;
            background: #fff;
            page-break-after: always;
        }
        .certificate-page:last-child { page-break-after: auto; }
        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Tajawal', sans-serif;
        }
        .no-print .btn { padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-family: inherit; }
        .no-print .btn-success { background: #198754; color: #fff; }
        .no-print .btn-primary { background: #0d6efd; color: #fff; }
        .field { position: absolute; z-index: 2; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <span style="font-weight:500;">طباعة {{ $certificates->count() }} شهادة</span>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-success" onclick="window.print()"> طباعة الكل</button>
            <button class="btn btn-primary" onclick="window.close()">إغلاق</button>
        </div>
    </div>

    @foreach ($certificates as $certificate)
        <div class="certificate-page">
            @if ($certificate->design?->template_image)
                <img src="{{ asset('storage/' . $certificate->design->template_image) }}" style="position:absolute;top:0;left:0;width:297mm;height:210mm;object-fit:fill;z-index:1;">
            @endif

            @php $config = $certificate->design?->fields_config ?? []; @endphp
            @foreach ($config as $field)
                @php
                    $style = '';
                    $style .= 'top: ' . number_format(($field['y_mm'] ?? 0) / 210 * 100, 4) . '%; ';
                    $style .= 'left: ' . number_format(($field['x_mm'] ?? 0) / 297 * 100, 4) . '%; ';
                    $style .= 'width: ' . number_format(($field['width_mm'] ?? 50) / 297 * 100, 4) . '%; ';
                    $style .= 'height: ' . number_format(($field['height_mm'] ?? 10) / 210 * 100, 4) . '%; ';
                    $style .= 'font-size: ' . ($field['font_size'] ?? 16) . 'pt; ';
                    $style .= 'color: ' . ($field['color'] ?? '#000') . '; ';
                    $style .= 'text-align: ' . ($field['align'] ?? 'center') . '; ';
                    $style .= 'font-weight: ' . ($field['font_weight'] ?? '500') . '; ';
                    $style .= 'display: flex; align-items: center; justify-content: ' . ($field['align'] === 'right' ? 'flex-end' : ($field['align'] === 'left' ? 'flex-start' : 'center')) . '; ';
                @endphp
                <div class="field" style="{{ $style }}">
                    @switch($field['type'] ?? 'text')
                        @case('student_name')
                            {{ $certificate->student->first_name_ar }} {{ $certificate->student->last_name_ar }}
                            @break
                        @case('student_code')
                            {{ $certificate->student->student_code }}
                            @break
                        @case('course_name')
                            {{ $certificate->enrollment?->course?->name_ar ?? $certificate->design?->course?->name_ar ?? '' }}
                            @break
                        @case('period_name')
                            {{ $certificate->enrollment?->period?->name_ar ?? '' }}
                            @break
                        @case('certificate_number')
                            {{ $certificate->certificate_number }}
                            @break
                        @case('issue_date')
                            {{ $certificate->issue_date?->format('Y-m-d') }}
                            @break
                        @case('barcode')
                            <canvas id="qr-{{ $certificate->id }}-{{ $field['id'] ?? $loop->index }}" style="width:100%;height:100%;"></canvas>
                            @break
                        @default
                            {{ $field['label'] ?? '' }}
                    @endswitch
                </div>
            @endforeach
            @php $sigs = $certificate->design?->signatures_config ?? []; @endphp
            @foreach ($sigs as $sig)
                @if (!empty($sig['image_path']))
                    <img src="{{ asset('storage/' . $sig['image_path']) }}"
                         style="position:absolute;
                                z-index:3;
                                top: {{ number_format(($sig['y_mm'] ?? 0) / 210 * 100, 4) }}%;
                                left: {{ number_format(($sig['x_mm'] ?? 0) / 297 * 100, 4) }}%;
                                width: {{ number_format(($sig['width_mm'] ?? 40) / 297 * 100, 4) }}%;
                                height: {{ number_format(($sig['height_mm'] ?? 20) / 210 * 100, 4) }}%;
                                object-fit: contain;">
                @endif
            @endforeach
        </div>
    @endforeach

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        @foreach ($certificates as $certificate)
            @php $cfg = $certificate->design?->fields_config ?? []; @endphp
            @foreach ($cfg as $field)
                @if (($field['type'] ?? '') === 'barcode')
                    (function() {
                        var canvas = document.getElementById('qr-{{ $certificate->id }}-{{ $field['id'] ?? $loop->index }}');
                        if (!canvas || typeof qrcode === 'undefined') return;
                        var url = '{{ url('/verify-certificate/' . $certificate->barcode_hash) }}';
                        var color = '{{ $field['color'] ?? '#000000' }}';
                        var px = Math.min(canvas.clientWidth || 0, canvas.clientHeight || 0) || {{ ($field['width_mm'] ?? 50) * 3.78 }};
                        canvas.width = px * 2;
                        canvas.height = px * 2;
                        var qr = qrcode(0, 'M');
                        qr.addData(url);
                        qr.make();
                        var ctx = canvas.getContext('2d');
                        ctx.scale(2, 2);
                        var cells = qr.getModuleCount();
                        var cellSize = px / cells;
                        for (var r = 0; r < cells; r++) {
                            for (var c = 0; c < cells; c++) {
                                ctx.fillStyle = qr.isDark(r, c) ? color : '#ffffff';
                                ctx.fillRect(c * cellSize, r * cellSize, cellSize, cellSize);
                            }
                        }
                    })();
                @endif
            @endforeach
        @endforeach
    });
    </script>
</body>
</html>
