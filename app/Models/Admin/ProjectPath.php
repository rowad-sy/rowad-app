<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPath extends Model
{
    protected $table = 'project_paths';

    protected $fillable = ['name', 'code', 'description', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('COALESCE(project_paths.sort_order, 999999) ASC')
            ->orderBy('project_paths.name')
            ->orderBy('project_paths.id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'path_id');
    }
}
