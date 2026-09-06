<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingPlanLesson extends Model
{
    protected $table = 'training_plan_lessons';

    protected $fillable = [
        'training_plan_id', 'academic_level_id', 'subject_id', 'instructor_id',
        'week_number', 'day_of_week', 'start_time', 'end_time', 'lesson_name', 'location',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'day_of_week' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
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
