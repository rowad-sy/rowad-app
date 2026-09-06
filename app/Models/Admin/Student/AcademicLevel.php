<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicLevel extends Model
{
    use SoftDeletes;

    protected $table = 'academic_levels';

    protected $fillable = [
        'project_id', 'course_id', 'name_ar', 'name_en', 'code', 'type', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'level_subjects')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('level_subjects.sort_order');
    }

    public function subjectInstructors(): HasMany
    {
        return $this->hasMany(LevelSubjectInstructor::class, 'academic_level_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingPlanLesson::class, 'academic_level_id');
    }
}
