<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/*
 * تصدير Excel مُعلَّم بهوية المؤسسة لخطط الحركة: شريط عنوان برتقالي باللوغو
 * واسم المؤسسة + ترويسة أعمدة برتقالية + شبكة — مطابقة لأسلوب الطباعة PDF.
 */
class MovementPlanExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents
{
    private const ORANGE = 'FFF37021';
    private const LABEL_BG = 'FFFFF3EC';

    public function __construct(
        private Collection $rows,
        private array $headings,
        private array $meta = [],
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 12, 'C' => 13, 'D' => 13, 'E' => 22, 'F' => 22, 'G' => 12, 'H' => 12, 'I' => 34, 'J' => 28, 'K' => 24, 'L' => 20, 'M' => 32];
    }

    public function map($row): array
    {
        return [
            $row['request_number'] ?? '',
            $row['plan_month'] ?? '',
            $row['movement_date'] ?? '',
            $row['return_date'] ?? '',
            $row['from_location'] ?? '',
            $row['to_location'] ?? '',
            $row['departure_time'] ?? '',
            $row['return_time'] ?? '',
            $row['purpose'] ?? '',
            $row['entry_notes'] ?? '',
            $row['status'] ?? '',
            $row['officer'] ?? '',
            $row['recipients'] ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->decorate($e->sheet->getDelegate())];
    }

    private function decorate($sheet): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headings));
        $sheet->setRightToLeft(true);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);
        $sheet->insertNewRowBefore(1, 2);

        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', 'مؤسسة الرواد للتعاون والتنمية — خطط الحركة / Movement Plans');
        $sheet->getStyle('A1:'.$lastCol.'1')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:'.$lastCol.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $metaParts = array_filter([
            ! empty($this->meta['plan']) ? 'الخطة: '.$this->meta['plan'] : null,
            ! empty($this->meta['center']) ? 'المكتب: '.$this->meta['center'] : null,
            ! empty($this->meta['project']) ? 'المشروع: '.$this->meta['project'] : null,
            $this->meta['month'] ?? null ? 'الشهر: '.$this->meta['month'] : null,
            $this->meta['status'] ?? null ? 'الحالة: '.$this->meta['status'] : null,
            'عدد البنود: '.($this->meta['count'] ?? $this->rows->count()),
            'تاريخ الإصدار: '.now()->format('Y-m-d'),
        ]);
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->setCellValue('A2', implode('   |   ', $metaParts));
        $sheet->getStyle('A2:'.$lastCol.'2')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A2:'.$lastCol.'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $headRow = 3;
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$headRow}:{$lastCol}{$lastRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A4:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A4');

        $statusColors = [
            'review' => 'FFFFF0C7', 'approved' => 'FFDDE8F5', 'officer_accepted' => 'FFDDE8F5', 'assigned' => 'FFFFF0C7',
            'completed' => 'FFDCEFE1', 'rejected' => 'FFF5DADB', 'cancelled' => 'FFE7EAF0',
        ];
        foreach ($this->rows->values() as $index => $row) {
            $excelRow = 4 + $index;
            if ($index % 2 === 1) {
                $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::LABEL_BG);
            }
            $sheet->getStyle("K{$excelRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($statusColors[$row['status_key'] ?? ''] ?? 'FFFFFFFF');
            if ($index === 0 || ($this->rows[$index - 1]['request_number'] ?? null) !== ($row['request_number'] ?? null)) {
                $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FFD95300');
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(30);
        }

        $footerRow = $lastRow + 2;
        $sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'تاريخ الإصدار: '.now()->format('Y-m-d').' — خطة الحركة الشهرية');
        $sheet->getStyle("A{$footerRow}:{$lastCol}{$footerRow}")->getFont()->setSize(9)->getColor()->setARGB('FF64748B');
        $sheet->getStyle("A{$footerRow}:{$lastCol}{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($logo);
                $drawing->setHeight(30);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(6);
                $drawing->setOffsetY(2);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable) {
            }
        }
    }
}
