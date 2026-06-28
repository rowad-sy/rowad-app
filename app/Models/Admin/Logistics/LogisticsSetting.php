<?php

namespace App\Models\Admin\Logistics;

use Illuminate\Database\Eloquent\Model;

class LogisticsSetting extends Model
{
    protected $table = 'logistics_settings';

    protected $fillable = ['key', 'value'];
}
