<?php

namespace App\Imports\Logistics;

use App\Models\Admin\Logistics\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PurchaseRequestImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $grouped = $rows->groupBy(fn($row) => $row['رقم_الطلب'] ?? uniqid('import_'));

        foreach ($grouped as $requestNumber => $group) {
            $first = $group->first();

            $lastRequest = PurchaseRequest::where('request_number', 'like', 'PR-' . date('Y') . '-%')
                ->orderBy('id', 'desc')
                ->first();
            $lastNumber = $lastRequest ? (int) substr($lastRequest->request_number, -5) : 0;

            $totalPrice = 0;
            foreach ($group as $row) {
                $totalPrice += (float) ($row['سعر_الوحدة'] ?? 0) * (int) ($row['الكمية'] ?? 1);
            }

            $purchaseRequest = PurchaseRequest::create([
                'request_number' => 'PR-' . date('Y') . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'expected_total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $first['ملاحظات'] ?? null,
            ]);

            foreach ($group as $row) {
                $qty = (int) ($row['الكمية'] ?? 1);
                $price = (float) ($row['سعر_الوحدة'] ?? 0);
                $purchaseRequest->items()->create([
                    'description' => $row['الوصف'] ?? '',
                    'quantity' => $qty,
                    'unit' => $row['الوحدة'] ?? 'قطعة',
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                    'notes' => $row['ملاحظات_البند'] ?? null,
                ]);
            }
        }
    }
}
