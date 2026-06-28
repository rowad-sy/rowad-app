<?php

namespace App\Models\Admin\Logistics;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApprovalRule extends Model
{
    protected $table = 'logistics_approval_rules';

    protected $fillable = [
        'name', 'min_amount', 'max_amount', 'required_approvals', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'required_approvals' => 'integer',
        ];
    }

    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'logistics_approval_rule_user', 'rule_id', 'user_id')
            ->withTimestamps();
    }
}
