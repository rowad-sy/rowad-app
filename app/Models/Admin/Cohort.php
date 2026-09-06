<?php

namespace App\Models\Admin;

use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cohort extends Model
{
    use SoftDeletes;

    protected $table = 'cohorts';

    protected $fillable = [
        'project_id', 'manager_id', 'name', 'shift', 'code', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'cohort_id');
    }
}
