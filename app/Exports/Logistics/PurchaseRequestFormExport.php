<?php

namespace App\Exports\Logistics;

use App\Models\Admin\Logistics\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PurchaseRequestFormExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents
{
    private const ITEM_ROWS = 15;

    public function __construct(
        private PurchaseRequest $pr,
        private array $rows,
        private array $totals,
        private array $signatures,
    ) {}

    public function collection(): Collection
    {
        $rows = array_slice($this->rows, 0, self::ITEM_ROWS);

        while (count($rows) < self::ITEM_ROWS) {
            $rows[] = ['n' => count($rows) + 1, 'description' => '', 'quantity' => '', 'unit' => '', 'currency' => '', 'unit_price' => '', 'total_price' => '', 'budget_line' => '', 'executed' => false];
        }

        $rows[] = ['n' => '', 'description' => 'الإجمالي — دولار أمريكي / Total (USD)', 'quantity' => '', 'unit' => '', 'currency' => 'USD', 'unit_price' => '', 'total_price' => $this->totals['USD'], 'budget_line' => '', 'executed' => false];
        $rows[] = ['n' => '', 'description' => 'الإجمالي — ليرة سورية / Total (SYP)', 'quantity' => '', 'unit' => '', 'currency' => 'SYP', 'unit_price' => '', 'total_price' => $this->totals['SYP'], 'budget_line' => '', 'executed' => false];

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'م / #', 'المنتج / ITEM', '', '', '', 'الكمية / Qty', 'الوحدة / Unit', 'العملة / Curr',
            'تكلفة الوحدة / Est. Unit Cost', 'التكلفة الإجمالية / Est. Total Cost', 'خط الميزانية / Budget Line', 'منفَّذ؟ / Executed',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 12, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 9, 'G' => 10, 'H' => 9, 'I' => 14, 'J' => 15, 'K' => 14, 'L' => 10];
    }

    public function map($row): array
    {
        return [
            $row['n'],
            $row['description'],
            '', '', '',
            $row['quantity'],
            $row['unit'],
            $row['currency'],
            $row['unit_price'] !== '' ? (float) $row['unit_price'] : '',
            $row['total_price'] !== '' ? (float) $row['total_price'] : '',
            $row['budget_line'],
            $row['n'] === '' ? '' : ($row['executed'] ? 'نعم' : 'لا'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->decorate($event->sheet->getDelegate()),
        ];
    }

    private const ORANGE = 'FFF37021';
    private const ORANGE_DARK = 'FFD96A10';
    private const SOFT_BG = 'FFFFF6EE';
    private const LABEL_BG = 'FFFFF3EC';
    private const INK = 'FF1F2937';
    private const DARK = 'FF2B2D42';

    private function decorate($sheet): void
    {
        $col = fn (int $index) => Coordinate::stringFromColumnIndex($index);
        $sheet->setRightToLeft(true);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 6);

        $insertRows = 5;
        $sheet->insertNewRowBefore(1, $insertRows);
        $sheet->mergeCells('A1:L1');
        $typeEn = $this->pr->request_type === 'maintenance' ? 'Maintenance Request' : 'Purchase Request';
        $sheet->setCellValue('A1', 'مؤسسة الرواد للتعاون والتنمية — '.$typeEn.' — '.$this->pr->typeLabel());
        $sheet->getStyle('A1:L1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(42);

        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath($logo);
            $drawing->setHeight(34);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setWorksheet($sheet);
        }

        $projectLabel = trim(($this->pr->project?->code ? $this->pr->project->code.' - ' : '').($this->pr->project?->name ?? '—'));
        $centerLabel = trim(($this->pr->center?->code ? $this->pr->center->code.' - ' : '').($this->pr->center?->name ?? '—'));
        $info = [
            ['رقم طلب الشراء / PR Reference No.', (string) $this->pr->request_number, 'اسم ورمز المشروع / Project Name and Code', $projectLabel],
            ['اسم وكود المكتب / Office Name and Code', $centerLabel, 'تاريخ الطلب / PR Date', optional($this->pr->pr_date)->format('Y-m-d') ?: '—'],
            ['الإدارة / القسم / الوحدة / Department', $this->pr->management_unit ?: '—', 'تاريخ التنفيذ المطلوب / Date Items Required', optional($this->pr->required_date)->format('Y-m-d') ?: '—'],
        ];
        foreach ($info as $index => [$label1, $value1, $label2, $value2]) {
            $row = $index + 2;
            foreach ([['A', 'C', 'D', 'F', $label1, $value1], ['G', 'I', 'J', 'L', $label2, $value2]] as [$labelStart, $labelEnd, $valueStart, $valueEnd, $label, $value]) {
                $sheet->mergeCells("{$labelStart}{$row}:{$labelEnd}{$row}");
                $sheet->mergeCells("{$valueStart}{$row}:{$valueEnd}{$row}");
                $sheet->setCellValue("{$labelStart}{$row}", $label);
                $sheet->setCellValue("{$valueStart}{$row}", $value);
                $sheet->getStyle("{$labelStart}{$row}:{$labelEnd}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::LABEL_BG);
                $sheet->getStyle("{$labelStart}{$row}:{$labelEnd}{$row}")->getFont()->setBold(true)->setSize(9);
            }
        }
        $sheet->getStyle('A2:L4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:L4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        foreach ([2, 3, 4] as $row) $sheet->getRowDimension($row)->setRowHeight(30);

        $executedCount = collect($this->rows)->where('executed', true)->count();
        $stamp = PurchaseRequest::STATUSES[$this->pr->status] ?? $this->pr->status;
        if (in_array($this->pr->status, ['approved', 'executed'], true)) {
            $stamp .= ' — البنود المنفذة: '.$executedCount.' / '.count($this->rows);
        }
        $sheet->mergeCells('A5:L5');
        $sheet->setCellValue('A5', 'حالة الطلب / Status: '.$stamp);
        $sheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::SOFT_BG);
        $sheet->getStyle('A5:L5')->getFont()->setBold(true)->getColor()->setARGB(self::ORANGE_DARK);
        $sheet->getStyle('A5:L5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headRow = 6;
        $sheet->mergeCells('B'.$headRow.':E'.$headRow);
        $sheet->getStyle("A{$headRow}:L{$headRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle("A{$headRow}:L{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:L{$headRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($headRow)->setRowHeight(34);

        $itemsStart = $headRow + 1;
        $itemsEnd = $itemsStart + self::ITEM_ROWS - 1;
        $totalsStart = $itemsEnd + 1;
        $lastItemsRow = $totalsStart + 1;
        for ($row = $itemsStart; $row <= $itemsEnd; $row++) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getRowDimension($row)->setRowHeight(25);
        }
        foreach ([$totalsStart, $lastItemsRow] as $row) {
            $sheet->mergeCells("B{$row}:I{$row}");
            $sheet->getStyle("A{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::SOFT_BG);
            $sheet->getStyle("A{$row}:L{$row}")->getFont()->setBold(true);
        }
        $sheet->getStyle("A{$headRow}:L{$lastItemsRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$itemsStart}:L{$lastItemsRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$itemsStart}:A{$lastItemsRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$itemsStart}:L{$lastItemsRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$itemsStart}:J{$lastItemsRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->freezePane('A7');

        $signatureStart = $lastItemsRow + 2;
        $blocks = [
            [['تم الطلب من قبل', 'Requested By'], $this->signatures['requested_by']],
            [['موافقة المدير المباشر', 'Direct Manager Approval'], $this->signatures['direct_manager']],
            [['موافقة قسم الموارد المالية', 'Finance Dept. Approval'], $this->signatures['finance']],
            [['موافقة المدير التنفيذي', 'CEO Approval'], $this->signatures['ceo']],
        ];
        $ranges = [['A', 'C'], ['D', 'F'], ['G', 'I'], ['J', 'L']];
        foreach ($blocks as $index => [[$arabic, $english], $signature]) {
            [$start, $end] = $ranges[$index];
            $sheet->mergeCells("{$start}{$signatureStart}:{$end}{$signatureStart}");
            $sheet->setCellValue("{$start}{$signatureStart}", $arabic."\n".$english);
            for ($offset = 1; $offset <= 4; $offset++) {
                $row = $signatureStart + $offset;
                $sheet->mergeCells("{$start}{$row}:{$end}{$row}");
            }
            $sheet->setCellValue($start.($signatureStart + 1), 'الاسم / Name: '.($signature['name'] ?: ''));
            $sheet->setCellValue($start.($signatureStart + 2), 'الصفة / Position: '.($signature['position'] ?: ''));
            $sheet->setCellValue($start.($signatureStart + 3), 'التاريخ / Date: '.($signature['date'] ? \Illuminate\Support\Carbon::parse($signature['date'])->format('Y-m-d') : ''));
            $sheet->setCellValue($start.($signatureStart + 4), 'التوقيع / Signature:');
        }
        $sheet->getStyle("A{$signatureStart}:L".($signatureStart + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$signatureStart}:L{$signatureStart}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::DARK);
        $sheet->getStyle("A{$signatureStart}:L{$signatureStart}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$signatureStart}:L".($signatureStart + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($signatureStart)->setRowHeight(34);
        $sheet->getRowDimension($signatureStart + 4)->setRowHeight(42);

        $footerRow = $signatureStart + 6;
        $sheet->mergeCells("A{$footerRow}:L{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'تاريخ الإصدار: '.now()->format('Y-m-d').' — '.$this->pr->typeLabel().' رقم '.$this->pr->request_number);
        $sheet->getStyle("A{$footerRow}:L{$footerRow}")->getFont()->setSize(8)->getColor()->setARGB('FF64748B');
        $sheet->getStyle("A{$footerRow}:L{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $logoPath = public_path('images/logo.png');
        if (is_file($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(32);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setWorksheet($sheet);
        }
    }
}
