<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'project_id', 'name_ar', 'name_en', 'description', 'duration',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function periods(): BelongsToMany
    {
        return $this->belongsToMany(Period::class, 'course_period')
            ->withTimestamps();
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('sort_order');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class)->orderBy('id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(AcademicLevel::class, 'course_id')->orderBy('sort_order');
    }
}
