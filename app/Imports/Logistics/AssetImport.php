<?php

namespace App\Imports\Logistics;

use App\Models\Admin\Logistics\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            Asset::create([
                'code' => $row['الكود'] ?? null,
                'name' => $row['الاسم'] ?? '',
                'value' => (float) ($row['القيمة'] ?? 0),
                'status' => 'new',
                'notes' => $row['ملاحظات'] ?? null,
            ]);
        }
    }
}
