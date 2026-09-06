<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectGrade extends Model
{
    protected $table = 'student_subject_grades';

    protected $fillable = [
        'student_enrollment_id', 'subject_id', 'grade',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
