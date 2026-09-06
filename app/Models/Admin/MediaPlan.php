<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlan extends Model
{
    protected $table = 'media_plans';

    protected $fillable = [
        'month_date', 'center_id', 'project_id', 'created_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'month_date' => 'date',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MediaPlanEvent::class, 'media_plan_id')->orderBy('event_date')->orderBy('event_time');
    }

    public function conflicts(): array
    {
        $seen = [];

        foreach ($this->events as $event) {
            $key = $event->event_date->format('Y-m-d') . '|' . $event->event_time;
            $seen[$key][] = $event->event_name;
        }

        return collect($seen)->filter(fn ($names) => count($names) > 1)->all();
    }
}