<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContact extends Model
{
    protected $table = 'hr_employee_contacts';

    protected $fillable = ['employee_id', 'type', 'value', 'is_primary'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
