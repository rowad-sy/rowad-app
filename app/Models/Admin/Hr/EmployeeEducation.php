<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    protected $table = 'hr_employee_educations';

    protected $fillable = ['employee_id', 'qualification', 'specialization', 'university', 'grade', 'graduation_year'];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
