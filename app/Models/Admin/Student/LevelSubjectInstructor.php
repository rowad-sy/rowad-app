<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelSubjectInstructor extends Model
{
    protected $table = 'level_subject_instructors';

    protected $fillable = [
        'academic_level_id', 'subject_id', 'instructor_id', 'is_main',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'instructor_id');
    }
}
