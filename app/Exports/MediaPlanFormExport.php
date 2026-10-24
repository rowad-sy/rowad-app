<?php

namespace App\Exports;

use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/*
 * تصدير الخطة الإعلامية إلى Excel — بنسختين هوية:
 *  - rowad     : مؤسسة الرواد للتعاون والتنمية (البرتقالي المؤسسي).
 *  - rowaduna  : هوية «روادنا» (كحلي/مرجاني) عند التصدير من صفحة روادنا.
 */
class MediaPlanFormExport implements FromCollection, WithMapping, WithColumnWidths, WithEvents
{
    public function __construct(private MediaPlan $plan, private string $theme = 'rowad') {}

    private function palette(): array
    {
        return $this->theme === 'rowaduna'
            ? ['title' => 'FF1B2B5A', 'head' => 'FFFF4B3E', 'soft' => 'FFE9EDF7']
            : ['title' => 'FFF37021', 'head' => 'FFD95300', 'soft' => 'FFFFF3EC'];
    }

    private function orgName(): string
    {
        return $this->theme === 'rowaduna' ? 'روادنا — RAWADUNA' : 'مؤسسة الرواد للتعاون والتنمية';
    }

    public function collection(): Collection
    {
        return $this->plan->events->values()->map(fn (MediaPlanEvent $event, int $index) => [
            'number' => $index + 1,
            'event' => $event,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6, 'B' => 12, 'C' => 10, 'D' => 9, 'E' => 14, 'F' => 25,
            'G' => 18, 'H' => 18, 'I' => 15, 'J' => 25, 'K' => 18, 'L' => 22,
            'M' => 28, 'N' => 18, 'O' => 24, 'P' => 26, 'Q' => 35,
        ];
    }

    public function map($row): array
    {
        /** @var MediaPlanEvent $event */
        $event = $row['event'];
        $links = collect($event->publish_links ?? [])
            ->map(fn ($l) => (MediaPlanEvent::PLATFORMS[$l['platform']] ?? $l['platform']) . ': ' . $l['url'])
            ->implode(' | ');
        $coverage = MediaPlanEvent::COVERAGE_STATUSES[$event->coverage_status] ?? (string) $event->coverage_status;
        if ($event->not_covered_reason) {
            $coverage .= "\n" . $event->not_covered_reason;
        }

        return [
            $row['number'],
            $event->event_date->format('Y-m-d'),
            $event->day ?? '—',
            substr((string) $event->event_time, 0, 5),
            $event->office ?? '—',
            $event->event_name,
            $event->location ?? '—',
            $event->responsible?->name ?? '—',
            $event->coverage_type ?? '—',
            $event->summary ?? '—',
            $event->reporter?->name ?? '—',
            $coverage,
            $event->media_items_url ?? '—',
            $event->publisher?->name ?? '—',
            MediaPlanEvent::PUBLISH_STATUSES[$event->publish_status] ?? (string) $event->publish_status,
            $event->preview_url ?? '—',
            $links !== '' ? $links : '—',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->decorate($event->sheet->getDelegate()),
        ];
    }

    private function decorate($sheet): void
    {
        $p = $this->palette();
        $plan = $this->plan;
        $month = optional($plan->month_date)->format('Y-m');

        $sheet->setRightToLeft(true);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 6);

        $sheet->insertNewRowBefore(1, 6);
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', $this->orgName() . ' — الخطة الإعلامية / Media Plan');
        $sheet->getStyle('A1:Q1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['title']);
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:Q1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(34);

        $pairs = [
            ['الشهر', $month], ['المركز / المكتب', $plan->center?->name ?? '—'], ['المشروع', $plan->project?->name ?? '—'],
            ['أنشأها', $plan->creator?->name ?? '—'], ['المدير المباشر', $plan->directManager?->name ?? '—'], ['مدير المشاريع', $plan->pm2User?->name ?? '—'],
            ['مسؤول روادنا', $plan->rowadunaUser?->name ?? '—'], ['عدد الفعاليات', (string) $plan->events->count()], ['ملاحظات', $plan->note ?: '—'],
        ];
        $mergePairs = [
            [2, 'A', 'B', 'C', 'E'], [2, 'F', 'G', 'H', 'J'], [2, 'K', 'L', 'M', 'Q'],
            [3, 'A', 'B', 'C', 'E'], [3, 'F', 'G', 'H', 'J'], [3, 'K', 'L', 'M', 'Q'],
            [4, 'A', 'B', 'C', 'E'], [4, 'F', 'G', 'H', 'J'], [4, 'K', 'L', 'M', 'Q'],
        ];
        foreach ($pairs as $index => [$label, $value]) {
            [$row, $labelStart, $labelEnd, $valueStart, $valueEnd] = $mergePairs[$index];
            $sheet->mergeCells("{$labelStart}{$row}:{$labelEnd}{$row}");
            $sheet->mergeCells("{$valueStart}{$row}:{$valueEnd}{$row}");
            $sheet->setCellValue("{$labelStart}{$row}", $label);
            $sheet->setCellValue("{$valueStart}{$row}", $value);
            $sheet->getStyle("{$labelStart}{$row}:{$labelEnd}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['soft']);
            $sheet->getStyle("{$labelStart}{$row}:{$labelEnd}{$row}")->getFont()->setBold(true);
        }

        $sheet->getStyle('A2:Q4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:Q4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(4)->setRowHeight(28);
        $sheet->mergeCells('A5:Q5');
        $sheet->setCellValue('A5', 'تاريخ الإصدار: ' . now()->format('Y-m-d') . '    |    عدد الفعاليات: ' . $plan->events->count());
        $sheet->getStyle('A5:Q5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['soft']);
        $sheet->getStyle('A5:Q5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headRow = 6;
        $headings = [
            '#', 'التاريخ', 'اليوم', 'الساعة', 'المكتب', 'اسم الفعالية', 'الموقع', 'المسؤول', 'نوع التغطية',
            'الملخص', 'المراسل', 'التغطية', 'رابط المواد', 'الناشر', 'النشر', 'المعاينة', 'روابط النشر الدائم',
        ];
        foreach ($headings as $index => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . $headRow, $heading);
        }
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$headRow}:Q{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:Q{$headRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['head']);
        $sheet->getStyle("A{$headRow}:Q{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$headRow}:Q{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$headRow}:Q{$headRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A7:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A7');

        $coverageColors = ['covered' => 'FFDCEFE1', 'not_covered' => 'FFF5DADB', 'assigned' => 'FFFFF0C7', 'pending' => 'FFE7EAF0'];
        $publishColors = ['published' => 'FFDCEFE1', 'to_final' => 'FFDDE8F5', 'to_publish' => 'FFFFF0C7', 'to_review' => 'FFFFF0C7', 'rework' => 'FFF5DADB', 'none' => 'FFE7EAF0'];
        foreach ($plan->events->values() as $index => $event) {
            $row = 7 + $index;
            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:Q{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['soft']);
            }
            $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($coverageColors[$event->coverage_status] ?? 'FFFFFFFF');
            $sheet->getStyle("O{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($publishColors[$event->publish_status] ?? 'FFFFFFFF');
            $sheet->getRowDimension($row)->setRowHeight(30);
        }

        $stampRow = max(7, 7 + $plan->events->count());
        $sheet->mergeCells("A{$stampRow}:Q{$stampRow}");
        $stamp = MediaPlan::STATUSES[$plan->status] ?? $plan->status;
        if ($plan->locked_at) {
            $stamp .= ' — أُقفلت بواسطة ' . ($plan->lockedByUser?->name ?? '—') . ' في ' . $plan->locked_at->format('Y-m-d');
        }
        $sheet->setCellValue("A{$stampRow}", 'حالة الخطة: ' . $stamp);
        $sheet->getStyle("A{$stampRow}:Q{$stampRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($p['soft']);
        $sheet->getStyle("A{$stampRow}:Q{$stampRow}")->getFont()->setBold(true)->getColor()->setARGB($p['title']);
        $sheet->getStyle("A{$stampRow}:Q{$stampRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

        $footerRow = $stampRow + 2;
        $sheet->mergeCells("A{$footerRow}:Q{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'تاريخ الإصدار: ' . now()->format('Y-m-d') . ' — ' . $this->orgName() . ' — الخطة الإعلامية');
        $sheet->getStyle("A{$footerRow}:Q{$footerRow}")->getFont()->setSize(9)->getColor()->setARGB('FF64748B');
        $sheet->getStyle("A{$footerRow}:Q{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $logoPath = public_path('images/logo.png');
        if ($this->theme !== 'rowaduna' && is_file($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(27);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
        }
    }
}
