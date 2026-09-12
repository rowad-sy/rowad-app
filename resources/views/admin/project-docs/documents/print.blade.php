<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title ?: $document->template->title_ar }} — طباعة</title>
    @vite(['resources/js/app.js'])
    <style>
        @page { size: A4 portrait; margin: 0; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Regular.ttf') format('truetype'); font-weight: 400; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Medium.ttf') format('truetype'); font-weight: 500; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Tajawal Local'; src: url('/fonts/Tajawal-Bold.ttf') format('truetype'); font-weight: 700; font-style: normal; font-display: swap; }
        :root { --accent: #f6a13a; --accent-strong: #e07f1f; }
        body { background: #f1f3f5; font-family: 'Tajawal Local', 'Tajawal', sans-serif; }
        .print-sheet {
            position: relative;
            background-color: #fff;
            background-image: url('{{ asset('branding/Picture1.jpg') }}');
            background-size: 210mm 297mm;
            background-repeat: repeat;
            background-position: top center;
            max-width: 210mm; margin: 0 auto 10mm; min-height: 297mm;
            padding: 30mm 18mm 22mm;
            font-family: 'Tajawal Local', 'Tajawal', sans-serif;
            page-break-after: always;
        }
        .print-sheet:last-child { margin-bottom: 0; page-break-after: auto; }
        .print-header { border-bottom: 2px solid var(--accent); padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start; }
        .no-print { margin: 12mm auto 6mm; max-width: 210mm; }
        .no-print .btn + .btn { margin-inline-start: .5rem; }
        .print-header .org { font-weight: 700; color: var(--accent-strong); }
        .print-header .doc-title { font-size: 1.25rem; font-weight: 700; color: var(--accent-strong); }
        .page-topbar { border-bottom: 1.5px dashed var(--accent); padding-bottom: 6px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .page-topbar .doc-title { font-weight: 700; color: var(--accent-strong); font-size: .95rem; }
        .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 12px; font-size: .85rem; margin-bottom: 14px; }
        .meta-grid .m-item b { font-weight: 600; }
        h2.section-title { font-size: 1rem; font-weight: 700; margin: 16px 0 8px; border-right: 4px solid var(--accent); padding-right: 8px; color: var(--accent-strong); }
        .block-body table { font-size: .8rem; }
        .signature-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
        .sig-box { text-align: center; }
        .sig-line { border-top: 1px solid #adb5bd; margin-top: 48px; padding-top: 4px; font-size: .8rem; color: #495057; }
        .page-number {
            position: absolute; bottom: 10mm; left: 0; right: 0; text-align: center;
            font-size: .75rem; color: #6c757d;
        }
        .badge { font-size: .7rem; }
        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { background: #fff; }
            .no-print { display: none; }
            .print-sheet { max-width: none; margin: 0; }
            .sig-line { margin-top: 44px; }
        }
    </style>
</head>
<body>
    @php
        $pageCount = max(1, (int) $document->page_count);
        $sections = $document->sections();
        $grouped = [];
        foreach ($sections as $section) {
            $key = $section['key'] ?? null;
            $block = $document->blocks->firstWhere('block_key', $key);
            $page = (int) ($block?->page_number ?? 1);
            $grouped[$page][] = ['section' => $section, 'block' => $block];
        }
        $totalPages = max($pageCount, max(array_keys($grouped) ?: [1], 1));
    @endphp
    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> طباعة / حفظ PDF</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.project-docs.documents.show', $document) }}">رجوع</a>
        <span class="badge bg-secondary">{{ $totalPages }} {{ Str::plural('صفحة', $totalPages) }}</span>
    </div>

    @for ($p = 1; $p <= $totalPages; $p++)
        <div class="print-sheet">
            @if ($p === 1)
                <div class="print-header">
                    <div>
                        <div class="org">مؤسسة الرواد للتنمية</div>
                        <div class="text-muted small">وثائق المشاريع</div>
                    </div>
                    <div class="text-start">
                        <div class="doc-title">{{ $document->title ?: $document->template->title_ar }}</div>
                        <div class="text-muted small">القالب: {{ $document->template->title_ar }} · الإصدار V{{ $document->template_version }}</div>
                    </div>
                </div>

                <div class="meta-grid">
                    <div class="m-item"><b>المشروع:</b> {{ $document->project?->name ?? '—' }}</div>
                    <div class="m-item"><b>المركز:</b> {{ $document->center?->name ?? '—' }}</div>
                    <div class="m-item"><b>الفترة:</b> {{ $document->period ?? '—' }}</div>
                    <div class="m-item"><b>تاريخ الإعداد:</b> {{ $document->created_at?->format('d/m/Y') }}</div>
                    <div class="m-item"><b>أعدّه:</b> {{ $document->creator?->name }}</div>
                    <div class="m-item">
                        <b>الحالة:</b>
                        @if ($document->isApproved())
                            <span class="badge bg-success">معتمد</span>
                        @elseif ($document->status === 'under_review')
                            <span class="badge bg-warning text-dark">قيد المراجعة</span>
                        @elseif ($document->status === 'rejected')
                            <span class="badge bg-danger">مرفوض</span>
                        @else
                            <span class="badge bg-secondary">مسودة</span>
                        @endif
                    </div>
                </div>

                @foreach ($document->template->headerMeta() as $meta)
                    <div class="small text-muted mb-1">{{ $meta }}</div>
                @endforeach
            @else
                <div class="page-topbar">
                    <span class="doc-title">{{ $document->title ?: $document->template->title_ar }}</span>
                    <span class="text-muted small">تابع — صفحة {{ $p }}</span>
                </div>
            @endif

            @foreach (($grouped[$p] ?? []) as $item)
                <h2 class="section-title">{{ $item['section']['title'] ?? $item['section']['key'] }}
                    @if (!empty($item['section']['assignee_role']))
                        <small class="text-muted fw-normal">(يُعبأ بواسطة: {{ $item['section']['assignee_role'] }})</small>
                    @endif
                </h2>
                <div class="px-1">
                    @include('admin.project-docs.partials.block-view', [
                        'section' => $item['section'],
                        'block' => $item['block'],
                        'document' => $document,
                    ])
                </div>
            @endforeach

            @if ($p === $totalPages)
                @if ($document->signoffs->isNotEmpty())
                    <h2 class="section-title">سجل الاعتمادات</h2>
                    <div class="meta-grid">
                        @foreach ($document->signoffs as $signoff)
                            <div class="m-item">
                                <b>{{ ['approve' => 'اعتماد', 'reject' => 'رفض', 'comment' => 'تعليق'][$signoff->action] ?? $signoff->action }}:</b>
                                {{ $signoff->user?->name }} ({{ $signoff->role_label ?? '—' }}) · {{ $signoff->created_at?->format('d/m/Y H:i') }}
                                @if ($signoff->note)<div class="text-muted">{{ $signoff->note }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="signature-row">
                    <div class="sig-box"><div class="sig-line">توقيع مدير المشروع</div></div>
                    <div class="sig-box"><div class="sig-line">توقيع إدارة المشاريع</div></div>
                    <div class="sig-box"><div class="sig-line">توقيع الإدارة التنفيذية</div></div>
                </div>

                <div class="text-muted small text-center mt-4 border-top pt-2">
                    وثيقة مولّدة من نظام رواد — مؤسسة الرواد للتنمية · {{ now()->format('d/m/Y H:i') }}
                </div>
            @endif

            <div class="page-number">صفحة {{ $p }} من {{ $totalPages }}</div>
        </div>
    @endfor
</body>
</html>