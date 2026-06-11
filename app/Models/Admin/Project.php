<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = ['name', 'description'];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_project')->withTimestamps();
    }
}
