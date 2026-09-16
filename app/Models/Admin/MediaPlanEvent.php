<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlanEvent extends Model
{
    protected $table = 'media_plan_events';

    protected $fillable = [
        'media_plan_id', 'event_date', 'office', 'event_name', 'day',
        'event_time', 'location', 'responsible_user_id', 'summary',
        'coverage_type', 'notes',
        'execution_status', 'execution_note', 'execution_by', 'execution_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'execution_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class, 'media_plan_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function executionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'execution_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MediaPlanComment::class, 'media_plan_event_id')->orderBy('created_at');
    }
}