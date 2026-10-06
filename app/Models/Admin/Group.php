<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public const KIND_GROUP = 'group';

    public const KIND_ROLE = 'role';

    protected $table = 'groups';

    protected $fillable = ['name', 'description', 'kind'];

    public function isRole(): bool
    {
        return $this->kind === self::KIND_ROLE;
    }

    public function scopeRoles(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_ROLE);
    }

    public function scopeGroups(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_GROUP);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot(['center_id', 'project_id', 'cohort_id'])
            ->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
