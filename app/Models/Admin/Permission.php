<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'user_id', 'group_id',
        'model_names', 'model_id',
        'center_id', 'project_id',
        'can_view', 'can_create', 'can_edit', 'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'model_names' => 'array',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
