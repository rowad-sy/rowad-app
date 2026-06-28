<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التايم شيت - {{ $arabicMonth }}</title>
    <style>
        @page { size: landscape; margin: 10mm; }
        body { font-family: 'DejaVu Sans', 'Tajawal', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 2px 3px; text-align: center; }
        th { background: #f0f0f0; font-weight: bold; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #555; font-size: 12px; }
        .present { background: #d1e7dd; }
        .absent { background: #f8d7da; }
        .excused { background: #fff3cd; }
        .off { background: #e9ecef; color: #999; }
        .weekend { color: #aaa; background: #f8f9fa; }
        .summary { font-weight: bold; font-size: 11px; }
        .footer { text-align: center; margin-top: 10px; font-size: 10px; color: #888; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h2>سجل الحضور والغياب الشهري</h2>
        <p>شهر {{ $arabicMonth }}</p>
    </div>

    @forelse ($timesheets as $index => $ts)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        <table>
            <thead>
                <tr>
                    <th colspan="{{ $daysInMonth + 5 }}" style="text-align:right;font-size:12px;">
                        {{ $ts['employee']->first_name_ar }} {{ $ts['employee']->last_name_ar }}
                        ({{ $ts['employee']->employee_code }})
                        @if ($ts['position'])
                            | {{ $ts['position'] }}
                        @endif
                        @if ($ts['employee']->center)
                            | {{ $ts['employee']->center->name }}
                        @endif
                    </th>
                </tr>
                <tr>
                    <th style="min-width:30px;">#</th>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $date = \Carbon\Carbon::parse(sprintf('%s-%02d-%02d', $year, $monthNum, $d));
                            $isWeekend = $date->dayOfWeek === 5 || $date->dayOfWeek === 6;
                        @endphp
                        <th class="{{ $isWeekend ? 'weekend' : '' }}" style="min-width:20px;font-size:9px;">
                            {{ $d }}<br>
                            <small>{{ $date->locale('ar')->translatedFormat('D') }}</small>
                        </th>
                    @endfor
                    <th>حضور</th>
                    <th>غياب</th>
                    <th>بعذر</th>
                    <th>ملخص</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold;font-size:9px;">الحالة</td>
                    @foreach ($ts['daily'] as $day)
                        <td class="{{ $day['is_off'] ? 'off' : ($day['status'] === 'present' ? 'present' : ($day['status'] === 'absent' ? 'absent' : ($day['status'] === 'excused' ? 'excused' : ''))) }}">
                            @if ($day['is_off'])
                                —
                            @elseif ($day['status'] === 'present')
                                ✔
                            @elseif ($day['status'] === 'absent')
                                ✘
                            @elseif ($day['status'] === 'excused')
                                ع
                            @else
                                ?
                            @endif
                        </td>
                    @endforeach
                    <td class="present summary">{{ $ts['total_present'] }}</td>
                    <td class="absent summary">{{ $ts['total_absent'] }}</td>
                    <td class="excused summary">{{ $ts['total_excused'] }}</td>
                    <td style="font-size:9px;text-align:right;">{{ $ts['leave_details'] }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p style="text-align:center;color:#999;">لا توجد بيانات</p>
    @endforelse

    <div class="footer">
        تم إنشاء التقرير في: {{ now()->locale('ar')->translatedFormat('l d F Y - h:i A') }}
    </div>
</body>
</html>
