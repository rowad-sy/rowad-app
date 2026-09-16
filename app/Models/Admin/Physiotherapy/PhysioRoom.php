<?php

namespace App\Models\Admin\Physiotherapy;

use App\Models\Admin\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysioRoom extends Model
{
    protected $table = 'physio_rooms';

    protected $fillable = [
        'center_id', 'name', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(PhysioPatient::class, 'room_id');
    }
}