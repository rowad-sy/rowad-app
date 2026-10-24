<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة المسارات والمشاريع</title>
    <style>
        @page { size: {{ $columns->count() > 5 ? 'A3' : 'A4' }} landscape; margin: 7mm; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Regular.ttf') format('truetype'); font-weight: 400; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Medium.ttf') format('truetype'); font-weight: 500; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Bold.ttf') format('truetype'); font-weight: 700; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #202124; font-family: 'Sakkal Majalla', 'Tajawal Local', 'Tajawal', sans-serif; }
        .toolbar { display: flex; justify-content: flex-start; gap: 7px; margin: 0 auto 8px; max-width: 285mm; }
        .toolbar button, .toolbar a { display: inline-block; border: 0; border-radius: 6px; padding: 7px 15px; background: #F37021; color: #fff; font-family: inherit; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar a { background: #fff3ec; color: #A9440A; border: 1px solid #edc2a8; }
        .sheet { width: 100%; break-inside: avoid; }
        .brand-header { display: flex; justify-content: center; align-items: center; min-height: 18mm; border: 1px solid #333; border-bottom: 0; padding: 2mm 4mm; }
        .brand-header img { width: 55mm; max-height: 15mm; object-fit: contain; }
        .date-band { padding: 1.3mm; border: 1px solid #333; background: #e7e7e7; text-align: center; font-size: 9pt; font-weight: 600; }
        .report-title { padding: 1.2mm; border: 1px solid #333; border-top: 0; background: #fff8f3; color: #B94D0B; text-align: center; font-size: 10pt; font-weight: 700; }
        .paths-print-table { width: 100%; table-layout: fixed; border-collapse: collapse; direction: rtl; }
        .paths-print-table th, .paths-print-table td { border: 1px solid #171717; }
        .paths-print-table thead { display: table-header-group; }
        .track-heading { height: 9mm; padding: 1mm 1.4mm; background: #DDF1E2; color: #202124; font-size: 9pt; font-weight: 700; }
        .track-heading-main { display: flex; align-items: center; justify-content: center; gap: 1.5mm; text-align: center; }
        .track-rank { display: inline-grid; place-items: center; min-width: 5mm; height: 5mm; border-radius: 2px; background: #F37021; color: #fff; font-size: 8pt; }
        .track-code { height: 5mm; padding: .5mm; background: #EDF8EF; text-align: center; font-size: 7.5pt; font-weight: 700; }
        .track-code-empty { background: #EDF8EF; }
        .column-heading { height: 6mm; padding: .7mm; background: #f1f1f1; text-align: center; font-size: 7.5pt; font-weight: 700; }
        .project-number { padding: .7mm .3mm; background: #EAF7ED; color: #A7440B; text-align: center; font-size: 7.5pt; font-weight: 700; }
        .project-name { padding: 1mm 1.2mm; text-align: center; font-size: 8pt; line-height: 1.2; overflow-wrap: anywhere; }
        .project-status { padding: .7mm .4mm; text-align: center; font-size: 7pt; font-weight: 700; line-height: 1.15; }
        .status-active { background: #DCEFE1; color: #28613A; }
        .status-studying { background: #DDE8F5; color: #315577; }
        .status-closed { background: #F5DADB; color: #963B40; }
        .status-pending { background: #FFF0C7; color: #775D16; }
        .status-internal { background: #E8DFF0; color: #644576; }
        .empty-cell { background: #fff; }
        .empty-state { padding: 12mm 4mm; border: 1px solid #333; text-align: center; color: #64748b; }
        .report-footer { display: flex; justify-content: space-between; gap: 8mm; padding-top: 2mm; color: #64748b; font-size: 8pt; }
        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none; }
            .paths-print-table tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $pathCount = $columns->count();
        $maxRows = $columns->max(fn (array $column) => count($column['items'])) ?: 0;
        $totalProjects = $columns->sum(fn (array $column) => count($column['items']));
        $groupWidth = $pathCount > 0 ? 100 / $pathCount : 100;
    @endphp

    <div class="toolbar">
        <button onclick="window.print()">طباعة / حفظ PDF</button>
        @if ($orphanCount > 0)
            <a href="{{ route('admin.paths.export.pdf', array_filter([
                'status' => request('status'),
                'include_orphans' => $includeOrphans ? null : 1,
            ])) }}">
                {{ $includeOrphans ? 'إخفاء المشاريع بدون مسار' : 'إظهار المشاريع بدون مسار (' . $orphanCount . ')' }}
            </a>
        @endif
    </div>

    <main class="sheet">
        <header class="brand-header">
            <img src="{{ asset('branding/logo.png') }}" alt="مؤسسة الرواد للتعاون والتنمية">
        </header>
        <div class="date-band" dir="ltr">{{ now()->format('Y-m-d') }}</div>
        <div class="report-title">قائمة المسارات والمشاريع</div>

        @if ($columns->isNotEmpty())
            <table class="paths-print-table">
                <colgroup>
                    @foreach ($columns as $column)
                        <col style="width: {{ $groupWidth * .08 }}%">
                        <col style="width: {{ $groupWidth * .70 }}%">
                        <col style="width: {{ $groupWidth * .22 }}%">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th colspan="2" class="track-heading">
                                <div class="track-heading-main">
                                    @if (!empty($column['sort_order']))
                                        <span class="track-rank">{{ $column['sort_order'] }}</span>
                                    @endif
                                    @if (!empty($column['orphan']))
                                        <span>مشاريع بدون مسار</span>
                                    @else
                                        <span>{{ $column['title'] }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="track-code-empty"></th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($columns as $column)
                            <th colspan="2" class="track-code">{{ $column['code'] ?? '' }}</th>
                            <th class="track-code-empty"></th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($columns as $column)
                            <th class="column-heading">#</th>
                            <th class="column-heading">المشروع</th>
                            <th class="column-heading">الحالة</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++)
                        <tr>
                            @foreach ($columns as $column)
                                @php $item = $column['items'][$rowIndex] ?? null; @endphp
                                @if ($item)
                                    <td class="project-number">{{ $item['sort_order'] ?? ($rowIndex + 1) }}</td>
                                    <td class="project-name">{{ $item['name'] }}</td>
                                    <td class="project-status status-{{ $item['status_key'] }}">{{ $item['status'] }}</td>
                                @else
                                    <td class="empty-cell"></td>
                                    <td class="empty-cell"></td>
                                    <td class="empty-cell"></td>
                                @endif
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        @else
            <div class="empty-state">لا توجد مسارات أو مشاريع مطابقة للطباعة.</div>
        @endif

        <footer class="report-footer">
            <span>المسارات: {{ $columns->where('orphan', false)->count() }} · المشاريع: {{ $totalProjects }}</span>
            <span>تاريخ الإصدار: {{ now()->translatedFormat('d F Y') }}</span>
        </footer>
    </main>
</body>
</html>
