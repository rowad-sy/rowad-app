<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $table = 'subjects';

    protected $fillable = [
        'course_id', 'name_ar', 'name_en', 'hours', 'weight', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'subject_id');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(AcademicLevel::class, 'level_subjects')
            ->withTimestamps();
    }

    public function exams(): HasMany
    {
        return $this->hasMany(SubjectExam::class)->orderBy('sort_order');
    }
}
