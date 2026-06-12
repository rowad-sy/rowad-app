<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
