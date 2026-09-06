<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowAction extends Model
{
    protected $table = 'workflow_actions';

    protected $fillable = [
        'workable_type', 'workable_id',
        'action', 'from_user_id', 'to_user_id', 'note', 'status',
    ];

    public function workable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}