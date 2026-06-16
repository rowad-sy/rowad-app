<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
