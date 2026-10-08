<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
    protected $table = 'hr_work_schedules';

    protected $fillable = ['employee_id', 'day_of_week', 'start_time', 'end_time', 'is_day_off'];

    protected function casts(): array
    {
        return [
            'is_day_off' => 'boolean',
            'start_time' => 'string',
            'end_time' => 'string',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
