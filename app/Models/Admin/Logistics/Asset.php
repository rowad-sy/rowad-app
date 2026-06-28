<?php

namespace App\Models\Admin\Logistics;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_assets';

    protected $fillable = [
        'asset_code', 'name', 'type', 'center_id', 'project_id',
        'room_number', 'status', 'notes', 'recipient_id',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
