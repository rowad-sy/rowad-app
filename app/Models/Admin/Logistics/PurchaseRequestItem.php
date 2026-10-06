<?php

namespace App\Models\Admin\Logistics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    protected $table = 'logistics_purchase_request_items';

    public const UNITS = [
        'قطعة', 'صندوق', 'كرتونة', 'ماعون', 'عبوة', 'كيس', 'طقم', 'لفة', 'دزينة', 'زوج',
        'زجاجة', 'علبة', 'غرام', 'كيلو غرام', 'طن', 'رطل', 'أوقية',
        'متر', 'سنتيميتر', 'ميليميتر', 'بوصة', 'قدم', 'متر مربع', 'سنتيميتر مربع',
        'ليتر', 'ميليلتر', 'متر مكعب', 'سنتيميتر مكعب', 'برميل',
    ];

    protected $fillable = [
        'purchase_request_id', 'description', 'quantity', 'unit', 'currency',
        'unit_price', 'total_price', 'notes', 'budget_line', 'executed_at', 'executed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'executed_at' => 'datetime',
        ];
    }

    public function executor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'executed_by');
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }
}
