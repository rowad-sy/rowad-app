<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/*
 * الإحالة الموحّدة (قسم الإحالات — النظام المرن):
 * كل كيان (طلب شراء / خطة حركة / خطة إعلامية) يحمل سلسلة إحالات عبر جدول
 * polymorphic واحد؛ "المستلَم الحالي" = أحدث إحالة نشطة لكل خطوة. وعاء
 * الترقية للمستقبل بدل الأعمدة المتناثرة refer_to_*.
 */
class Referral extends Model
{
    public const STATUSES = [
        'active' => 'نشطة',
        'done' => 'منجزة',
        'cancelled' => 'ملغاة',
    ];

    /*
     * أسماء الخطوات على مستوى النظام — تبقى نصاً حراً في DB لكننا نعتمد
     * هذه الأسماء كعقود ثابتة بين الموديلات والكونترولرات والواجهات.
     */
    public const STEPS = [
        'logistics' => 'التسعير (لوجستي)',
        'direct_manager' => 'المدير المباشر',
        'pm2' => 'مدير المشاريع',
        'finance' => 'المسؤول المالي',
        'executive' => 'المدير التنفيذي',
        'media_manager' => 'مدير الإعلام',
        'media_officer' => 'المسؤول الإعلامي',
        'movement_officer' => 'مسؤول الحركة',
        'recipient' => 'متابعة',
        'event_approve' => 'موافقة على بطاقة الفعالية',
        'event_finalize' => 'اعتماد بطاقة الفعالية',
        'rowaduna' => 'مسؤول روادنا',
        'reporter' => 'مراسل التغطية',
        'publisher' => 'المونتير/الناشر',
        'preview_review' => 'مراجعة النشر المؤقت',
        'designer' => 'المصمم',
        'creator' => 'صاحب الطلب',
    ];

    protected $fillable = [
        'workable_type', 'workable_id',
        'from_user_id', 'to_user_id',
        'step', 'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'to_user_id' => 'integer',
        ];
    }

    public function workable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function stepLabel(): string
    {
        return self::STEPS[$this->step] ?? $this->step;
    }
}