<?php

namespace App\Models\Admin\Logistics;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Concerns\ManagesReferrals;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes, RecordsWorkflow, ManagesReferrals;

    protected $table = 'logistics_purchase_requests';

    protected $fillable = [
        'request_number', 'user_id', 'specifications', 'quantity', 'unit',
        'expected_unit_price', 'expected_total_price',
        'center_id', 'project_id', 'status', 'notes', 'signature_path',
        'budget_number', 'locked_at', 'locked_by',
        'refer_to_logistics_id', 'refer_to_direct_manager_id', 'refer_to_pm2_id', 'refer_to_finance_id',
        'refer_to_executive_id', 'finance_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expected_unit_price' => 'decimal:2',
            'expected_total_price' => 'decimal:2',
            'locked_at' => 'datetime',
            'approved_at' => 'datetime',
            'finance_at' => 'datetime',
        ];
    }

    /*
     * الحالات المعتمدة (قسم 14.6): pending / priced / pm_approved /
     * pm2_approved / approved / rejected / executed
     */
    public const STATUSES = [
        'pending' => 'بانتظار التسعير',
        'priced' => 'مُسعَّر',
        'pm_approved' => 'وافق مدير المشروع',
        'pm2_approved' => 'وافق مدير المشاريع',
        'finance_approved' => 'وافق المسؤول المالي',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'executed' => 'منفَّذ',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseRequestApproval::class, 'purchase_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class, 'purchase_request_id');
    }

    public function logisticsStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_logistics_id');
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_direct_manager_id');
    }

    public function pm2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_pm2_id');
    }

    public function financeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_finance_id');
    }

    public function executiveUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_executive_id');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->items()->sum('total_price');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    /*
     * الطلب مقفول نهائياً: بمجرد اعتماد المدير التنفيذي لا تقبل أي
     * تعديلات/حذف. (نُقل القفل من موافقة مدير المشروع إلى الاعتماد النهائي.)
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null
            || in_array($this->status, ['approved', 'executed'], true);
    }

    /*
     * المستلم الحالي للخطوة حسب الحالة — تُستخدم للتحقق في الكونترولر
     * (فحص صلاحية الطرف المستقبِل عند كل إحالة).
     */
    public function stepRecipientId(): ?int
    {
        return match ($this->status) {
            'pending' => $this->refer_to_logistics_id,
            'priced' => $this->refer_to_direct_manager_id,
            'pm_approved' => $this->refer_to_pm2_id,
            'pm2_approved' => $this->refer_to_finance_id,
            'finance_approved' => $this->refer_to_executive_id,
            default => null,
        };
    }

    /*
     * حصرية الرؤية (الإحالات): المنشئ + المستلَمون الحاليون (عبر
     * الإحالات النشطة أو الأعمدة القديمة المتوافقة) هم من يرون الطلب فقط.
     */
    public function isVisibleToUserId(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ((int) $this->user_id === $userId) {
            return true;
        }

        if ($this->isCurrentRecipient($userId)) {
            return true;
        }

        return in_array($userId, [
            (int) $this->refer_to_logistics_id,
            (int) $this->refer_to_direct_manager_id,
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_finance_id,
            (int) $this->refer_to_executive_id,
        ], true);
    }
}
