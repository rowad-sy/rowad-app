<?php

namespace App\Models\Admin\Logistics;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestSignature extends Model
{
    public const ROLES = [
        'requested_by' => 'تم الطلب من قبل',
        'approver1' => 'موافقة المدير المباشر',
        'approver2' => 'موافقة قسم الموارد المالية',
        'approver3' => 'موافقة المدير التنفيذي',
    ];

    protected $table = 'purchase_request_signatures';

    protected $fillable = [
        'purchase_request_id', 'role', 'user_id', 'name', 'position', 'signed_at', 'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
