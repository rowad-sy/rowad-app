<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaPlanComment extends Model
{
    protected $table = 'media_plan_comments';

    protected $fillable = [
        'media_plan_event_id', 'user_id', 'comment',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(MediaPlanEvent::class, 'media_plan_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}