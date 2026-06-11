<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = ['name', 'description'];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class)->withTimestamps();
    }
}
