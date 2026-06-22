<?php

namespace App\Models\Admin\Tech;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechEquipment extends Model
{
    protected $fillable = [
        'name', 'type', 'serial_number',
        'condition', 'room',
        'center_id', 'project_id',
        'notes',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
