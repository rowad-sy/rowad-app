<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementPlanRecipient extends Model
{
    protected $table = 'movement_plan_recipients';

    protected $fillable = [
        'movement_plan_id', 'user_id', 'role_label', 'note',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MovementPlan::class, 'movement_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}