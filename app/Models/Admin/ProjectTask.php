<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use SoftDeletes;

    protected $table = 'project_tasks';

    protected $fillable = [
        'title', 'purpose', 'start_date', 'end_date',
        'needs_media_coverage', 'needs_costs', 'costs_details',
        'needs_equipment', 'equipment_details',
        'assigned_to', 'created_by', 'center_id',
        'status', 'executed', 'not_executed_reason',
        'has_delay', 'delay_reason',
        'media_coverage_done', 'no_media_coverage_reason',
        'execution_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'needs_media_coverage' => 'boolean',
            'needs_costs' => 'boolean',
            'needs_equipment' => 'boolean',
            'executed' => 'boolean',
            'has_delay' => 'boolean',
            'media_coverage_done' => 'boolean',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
