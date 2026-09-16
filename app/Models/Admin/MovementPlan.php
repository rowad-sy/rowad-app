<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementPlan extends Model
{
    use RecordsWorkflow, ManagesReferrals;

    protected $table = 'movement_plans';

    protected $fillable = [
        'request_number', 'created_by', 'center_id', 'project_id',
        'movement_date', 'departure_time', 'return_time',
        'from_location', 'to_location', 'purpose', 'notes',
        'refer_to_movement_officer_id', 'refer_to_pm2_id', 'assigned_by', 'assigned_at',
        'completed_at', 'status', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
     * دورة خطة الحركة:
     * مدير المشروع ينشئ → إدارة المشاريع توافق وتحيل لمسؤول الحركة →
     * مسؤول الحركة يحدد المستفيدين/المتابعين (سائق/مدير مركز/لوجستي...) → المتابعة → منجزة
     */
    public const STATUSES = [
        'review' => 'بانتظار مراجعة إدارة المشاريع',
        'approved' => 'أُحيلت لمسؤول الحركة',
        'assigned' => 'قيد المتابعة',
        'completed' => 'منجزة',
        'rejected' => 'مرفوضة',
        'cancelled' => 'ملغاة',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function movementOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_movement_officer_id');
    }

    public function projectsManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_pm2_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MovementPlanRecipient::class, 'movement_plan_id');
    }

    /*
     * حصرية الرؤية (الإحالات): المنشئ + المستلَمون الحاليون (إدارة
     * المشاريع / مسؤول الحركة / المتابِعون) هم من يرون الخطة فقط.
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
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_movement_officer_id,
        ], true);
    }

    /*
     * هل المستخدم من جهات المتابعة (المستفيدين)؟
     */
    public function isRecipientOfUserId(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return $this->recipients()->where('user_id', $userId)->exists();
    }
}