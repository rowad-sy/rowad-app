<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCard extends Model
{
    use ManagesReferrals, RecordsWorkflow;

    protected $table = 'event_cards';

    public const STATUSES = [
        'review' => 'بانتظار موافقة المحال إليه',
        'approved' => 'موافق عليها — بانتظار الاعتماد',
        'finalized' => 'معتمدة',
        'rejected' => 'مرفوضة',
    ];

    protected $fillable = [
        'name', 'project_id', 'center_id', 'event_date', 'location', 'organizer',
        'presenter', 'expected_attendance', 'objectives',
        'schedule_place', 'schedule_date', 'schedule_time',
        'tasks_projects', 'tasks_operations', 'tasks_mel',
        'content_items', 'logistics_items', 'purchases_items', 'media_items',
        'hr_notes', 'transport_items', 'budget_items', 'budget_total',
        'post_evaluation', 'status', 'created_by', 'referred_user_id',
        'approved_by', 'approved_at', 'finalized_by', 'finalized_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'schedule_date' => 'date',
            'expected_attendance' => 'integer',
            'content_items' => 'array',
            'logistics_items' => 'array',
            'purchases_items' => 'array',
            'media_items' => 'array',
            'transport_items' => 'array',
            'budget_items' => 'array',
            'budget_total' => 'decimal:2',
            'approved_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'review' => 'bg-warning text-dark',
            'approved' => 'bg-info text-dark',
            'finalized' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /*
     * بعد الموافقة تُقفل البطاقة عن التعديل (نفس فلسفة الخطة الإعلامية).
     */
    public function isLocked(): bool
    {
        return in_array($this->status, ['approved', 'finalized', 'rejected'], true);
    }

    public function isVisibleToUserId(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return (int) $this->created_by === $userId
            || (int) $this->referred_user_id === $userId
            || (int) $this->approved_by === $userId
            || (int) $this->finalized_by === $userId
            || $this->isCurrentRecipient($userId);
    }
}
