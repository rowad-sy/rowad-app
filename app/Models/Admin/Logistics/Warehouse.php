<?php

namespace App\Models\Admin\Logistics;

use App\Models\Admin\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_warehouses';

    protected $fillable = ['name', 'center_id', 'notes'];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseItem::class, 'warehouse_id');
    }
}
