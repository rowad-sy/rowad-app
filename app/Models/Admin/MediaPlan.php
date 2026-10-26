<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Helpers\PermissionHelper;
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
        'status', 'workflow_version', 'refer_to_direct_manager_id', 'refer_to_pm2_id',
        'refer_to_rowaduna_id', 'refer_to_media_manager_id', 'refer_to_media_officer_id',
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
     * دورة الخطة الإعلامية (قسم روادنا):
     * المنشئ (مسؤول/مدير مشروع) → المدير المباشر — إن لم يكن المنشئ هو مدير
     * المشروع (هنا تُقفل) → مدير المشاريع → مسؤول روادنا (يُسند كل فعالية
     * لمراسل) → التغطية والمونتاج والنشر على مستوى الفعاليات → منجزة.
     */
    public const STATUSES = [
        'simple_review' => 'بانتظار موافقة مدير المشاريع',
        'simple_rowaduna' => 'بانتظار مسؤول روادنا لإسناد المراسلين',
        'simple_in_progress' => 'بانتظار إغلاق مسؤول روادنا',
        'simple_completed' => 'منتهية',
        'simple_rejected' => 'مرفوضة',
        'review' => 'بانتظار موافقة المدير المباشر',
        'pm2_review' => 'بانتظار موافقة مدير المشاريع',
        'manager_approved' => 'بانتظار موافقة مدير المشاريع',
        'pm2_approved' => 'بانتظار موافقة مسؤول روادنا',
        'rowaduna_review' => 'بانتظار مسؤول روادنا — إسناد التغطيات',
        'in_progress' => 'قيد التنفيذ — تغطيات ومونتاج ونشر',
        'executed' => 'منجزة',
        'rejected' => 'مرفوضة',
        'media_manager_approved' => 'بانتظار مسؤول روادنا',
        'executing' => 'قيد التنفيذ',
    ];

    public function isSimple(): bool
    {
        return $this->workflow_version === 'simple';
    }

    public const EXECUTION_STATUSES = [
        'executed' => 'نُفِّذت',
        'not_executed' => 'لم تُنفَّذ',
    ];

    public const ACTIVE_STATUSES = [
        'simple_review', 'simple_rowaduna', 'simple_in_progress',
        'review', 'pm2_review', 'manager_approved', 'pm2_approved', 'rowaduna_review', 'in_progress',
    ];

    /*
     * تُقفل الخطة بعد أول موافقة في سلسلتها (المدير المباشر أو مدير المشاريع
     * إذا كان المنشئ هو المدير المباشر) — لا يُعدَّل محتواها بعدها.
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null
            || ($this->isSimple() && in_array($this->status, [
                'simple_rowaduna', 'simple_in_progress', 'simple_completed', 'simple_rejected',
            ], true))
            || in_array($this->status, [
                'manager_approved', 'pm2_approved', 'media_manager_approved',
                'rowaduna_review', 'in_progress', 'executing', 'executed', 'rejected',
            ], true);
    }

    public function stepForStatus(): ?string
    {
        return match ($this->status) {
            'simple_review' => 'projects_manager',
            'simple_rowaduna', 'simple_in_progress' => 'rowaduna',
            'review' => 'direct_manager',
            'pm2_review', 'manager_approved' => 'pm2',
            'pm2_approved', 'media_manager_approved', 'rowaduna_review' => 'rowaduna',
            'executing', 'in_progress' => null,
            default => null,
        };
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

    public function rowadunaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_rowaduna_id');
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

    /** Overlapping event intervals inside this plan (simple workflow). */
    public function scheduleConflicts(): array
    {
        $events = $this->events()->get();
        $conflicts = [];

        foreach ($events as $index => $event) {
            $start = substr((string) $event->event_time, 0, 5);
            $end = $event->event_end_time ? substr((string) $event->event_end_time, 0, 5) : $start;

            foreach ($events->slice($index + 1) as $other) {
                if ($event->event_date->toDateString() !== $other->event_date->toDateString()) {
                    continue;
                }

                $otherStart = substr((string) $other->event_time, 0, 5);
                $otherEnd = $other->event_end_time ? substr((string) $other->event_end_time, 0, 5) : $otherStart;

                if ($start < $otherEnd && $end > $otherStart) {
                    $conflicts[$event->id] = true;
                    $conflicts[$other->id] = true;
                }
            }
        }

        return $conflicts;
    }

    /*
     * حصرية الرؤية (الإحالات): المنشئ + المستلمون الحاليون + حاملو العهدة
     * في الدورة + حاملو عهدات الفعاليات (مراسل/ناشر/مراجع) هم من يرونها.
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

        $user = User::find($userId);
        if ($user && PermissionHelper::canAccessRecord($user, self::class, 'view', $this)) {
            return true;
        }

        if ($this->isSimple() && $user && Group::roles()->where('role_key', Group::ROLE_PROJECTS_MANAGER)
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))->exists()
            && PermissionHelper::canAccessRecord($user, self::class, 'edit', $this)) {
            return true;
        }

        if (in_array($userId, [
            (int) $this->refer_to_direct_manager_id,
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_rowaduna_id,
            (int) $this->refer_to_media_manager_id,
            (int) $this->refer_to_media_officer_id,
        ], true)) {
            return true;
        }

        return $this->events()
            ->where(fn ($q) => $q->where('refer_to_reporter_id', $userId)
                ->orWhere('refer_to_publisher_id', $userId)
                ->orWhere('refer_to_reviewer_id', $userId))
            ->exists();
    }

    /*
     * هل وصلت كل الفعاليات إلى حالة نهائية (منشورة أو لم تُغطَّ)?
     */
    public function allEventsClosed(): bool
    {
        return $this->events()->get()->every(fn ($event) => $event->isClosed());
    }

    /*
     * الإغلاق التلقائي للخطة لا يتم إلا عند نشر كل الفعاليات نهائياً؛
     * الفعاليات «لم تتم تغطيتها» تحتاج قراراً يدوياً (إعادة جدولة أو إغلاق).
     */
    public function allEventsPublished(): bool
    {
        return $this->events()->count() > 0
            && $this->events()->where('publish_status', '!=', 'published')->doesntExist();
    }
}
