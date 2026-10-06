<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Center extends Model
{
    protected $table = 'centers';

    protected $fillable = ['name', 'code', 'address', 'phone'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'center_project')->withTimestamps();
    }
}
