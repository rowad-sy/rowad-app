<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'model',
        'model_name',
        'model_id',
        'event',
        'description',
        'old_values',
        'new_values',
        'hash',
        'prev_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function changes(): HasMany
    {
        return $this->hasMany(AuditChange::class, 'audit_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getShortModelAttribute(): string
    {
        $parts = explode('\\', $this->model);
        return end($parts);
    }
}
