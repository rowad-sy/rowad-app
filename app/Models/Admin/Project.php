<?php

namespace App\Models\Admin;

use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\TrainingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = ['name', 'description'];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_project')->withTimestamps();
    }

    public function academicLevels(): HasMany
    {
        return $this->hasMany(AcademicLevel::class)->orderBy('sort_order');
    }

    public function trainingPlans(): HasMany
    {
        return $this->hasMany(TrainingPlan::class)->orderBy('start_date');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }
}
