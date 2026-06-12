<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warning extends Model
{
    protected $table = 'hr_warnings';

    protected $fillable = ['employee_id', 'date', 'reason', 'level', 'is_folded', 'fold_reason', 'folded_at'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_folded' => 'boolean',
            'folded_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
