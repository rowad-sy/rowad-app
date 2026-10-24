<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectPathsExport implements FromArray, WithEvents, WithTitle
{
    private const BRAND = 'FFF37021';
    private const TRACK = 'FFDDF1E2';
    private const TRACK_CODE = 'FFEDF8EF';
    private const HEADER = 'FFF1F1F1';
    private const NUMBER = 'FFEAF7ED';

    private array $columns;
    private int $groupCount;
    private int $maxRows;

    public function __construct(Collection $columns)
    {
        $this->columns = $columns->values()->all();
        $this->groupCount = max(1, count($this->columns));
        $this->maxRows = $columns->max(fn (array $column) => count($column['items'] ?? [])) ?: 0;
    }

    public function array(): array
    {
        $columnCount = $this->groupCount * 3;
        $rows = [];
        $title = array_fill(0, $columnCount, '');
        $title[0] = 'مؤسسة الرواد للتعاون والتنمية — قائمة المسارات والمشاريع';
        $rows[] = $title;

        $date = array_fill(0, $columnCount, '');
        $date[0] = 'تاريخ الإصدار: ' . now()->format('Y-m-d');
        $rows[] = $date;

        $trackHeadings = array_fill(0, $columnCount, '');
        $trackCodes = array_fill(0, $columnCount, '');
        $columnHeadings = array_fill(0, $columnCount, '');

        foreach ($this->columns as $index => $column) {
            $start = $index * 3;
            $title = ! empty($column['orphan']) ? 'مشاريع بدون مسار' : ($column['title'] ?? '');
            $rank = $column['sort_order'] ?? null;

            $trackHeadings[$start] = $rank ? $rank . ' — ' . $title : $title;
            $trackCodes[$start] = $column['code'] ?? '';
            $columnHeadings[$start] = '#';
            $columnHeadings[$start + 1] = 'المشروع';
            $columnHeadings[$start + 2] = 'الحالة';
        }

        $rows[] = $trackHeadings;
        $rows[] = $trackCodes;
        $rows[] = $columnHeadings;

        for ($rowIndex = 0; $rowIndex < $this->maxRows; $rowIndex++) {
            $row = array_fill(0, $columnCount, '');

            foreach ($this->columns as $columnIndex => $column) {
                $item = $column['items'][$rowIndex] ?? null;
                if (! $item) {
                    continue;
                }

                $start = $columnIndex * 3;
                $row[$start] = $item['sort_order'] ?? ($rowIndex + 1);
                $row[$start + 1] = $item['name'];
                $row[$start + 2] = $item['status'];
            }

            $rows[] = $row;
        }

        $footer = array_fill(0, $columnCount, '');
        $pathCount = collect($this->columns)->where('orphan', false)->count();
        $projectCount = collect($this->columns)->sum(fn (array $column) => count($column['items'] ?? []));
        $footer[0] = 'إجمالي المسارات: ' . $pathCount . ' — المشاريع: ' . $projectCount;
        $rows[] = $footer;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->decorate($event->sheet->getDelegate())];
    }

    public function title(): string
    {
        return 'المسارات والمشاريع';
    }

    private function decorate(Worksheet $sheet): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($this->groupCount * 3);
        $lastRow = 6 + $this->maxRows;

        $sheet->setRightToLeft(true);
        $sheet->setShowGridlines(false);
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A" . $lastRow . ":{$lastColumn}" . $lastRow);

        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::BRAND);
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->getStyle("A2:{$lastColumn}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE7E7E7');
        $sheet->getStyle("A2:{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($group = 0; $group < $this->groupCount; $group++) {
            $startIndex = ($group * 3) + 1;
            $start = Coordinate::stringFromColumnIndex($startIndex);
            $project = Coordinate::stringFromColumnIndex($startIndex + 1);
            $end = Coordinate::stringFromColumnIndex($startIndex + 2);

            $sheet->mergeCells("{$start}3:{$end}3");
            $sheet->mergeCells("{$start}4:{$end}4");
            $sheet->getStyle("{$start}3:{$end}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TRACK);
            $sheet->getStyle("{$start}3:{$end}3")->getFont()->setBold(true);
            $sheet->getStyle("{$start}4:{$end}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TRACK_CODE);
            $sheet->getStyle("{$start}3:{$end}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->getColumnDimension($start)->setWidth(7);
            $sheet->getColumnDimension($project)->setWidth(38);
            $sheet->getColumnDimension($end)->setWidth(16);
        }

        $sheet->getStyle("A5:{$lastColumn}5")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER);
        $sheet->getStyle("A5:{$lastColumn}5")->getFont()->setBold(true);
        $sheet->getStyle("A5:{$lastColumn}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF333333');
        $sheet->getStyle("A5:{$lastColumn}" . ($lastRow - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        for ($rowIndex = 0; $rowIndex < $this->maxRows; $rowIndex++) {
            $excelRow = 6 + $rowIndex;
            $sheet->getRowDimension($excelRow)->setRowHeight(29);

            foreach ($this->columns as $columnIndex => $column) {
                $item = $column['items'][$rowIndex] ?? null;
                $statusColor = match ($item['status_key'] ?? '') {
                    'active' => 'FFDCEFE1',
                    'pending' => 'FFFFF0C7',
                    'closed' => 'FFF5DADB',
                    'studying' => 'FFDDE8F5',
                    'internal' => 'FFE8DFF0',
                    default => 'FFFFFFFF',
                };

                $numberColumn = Coordinate::stringFromColumnIndex(($columnIndex * 3) + 1);
                $statusColumn = Coordinate::stringFromColumnIndex(($columnIndex * 3) + 3);
                $sheet->getStyle("{$numberColumn}{$excelRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::NUMBER);
                $sheet->getStyle("{$statusColumn}{$excelRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($statusColor);
                $sheet->getStyle("{$numberColumn}{$excelRow}:{$statusColumn}{$excelRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            }
        }

        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF3EC');
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getFont()->setBold(true)->getColor()->setARGB('FF9B4615');
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->freezePane('A6');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setFitToPage(true)->setRowsToRepeatAtTopByStartAndEnd(1, 5);
        $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);

        $logoPath = public_path('branding/logo.png');
        if (is_file($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(28);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
        }
    }
}
