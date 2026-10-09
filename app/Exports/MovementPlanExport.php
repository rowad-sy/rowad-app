<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

/*
 * تصدير Excel مُعلَّم بهوية المؤسسة لخطط الحركة: شريط عنوان برتقالي باللوغو
 * واسم المؤسسة + ترويسة أعمدة برتقالية + شبكة — مطابقة لأسلوب الطباعة PDF.
 */
class MovementPlanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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

    public function map($row): array
    {
        return [
            $row['request_number'] ?? '',
            $row['plan_month'] ?? '',
            $row['movement_date'] ?? '',
            $row['day_name'] ?? '',
            $row['departure_time'] ?? '',
            $row['return_time'] ?? '',
            $row['from_location'] ?? '',
            $row['to_location'] ?? '',
            $row['purpose'] ?? '',
            $row['entry_notes'] ?? '',
            $row['status'] ?? '',
            $row['center'] ?? '',
            $row['project'] ?? '',
            $row['creator'] ?? '',
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

        $sheet->insertNewRowBefore(1, 2);

        // شريط العنوان
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', 'مؤسسة الرواد للتعاون والتنمية — جدول خطط الحركة');
        $sheet->getStyle('A1:'.$lastCol.'1')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(34);

        // سطر البيانات الوصفية (الخطة/الشهر/الحالة/العدد/التاريخ)
        $metaParts = array_filter([
            ! empty($this->meta['plan']) ? 'الخطة: '.$this->meta['plan'] : null,
            $this->meta['month'] ?? null ? 'الشهر: '.$this->meta['month'] : null,
            $this->meta['status'] ?? null ? 'الحالة: '.$this->meta['status'] : null,
            'عدد البنود: '.($this->meta['count'] ?? $this->rows->count()),
            'تاريخ الإصدار: '.now()->format('Y-m-d'),
        ]);
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->setCellValue('A2', implode('   |   ', $metaParts));
        $sheet->getStyle('A2:'.$lastCol.'2')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        // ترويسة الأعمدة برتقالية
        $headRow = 3;
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->getAlignment()->setHorizontal('center')->setWrapText(true);

        $lastRow = $sheet->getHighestRow();

        // شبكة حول الجدول كاملاً
        $sheet->getStyle("A{$headRow}:{$lastCol}{$lastRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

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
