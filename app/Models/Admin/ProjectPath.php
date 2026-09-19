<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPath extends Model
{
    protected $table = 'project_paths';

    protected $fillable = ['name', 'code', 'description'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'path_id');
    }
}
