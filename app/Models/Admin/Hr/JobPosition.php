<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    protected $table = 'hr_job_positions';

    protected $fillable = ['title_ar', 'title_en', 'description_ar', 'description_en'];
}
