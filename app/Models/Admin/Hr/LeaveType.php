<?php

namespace App\Models\Admin\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name_ar', 'annual_days', 'requires_approval',
        'approver_ids', 'color', 'icon', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_days' => 'integer',
            'requires_approval' => 'boolean',
            'approver_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function approvers()
    {
        return User::whereIn('id', $this->approver_ids ?? [])->get();
    }
}
