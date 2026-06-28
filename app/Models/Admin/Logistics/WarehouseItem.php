<?php

namespace App\Models\Admin\Logistics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseItem extends Model
{
    protected $table = 'logistics_warehouse_items';

    protected $fillable = [
        'warehouse_id', 'name', 'description', 'quantity', 'unit', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
