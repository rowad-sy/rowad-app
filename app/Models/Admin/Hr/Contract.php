<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $table = 'hr_contracts';

    protected $fillable = [
        'employee_id', 'contract_type', 'job_position_id',
        'start_date', 'contract_start', 'contract_end', 'leave_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'contract_start' => 'date',
            'contract_end' => 'date',
            'leave_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }
}
