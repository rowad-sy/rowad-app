<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingPlan extends Model
{
    use SoftDeletes;

    protected $table = 'training_plans';

    protected $fillable = [
        'project_id', 'name_ar', 'name_en', 'start_date', 'end_date', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingPlanLesson::class, 'training_plan_id');
    }
}
