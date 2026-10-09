<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdDesignRequest extends Model
{
    use ManagesReferrals, RecordsWorkflow;

    protected $table = 'ad_design_requests';

    protected $fillable = [
        'title', 'description', 'center_id', 'project_id', 'created_by', 'due_date',
        'status', 'refer_to_pm2_id', 'refer_to_rowaduna_id', 'refer_to_designer_id',
        'refer_to_publisher_id', 'design_url', 'design_note', 'revision_note',
        'publish_links', 'approved_by', 'approved_at', 'published_by', 'published_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'publish_links' => 'array',
        ];
    }

    public const STATUSES = [
        'pm2_review' => 'بانتظار موافقة مدير المشاريع',
        'rowaduna_review' => 'بانتظار مسؤول روادنا',
        'designing' => 'بانتظار تنفيذ التصميم (لدى المصمم)',
        'ready_for_review' => 'بانتظار مراجعة صاحب الطلب',
        'to_publish' => 'معتمد — بانتظار النشر',
        'published' => 'منشور',
        'rejected' => 'مرفوض',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function centers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'ad_design_request_center')
            ->withTimestamps()
            ->orderBy('centers.name');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pm2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_pm2_id');
    }

    public function rowadunaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_rowaduna_id');
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_designer_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_publisher_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /*
     * محتوى الطلب يُقفل بعد موافقة مدير المشاريع (أول حلقة اعتماد).
     */
    public function isLocked(): bool
    {
        return in_array($this->status, ['rowaduna_review', 'designing', 'ready_for_review', 'to_publish', 'published', 'rejected'], true);
    }

    public function stepForStatus(): ?string
    {
        return match ($this->status) {
            'pm2_review' => 'pm2',
            'rowaduna_review' => 'rowaduna',
            'designing' => 'designer',
            'ready_for_review' => 'creator',
            'to_publish' => 'publisher',
            default => null,
        };
    }

    public function isVisibleToUserId(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ((int) $this->created_by === $userId) {
            return true;
        }

        if ($this->isCurrentRecipient($userId)) {
            return true;
        }

        return in_array($userId, [
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_rowaduna_id,
            (int) $this->refer_to_designer_id,
            (int) $this->refer_to_publisher_id,
            (int) $this->approved_by,
            (int) $this->published_by,
        ], true);
    }
}
