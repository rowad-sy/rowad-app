<?php

namespace App\Imports\Logistics;

use App\Models\Admin\Logistics\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehouseImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            Warehouse::create([
                'name' => $row['الاسم'] ?? '',
                'code' => $row['الكود'] ?? null,
                'address' => $row['العنوان'] ?? null,
                'notes' => $row['ملاحظات'] ?? null,
            ]);
        }
    }
}
