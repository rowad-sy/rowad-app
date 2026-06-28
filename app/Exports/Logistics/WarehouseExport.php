<?php

namespace App\Exports\Logistics;

use App\Exports\BaseExport;
use App\Models\Admin\Logistics\Warehouse;

class WarehouseExport extends BaseExport
{
    public static function all(): self
    {
        $records = Warehouse::orderBy('name')->get();

        return new self($records, [
            'الاسم', 'الكود', 'العنوان', 'ملاحظات', 'تاريخ الإنشاء',
        ], [
            'name', 'code', 'address', 'notes',
            fn($r) => $r->created_at?->format('Y-m-d'),
        ]);
    }
}
