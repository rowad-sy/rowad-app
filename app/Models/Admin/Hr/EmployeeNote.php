<?php

namespace App\Models\Admin\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeNote extends Model
{
    protected $table = 'hr_employee_notes';

    protected $fillable = ['employee_id', 'user_id', 'note'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
