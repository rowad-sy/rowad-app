<?php

namespace App\Models\Admin\Logistics;

use Illuminate\Database\Eloquent\Model;

class DeletedItem extends Model
{
    protected $table = 'logistics_deleted_items';

    protected $fillable = [
        'warehouse_id', 'item_name', 'description', 'quantity', 'unit', 'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
