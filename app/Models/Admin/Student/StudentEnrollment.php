<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id', 'course_id', 'period_id',
        'enrollment_date', 'status', 'grade', 'is_certificate_eligible',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'grade' => 'decimal:2',
            'is_certificate_eligible' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
