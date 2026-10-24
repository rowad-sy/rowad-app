<?php

namespace App\Exports;

use App\Models\Admin\Logistics\HandoverNote;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/*
 * تصدير محضر التسليم إلى Excel بنفس تنسيق نموذج RCD-OPR-PS-F04:
 * شريط عنوان برتقالي باللوغو + رأس المعلومات + جدول 20 بنداً + خانتا التوقيع.
 */
class HandoverNoteExport implements FromArray, WithColumnWidths, WithEvents
{
    private const ORANGE = 'FFF37021';
    private const DARK = 'FF2B2D42';
    private const LABEL_BG = 'FFFFF3EC';
    private const ROWS = 20;

    public function __construct(private HandoverNote $note) {}

    public function array(): array
    {
        $items = $this->note->items ?? [];
        $data = [];

        for ($i = 0; $i < self::ROWS; $i++) {
            $row = $items[$i] ?? null;
            $data[] = [
                $i + 1,
                $row['description'] ?? '',
                $row['serial'] ?? '',
                $row['unit'] ?? '',
                $row['quantity'] ?? '',
                $row['status'] ?? '',
            ];
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return ['A' => 7, 'B' => 38, 'C' => 17, 'D' => 12, 'E' => 10, 'F' => 15];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->decorate($e->sheet->getDelegate())];
    }

    private function decorate($sheet): void
    {
        $pr = $this->note->purchaseRequest;
        $sheet->setRightToLeft(true);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 6);
        $sheet->insertNewRowBefore(1, 6);

        // 1) شريط العنوان + رقم النموذج
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'مؤسسة الرواد للتعاون والتنمية — تسليم عهدة / Goods Receipt Note (GRN) — RCD-OPR-PS-F04');
        $sheet->getStyle('A1:F1')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(42);

        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($logo);
                $drawing->setHeight(36);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(8);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable) {
            }
        }

        // 2) رأس المعلومات كما في PDF
        $centerLabel = trim(($pr->center?->code ?? '').' - '.($pr->center?->name ?? ''), ' -');
        $projectLabel = trim(($pr->project?->code ?? '').' - '.($pr->project?->name ?? ''), ' -');
        $info = [
            ['رقم محضر التسليم / GRN No.', (string) $this->note->grn_number, 'الإدارة/القسم / Program', $pr->management_unit ?: '—'],
            ['رقم طلب الشراء / PR No.', (string) $pr->request_number, 'اسم ورمز المشروع / Project', $projectLabel ?: '—'],
            ['اسم وكود المركز / Center', $centerLabel ?: '—', 'تاريخ الاستلام / Receiving Date', $this->note->handover_date->format('Y-m-d')],
        ];
        foreach ($info as $i => [$l1, $v1, $l2, $v2]) {
            $r = $i + 2;
            $sheet->setCellValue('A'.$r, $l1); $sheet->mergeCells('A'.$r.':B'.$r);
            $sheet->setCellValue('C'.$r, $v1);
            $sheet->setCellValue('D'.$r, $l2);
            $sheet->mergeCells('D'.$r.':E'.$r);
            $sheet->setCellValue('F'.$r, $v2);
        }
        $sheet->getStyle('A2:F4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:A4')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('D2:D4')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A2:A4')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getStyle('D2:D4')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getStyle('A2:F4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(30);

        // 3) ملاحظة
        $noteRow = 5;
        $sheet->mergeCells('B'.$noteRow.':F'.$noteRow);
        $sheet->setCellValue('A'.$noteRow, 'ملاحظات');
        $sheet->setCellValue('B'.$noteRow, (string) ($this->note->note ?: ''));
        $sheet->getStyle('A'.$noteRow)->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getStyle('A'.$noteRow)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A'.$noteRow.':F'.$noteRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A'.$noteRow.':F'.$noteRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($noteRow)->setRowHeight(28);

        // 4) رأس جدول البنود (الصف 8 بعد الإدراج — الآن الصف 6+1)
        $headRow = 6;
        $heads = ['#', 'وصف البند / ITEM Description', 'الرقم التسلسلي / Serial No.', 'الوحدة / Unit', 'الكمية / Qty', 'الحالة / Status'];
        foreach ($heads as $i => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1).$headRow, $h);
        }
        $sheet->getStyle("A{$headRow}:F{$headRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle("A{$headRow}:F{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:F{$headRow}")->getAlignment()->setHorizontal('center')->setWrapText(true);

        $lastItemRow = $headRow + self::ROWS;
        $sheet->getStyle("A{$headRow}:F{$lastItemRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A7:A{$lastItemRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("C7:F{$lastItemRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A7:F{$lastItemRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A7:A{$lastItemRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getRowDimension($headRow)->setRowHeight(34);
        for ($row = 7; $row <= $lastItemRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(23);
        }
        $sheet->freezePane('A7');

        // 5) خانتا التوقيع
        $sigTop = $lastItemRow + 2;
        $sheet->setCellValue('A'.$sigTop, 'البند');
        $sheet->mergeCells('B'.$sigTop.':D'.$sigTop);
        $sheet->setCellValue('B'.$sigTop, 'تم التسليم من قبل — GOODS DELIVERED BY');
        $sheet->mergeCells('E'.$sigTop.':F'.$sigTop);
        $sheet->setCellValue('E'.$sigTop, 'تم الاستلام من قبل — GOODS RECEIVED BY');
        $sheet->getStyle('A'.$sigTop.':F'.$sigTop)->getFill()->setFillType('solid')->getStartColor()->setARGB(self::DARK);
        $sheet->getStyle('A'.$sigTop.':F'.$sigTop)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A'.$sigTop.':F'.$sigTop)->getAlignment()->setHorizontal('center');

        $sigRows = [
            ['الاسم / Name', $this->note->delivered_by_name, $this->note->received_by_name],
            ['الصفة / Position', $this->note->delivered_by_position ?: '', $this->note->received_by_position ?: ''],
            ['التاريخ / Date', $this->note->handover_date->format('Y-m-d'), $this->note->handover_date->format('Y-m-d')],
            ['التوقيع / Signature', '', ''],
        ];
        foreach ($sigRows as $i => [$label, $d, $r]) {
            $row = $sigTop + 1 + $i;
            $sheet->setCellValue('A'.$row, $label);
            $sheet->mergeCells('B'.$row.':D'.$row);
            $sheet->setCellValue('B'.$row, $d);
            $sheet->mergeCells('E'.$row.':F'.$row);
            $sheet->setCellValue('E'.$row, $r);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle('A'.$row)->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        }
        $sheet->getRowDimension($sigTop + 4)->setRowHeight(42);
        $sheet->getStyle('A'.$sigTop.':F'.($sigTop + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $footerRow = $sigTop + 6;
        $sheet->mergeCells('A'.$footerRow.':F'.$footerRow);
        $sheet->setCellValue('A'.$footerRow, 'تاريخ الإصدار: '.now()->format('Y-m-d').' — محضر تسليم عهدة رقم '.$this->note->grn_number);
        $sheet->getStyle('A'.$footerRow.':F'.$footerRow)->getFont()->setSize(8)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A'.$footerRow.':F'.$footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
