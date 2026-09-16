<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivity extends Model
{
    protected $table = 'project_activities';

    protected $fillable = [
        'project_id', 'center_id', 'responsible', 'activity_date',
        'beneficiary', 'male_count', 'female_count', 'progress', 'obstacles', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'male_count' => 'integer',
            'female_count' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalBeneficiariesAttribute(): int
    {
        return (int) $this->male_count + (int) $this->female_count;
    }
}