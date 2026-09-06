<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOffering extends Model
{
    use SoftDeletes;

    protected $table = 'course_offerings';

    protected $fillable = [
        'course_id', 'period_id', 'instructor_id',
        'name_ar', 'session_time', 'start_date', 'end_date', 'capacity', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'instructor_id');
    }
}
