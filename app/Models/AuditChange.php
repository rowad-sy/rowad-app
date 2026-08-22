<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'audit_log_id',
        'field',
        'label',
        'old_value',
        'new_value',
        'value_type',
        'is_masked',
    ];

    protected function casts(): array
    {
        return [
            'is_masked' => 'boolean',
        ];
    }

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id');
    }
}
