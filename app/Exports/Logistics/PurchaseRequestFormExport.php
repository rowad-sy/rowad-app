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
            'م', '#',
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

    private function decorate($sheet): void
    {
        $header = [
            [($this->pr->typeLabel()).' — '.($this->pr->request_type === 'maintenance' ? 'Maintenance Request' : 'Purchase Request').' — '.($this->pr->project?->name ?? ''), '', '', '', '', '', 'مؤسسة الرواد للتعاون والتنمية', ''],
            ['رقم طلب الشراء / PR Ref No:', $this->pr->request_number, '', '', 'اسم ورمز المشروع / Project:', ($this->pr->project?->name ?? '—').' - '.($this->pr->project?->code ?? '—'), '', ''],
            ['اسم وكود المكتب / Office:', trim(($this->pr->center?->code ?? '').' - '.($this->pr->center?->name ?? ''), ' -'), '', '', 'تاريخ الطلب / PR Date:', optional($this->pr->pr_date)->format('Y-m-d'), '', ''],
            ['الإدارة/القسم/الوحدة / Department:', $this->pr->management_unit ?? '—', '', '', 'تاريخ التنفيذ المطلوب / Date Required:', optional($this->pr->required_date)->format('Y-m-d'), '', ''],
            ['حالة الطلب / Status:', PurchaseRequest::STATUSES[$this->pr->status] ?? $this->pr->status, '', '', 'حالة التنفيذ / Execution:', $this->pr->executedItemsCount().' / '.count($this->rows).'  بنود', '', ''],
        ];

        $insert = count($header);
        $sheet->insertNewRowBefore(1, $insert);

        foreach ($header as $i => $line) {
            $sheet->fromArray($line, null, 'A'.($i + 1));
        }

        $lastRow = $sheet->getHighestRow();

        // ترويسة وتلوين
        $sheet->getStyle("A1:H1")->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF37021');
        $sheet->getStyle("A2:H".($insert - 1))->getFont()->setSize(10);
        $sheet->getStyle("A2:H2")->getFont()->setBold(true);

        // شبكة الحدود حول جدول البنود (ترويسة + بنود + إجماليان)
        $sheet->getStyle("A".($insert + 1).":J{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("A".($insert + 1).":J".($insert + 1))->getFont()->setBold(true);

        // كتلة التواقيع
        $sigStart = $lastRow + 2;
        $blocks = [
            ['تم الطلب من قبل / Requested By', $this->signatures['requested_by']],
            ['موافقة المدير المباشر / Direct Manager', $this->signatures['direct_manager']],
            ['موافقة الموارد المالية / Finance', $this->signatures['finance']],
            ['موافقة المدير التنفيذي / CEO', $this->signatures['ceo']],
        ];

        foreach ($blocks as $j => [$label, $sig]) {
            $col = chr(ord('A') + $j * 2);
            $col2 = chr(ord('A') + $j * 2 + 1);
            $sheet->setCellValue("{$col}{$sigStart}", $label);
            $sheet->mergeCells("{$col}{$sigStart}:{$col2}{$sigStart}");
            $sheet->getStyle("{$col}{$sigStart}")->getFont()->setBold(true);
            $sheet->setCellValue("{$col}".($sigStart + 1), 'الاسم / Name: '.($sig['name'] ?? ''));
            $sheet->setCellValue("{$col}".($sigStart + 2), 'الصفة / Position: '.($sig['position'] ?? ''));
            $sheet->setCellValue("{$col}".($sigStart + 3), 'التاريخ / Date: '.($sig['date'] ? \Illuminate\Support\Carbon::parse($sig['date'])->format('Y-m-d') : ''));
            $sheet->setCellValue("{$col}".($sigStart + 4), 'التوقيع / Signature:');

            $path = $sig['image'] ?? null;
            $full = $path ? public_path('storage/'.$path) : null;
            if ($full && is_file($full)) {
                try {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setPath($full);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates("{$col2}".($sigStart + 4));
                    $drawing->setWorksheet($sheet);
                } catch (\Throwable) {
                    // صورة غير قابلة للقراءة — يتجاهلها التصدير
                }
            }
        }

        $sheet->getStyle("A{$sigStart}:H".($sigStart + 4))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getRowDimension($sigStart + 4)->setRowHeight(55);
    }
}
