<?php

namespace App\Exports\Logistics;

use App\Models\Admin\Logistics\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseRequestExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return PurchaseRequest::with(['user', 'center', 'project', 'items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($pr) {
                if ($pr->items->isEmpty()) {
                    return collect([(object) array_merge($pr->toArray(), ['item_description' => $pr->specifications, 'item_quantity' => $pr->quantity, 'item_unit' => $pr->unit, 'item_unit_price' => $pr->expected_unit_price, 'item_total' => $pr->expected_total_price, 'item_budget_line' => null, 'item_notes' => ''])]);
                }
                return $pr->items->map(fn($item) => (object) array_merge($pr->toArray(), [
                    'item_description' => $item->description,
                    'item_quantity' => $item->quantity,
                    'item_unit' => $item->unit,
                    'item_unit_price' => $item->unit_price,
                    'item_total' => $item->total_price,
                    'item_budget_line' => $item->budget_line,
                    'item_notes' => $item->notes ?? '',
                ]));
            });
    }

    public function headings(): array
    {
        return [
            'رقم الطلب', 'الوصف', 'الكمية', 'الوحدة', 'خط الميزانية', 'سعر الوحدة',
            'الإجمالي', 'ملاحظات البند', 'إجمالي الطلب', 'المركز', 'المشروع',
            'الحالة', 'المستخدم', 'ملاحظات الطلب', 'تاريخ الإنشاء',
        ];
    }

    public function map($row): array
    {
        return [
            $row->request_number,
            $row->item_description,
            $row->item_quantity,
            $row->item_unit,
            $row->item_budget_line !== null ? number_format($row->item_budget_line, 0) : '—',
            number_format($row->item_unit_price, 2),
            number_format($row->item_total, 2),
            $row->item_notes,
            number_format($row->expected_total_price, 2),
            $row->center?->name ?? '—',
            $row->project?->name ?? '—',
            match ($row->status) { 'pending' => 'قيد الانتظار', 'approved' => 'معتمد', 'rejected' => 'مرفوض', 'executed' => 'منفذ', default => $row->status },
            $row->user?->name ?? '—',
            $row->notes ?? '—',
            $row->created_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
