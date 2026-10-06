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
        'request_number', 'request_type', 'user_id', 'specifications', 'quantity', 'unit',
        'expected_unit_price', 'expected_total_price',
        'center_id', 'project_id', 'pr_date', 'required_date', 'management_unit',
        'status', 'notes', 'signature_path',
        'budget_number', 'locked_at', 'locked_by',
        'refer_to_logistics_id', 'refer_to_direct_manager_id', 'refer_to_pm2_id', 'refer_to_finance_id',
        'refer_to_executive_id', 'refer_to_approver1_id', 'refer_to_approver2_id', 'refer_to_approver3_id',
        'finance_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expected_unit_price' => 'decimal:2',
            'expected_total_price' => 'decimal:2',
            'pr_date' => 'date',
            'required_date' => 'date',
            'locked_at' => 'datetime',
            'approved_at' => 'datetime',
            'finance_at' => 'datetime',
        ];
    }

    /*
     * الدورة الجديدة (2026-10): مدير المشروع يعبئ الطلب كاملا ويحيل ←
     * موافقة 1 (المدير المباشر، عادة مدير المشاريع) بتوقيع صورة ←
     * موافقة 2 (المالية) بتوقيع ← موافقة 3 (التنفيذي) بتوقيع ويختار اللوجستي ←
     * اللوجستي يعلّم بنود التنفيذ ويطبع PDF/Excel دون توقيع.
     * الاعتماد محصور بالمحال اليه حصرا — حتى super-admin لا يعتمد.
     */
    public const STATUSES = [
        'review' => 'بانتظار موافقة المدير المباشر',
        'approved1' => 'بانتظار موافقة المالية',
        'approved2' => 'بانتظار موافقة المدير التنفيذي',
        'approved' => 'معتمد — بانتظار تنفيذ اللوجستي',
        'executed' => 'منفذ',
        'rejected' => 'مرفوض',
    ];

    public const CURRENCIES = [
        'USD' => 'دولار أمريكي ($)',
        'SYP' => 'ليرة سورية (SYP)',
    ];

    public const TYPES = [
        'purchase' => 'طلب شراء',
        'maintenance' => 'طلب صيانة',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->request_type] ?? $this->request_type;
    }

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

    public function approver1User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_approver1_id');
    }

    public function approver2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_approver2_id');
    }

    public function approver3User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_approver3_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(PurchaseRequestSignature::class, 'purchase_request_id');
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
     * الإجماليات حسب العملة (USD / SYP).
     */
    public function totalsByCurrency(): array
    {
        $totals = ['USD' => 0.0, 'SYP' => 0.0];

        foreach ($this->items as $item) {
            $totals[strtoupper((string) $item->currency)] += (float) $item->total_price;
        }

        return $totals;
    }

    public function executedItemsCount(): int
    {
        return $this->items()->whereNotNull('executed_at')->count();
    }

    /*
     * الطلب مقفول: بعد توقيع المدير التنفيذي لا يقبل تعديلا ولا حجفا.
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null
            || in_array($this->status, ['approved', 'executed'], true);
    }

    /*
     * الخطوة الحالية للمستلم الحصري.
     */
    public function currentStep(): ?string
    {
        return match ($this->status) {
            'review' => 'approver1',
            'approved1' => 'approver2',
            'approved2' => 'approver3',
            'approved' => 'logistics',
            default => null,
        };
    }

    public function stepRecipientId(): ?int
    {
        return match ($this->status) {
            'review' => $this->refer_to_approver1_id,
            'approved1' => $this->refer_to_approver2_id,
            'approved2' => $this->refer_to_approver3_id,
            'approved' => $this->refer_to_logistics_id,
            default => null,
        };
    }

    /*
     * حصرية الرؤية: المنشئ + المستلمون الحاليون + من مر الطلب بيده.
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

        return in_array($userId, array_filter([
            (int) $this->refer_to_approver1_id,
            (int) $this->refer_to_approver2_id,
            (int) $this->refer_to_approver3_id,
            (int) $this->refer_to_logistics_id,
            (int) $this->refer_to_direct_manager_id,
            (int) $this->refer_to_pm2_id,
            (int) $this->refer_to_finance_id,
            (int) $this->refer_to_executive_id,
        ]), true);
    }
}
