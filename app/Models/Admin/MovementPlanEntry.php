<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementPlanEntry extends Model
{
    protected $table = 'movement_plan_entries';

    protected $fillable = [
        'movement_plan_id', 'movement_date', 'departure_time', 'return_time',
        'from_location', 'to_location', 'purpose', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MovementPlan::class, 'movement_plan_id');
    }
}
