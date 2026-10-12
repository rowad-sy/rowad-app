<?php

namespace App\Models\Admin\Logistics;

use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverNote extends Model
{
    use RecordsWorkflow;

    protected $table = 'handover_notes';

    protected $fillable = [
        'purchase_request_id', 'grn_number', 'handover_date',
        'delivered_by_user_id', 'delivered_by_name', 'delivered_by_position',
        'received_by_user_id', 'received_by_name', 'received_by_position',
        'note', 'items', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'handover_date' => 'date',
            'items' => 'array',
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
