<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlan extends Model
{
    use ManagesReferrals, RecordsWorkflow;

    protected $table = 'media_plans';

    protected $fillable = [
        'month_date', 'center_id', 'project_id', 'created_by', 'note',
        'status', 'refer_to_direct_manager_id', 'refer_to_pm2_id',
        'refer_to_media_manager_id', 'refer_to_media_officer_id',
        'locked_at', 'locked_by', 'approved_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'month_date' => 'date',
            'locked_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /*
     * دورة الخطة الإعلامية:
     * مسؤول المشروع ينشئ → المدير المباشر يوافق (هنا تُقفل) → مدير المشاريع →
     * مدير الإعلام → المسؤول الإعلامي في المركز (نفّذ/لم ينفّذ) → منجزة.
     */
    public const STATUSES = [
        'review' => 'بانتظار موافقة المدير المباشر',
        'manager_approved' => 'وافق المدير المباشر',
        'pm2_approved' => 'وافق مدير المشاريع',
        'media_manager_approved' => 'وافق مدير الإعلام',
        'executing' => 'قيد التنفيذ',
        'executed' => 'منجزة',
        'rejected' => 'مرفوضة',
    ];

    public const EXECUTION_STATUSES = [
        'executed' => 'نُفِّذت',
        'not_executed' => 'لم تُنفَّذ',
    ];

    /*
     * تُقفل الخطة بعد موافقة المدير المباشر (لا يُعدَّل محتواها بعدها).
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null
            || in_array($this->status, ['manager_approved', 'pm2_approved', 'media_manager_approved', 'executing', 'executed'], true)
            || $this->status === 'rejected';
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_direct_manager_id');
    }

    public function pm2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_pm2_id');
    }

    public function mediaManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_media_manager_id');
    }

    public function mediaOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_media_officer_id');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MediaPlanEvent::class, 'media_plan_id')->orderBy('event_date')->orderBy('event_time');
    }

    public function conflicts(): array
    {
        $seen = [];

        foreach ($this->events as $event) {
            $key = $event->event_date->format('Y-m-d') . '|' . $event->event_time;
            $seen[$key][] = $event->event_name;
        }

        return collect($seen)->filter(fn ($names) => count($names) > 1)->all();
    }

    /*
     * حصرية الرؤية (الإحالات): المنشئ + المستلَمون الحاليون + المشاركون
     * في الدورة هم من يرون الخطة فقط.
     */
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
            (int) $this->refer_to_direct_manager_id,
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_media_manager_id,
            (int) $this->refer_to_media_officer_id,
        ], true);
    }
}