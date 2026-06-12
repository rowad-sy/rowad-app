<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    protected $table = 'hr_salaries';

    protected $fillable = [
        'employee_id', 'currency', 'salary_unit',
        'base_salary', 'study_allowance', 'marriage_allowance', 'experience_allowance',
        'transport_allowance', 'food_allowance', 'housing_allowance', 'mobile_allowance',
        'risk_allowance', 'overtime_rate', 'deduction', 'total_salary',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'study_allowance' => 'decimal:2',
            'marriage_allowance' => 'decimal:2',
            'experience_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'mobile_allowance' => 'decimal:2',
            'risk_allowance' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'deduction' => 'decimal:2',
            'total_salary' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
