<?php

namespace App\Models\Admin\Logistics;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_purchase_requests';

    protected $fillable = [
        'request_number', 'user_id', 'specifications', 'quantity', 'unit',
        'expected_unit_price', 'expected_total_price',
        'center_id', 'project_id', 'status', 'notes', 'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expected_unit_price' => 'decimal:2',
            'expected_total_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseRequestApproval::class, 'purchase_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class, 'purchase_request_id');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->items()->sum('total_price');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2);
    }
}
