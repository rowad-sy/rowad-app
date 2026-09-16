<?php

namespace App\Models\Admin\Physiotherapy;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysioSession extends Model
{
    protected $table = 'physio_sessions';

    protected $fillable = [
        'patient_id', 'session_date', 'session_number',
        'what_done', 'therapist_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'session_number' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PhysioPatient::class, 'patient_id');
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}