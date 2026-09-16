<?php

namespace App\Models\Admin\Physiotherapy;

use App\Models\Admin\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysioPatient extends Model
{
    protected $table = 'physio_patients';

    public const GENDERS = [
        'male' => 'ذكر',
        'female' => 'أنثى',
    ];

    protected $fillable = [
        'center_id', 'name', 'gender', 'birth_date', 'medical_history',
        'phone', 'address', 'is_transferred', 'transferred_at',
        'registration_date', 'therapist_id', 'room_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'transferred_at' => 'date',
            'registration_date' => 'date',
            'is_transferred' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(PhysioRoom::class, 'room_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PhysioSession::class, 'patient_id')->orderBy('session_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}