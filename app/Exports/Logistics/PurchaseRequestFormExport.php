<?php

namespace App\Exports\Logistics;

use App\Models\Admin\Logistics\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PurchaseRequestFormExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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
            'م / #',
            'المنتج / ITEM',
            'الكمية / Qty',
            'الوحدة / Unit',
            'العملة / Curr',
            'تكلفة الوحدة / Est. Unit Cost',
            'التكلفة الإجمالية / Est. Total Cost',
            'خط الميزانية / Budget Line',
            'منفَّذ؟ / Executed',
        ];
    }

    public function map($row): array
    {
        return [
            $row['n'],
            $row['description'],
            $row['quantity'],
            $row['unit'],
            $row['currency'],
            $row['unit_price'] !== '' ? (float) $row['unit_price'] : '',
            $row['total_price'] !== '' ? (float) $row['total_price'] : '',
            $row['budget_line'],
            $row['executed'] ? 'نعم' : ($row['description'] !== '' ? 'لا' : ''),
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
        $col = fn (int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);

        // ضبط صفحة A4 أفقي مناسب للنموذج (يعرضها Excel بالاتجاه الطبيعي للنصوص العربية)
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);

        /* ── الترويسة: صف عنوان برتقالي + اللوغو (كما في PDF) ── */
        $typeEn = $this->pr->request_type === 'maintenance' ? 'Maintenance Request' : 'Purchase Request';
        $insert = 5;
        $sheet->insertNewRowBefore(1, $insert);
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'مؤسسة الرواد للتعاون والتنمية — '.$typeEn.' — '.$this->pr->typeLabel());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:J1')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getRowDimension(1)->setRowHeight(46);

        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($logo);
                $drawing->setHeight(40);
                $drawing->setCoordinates('H1');
                $drawing->setOffsetX(8);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable) {
            }
        }

        /* ── شبكة معلومات الرأس (2×3 + صف الحالة) مطابقة لـ .info في الـ PDF ── */
        $info = [
            ['رقم طلب الشراء / PR Reference No.', (string) $this->pr->request_number, 'اسم ورمز المشروع / Project Name and Code', trim(($this->pr->project?->name ?? '—').' - '.($this->pr->project?->code ?? '—'), ' -')],
            ['اسم وكود المكتب / Office Name and Code', trim(($this->pr->center?->code ?? '').' - '.($this->pr->center?->name ?? '—'), ' -'), 'تاريخ الطلب / PR Date', optional($this->pr->pr_date)->format('Y-m-d') ?: '—'],
            ['الإدارة / القسم / الوحدة / Department', $this->pr->management_unit ?: '—', 'تاريخ التنفيذ المطلوب / Date Items Required', optional($this->pr->required_date)->format('Y-m-d') ?: '—'],
        ];

        foreach ($info as $i => [$l1, $v1, $l2, $v2]) {
            $r = $i + 2;
            $sheet->setCellValue($col(0).$r, $l1); $sheet->mergeCells($col(0).$r.':'.$col(1).$r);
            $sheet->setCellValue($col(2).$r, $v1); $sheet->mergeCells($col(2).$r.':'.$col(4).$r);
            $sheet->setCellValue($col(5).$r, $l2); $sheet->mergeCells($col(5).$r.':'.$col(7).$r);
            $sheet->setCellValue($col(8).$r, $v2); $sheet->mergeCells($col(8).$r.':'.$col(9).$r);
        }

        // صف الحالة (ختم الطابع كما في الـ PDF)
        $executedCount = collect($this->rows)->where('executed', true)->count();
        $statusRow = 5;
        $stamp = \App\Models\Admin\Logistics\PurchaseRequest::STATUSES[$this->pr->status] ?? $this->pr->status;
        if (in_array($this->pr->status, ['approved', 'executed'], true)) {
            $stamp .= ' — البنود المنفذة: '.$executedCount.' / '.count($this->rows);
        }
        $sheet->setCellValue('A'.$statusRow, 'حالة الطلب / Status: '.$stamp);
        $sheet->mergeCells('A'.$statusRow.':J'.$statusRow);
        $sheet->getStyle('A'.$statusRow)->getFont()->setBold(true)->getColor()->setARGB(self::ORANGE_DARK);
        $sheet->getStyle('A'.$statusRow)->getAlignment()->setHorizontal('center');

        // تنسيق شبكة المعلومات: حدود + خلفيات التسميات
        $sheet->getStyle('A2:J'.$statusRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ([['A', 'B'], ['F', 'H']] as [$a, $b]) {
            foreach (['2', '3', '4'] as $r) {
                $style = $sheet->getStyle($a.$r.':'.$b.$r);
                $style->getFill()->setFillType('solid')->getStartColor()->setARGB(self::LABEL_BG);
                $style->getFont()->setBold(true)->setSize(9);
            }
        }
        $sheet->getStyle('A2:J4')->getFont()->setSize(10);
        $sheet->getStyle('A2:J5')->getAlignment()->setVertical('center')->setWrapText(true);

        $lastRow = $sheet->getHighestRow();

        // ترويسة جدول البنود برتقالية + نص أبيض (مثل thead في الـ PDF)
        $headRow = $insert + 1;
        $sheet->getStyle("A{$headRow}:J{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:J{$headRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::ORANGE);
        $sheet->getStyle("A{$headRow}:J{$headRow}")->getAlignment()->setHorizontal('center')->setWrapText(true);

        // شبكة الحدود حول جدول البنود (ترويسة + بنود + إجماليان)
        $sheet->getStyle("A".($insert + 1).":J{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // أسطر الإجمالي: خلفية فاتحة + عريض
        $sheet->getStyle('A'.($lastRow - 1).":J{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle('A'.($lastRow - 1).":J{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::SOFT_BG);

        // تنسيق الأرقام داخل الجدول
        $numCols = 'F'.($insert + 1).':H'.$lastRow;
        $sheet->getStyle($numCols)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle($numCols)->getAlignment()->setHorizontal('center');

        /* ── شبكة التواقيع الأربعة (كما في .signs بالـ PDF) ── */
        $sigStart = $lastRow + 2;
        $blocks = [
            [['تم الطلب من قبل', 'Requested By'], $this->signatures['requested_by']],
            [['موافقة المدير المباشر', 'Direct Manager Approval'], $this->signatures['direct_manager']],
            [['موافقة قسم الموارد المالية', 'Finance Dept. Approval'], $this->signatures['finance']],
            [['موافقة المدير التنفيذي', 'CEO Approval'], $this->signatures['ceo']],
        ];

        $ranges = [['A', 'C'], ['D', 'F'], ['G', 'I'], ['J', 'L']];

        foreach ($blocks as $k => [[ $ar, $en ], $sig]) {
            [$a, $b] = $ranges[$k];
            $put = function (int $offset, string $value) use ($sheet, $a, $b, $sigStart) {
                $r = $sigStart + $offset;
                $sheet->setCellValue($a.$r, $value);
                $sheet->mergeCells($a.$r.':'.$b.$r);
            };

            $put(0, $ar."\n".$en);
            $put(1, 'Name / الاسم: '.($sig['name'] ?: ''));
            $put(2, 'Position / الصفة: '.($sig['position'] ?: ''));
            $put(3, 'Date / التاريخ: '.($sig['date'] ? \Illuminate\Support\Carbon::parse($sig['date'])->format('Y-m-d') : ''));
            $put(4, 'التوقيع / Signature:');

            $path = $sig['image'] ?? null;
            $full = $path ? public_path('storage/'.$path) : null;
            if ($full && is_file($full)) {
                try {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setPath($full);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates($a.($sigStart + 4));
                    $drawing->setOffsetX(120);
                    $drawing->setWorksheet($sheet);
                } catch (\Throwable) {
                }
            }
        }

        // تلوين رؤوس التواقيع (كحلية مثل thead في .signs) وحدود الشبكة
        $lastRange = $ranges[3][1];
        $sheet->getStyle("A{$sigStart}:{$lastRange}".($sigStart + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($ranges as [$a, $b]) {
            $style = $sheet->getStyle($a.$sigStart.':'.$b.$sigStart);
            $style->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType('solid')->getStartColor()->setARGB(self::DARK);
            $style->getAlignment()->setHorizontal('center')->setWrapText(true);
        }
        $sheet->getStyle("A".($sigStart + 1).":{$lastRange}".($sigStart + 4))->getFont()->setSize(9);
        $sheet->getRowDimension($sigStart)->setRowHeight(30);
        $sheet->getRowDimension($sigStart + 4)->setRowHeight(60);

        // تذييل مطابق لـ footer في الـ PDF
        $footRow = $sigStart + 6;
        $sheet->setCellValue('A'.$footRow, 'التاريخ: '.now()->format('Y-m-d'));
        $sheet->setCellValue('E'.$footRow, 'طباعة من نظام مؤسسة الرواد — '.$this->pr->typeLabel().' رقم '.$this->pr->request_number);
        $sheet->getStyle('A'.$footRow.':'.$lastRange.$footRow)->getFont()->setSize(8)->getColor()->setARGB('FF64748B');
    }
}
