<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowAction extends Model
{
    public const ACTIONS = [
        'create' => 'الإنشاء والإحالة',
        'referred' => 'إعادة إحالة',
        'manager_approved' => 'موافقة المدير المباشر',
        'pm2_approved' => 'موافقة مدير المشاريع',
        'media_manager_approved' => 'موافقة مدير الإعلام',
        'event_assigned' => 'إسناد فعالية لمراسل',
        'covered' => 'تمت التغطية',
        'not_covered' => 'لم تتم التغطية',
        'rescheduled' => 'إعادة جدولة',
        'preview_published' => 'نشر مؤقت للمعاينة',
        'preview_approved' => 'اعتماد المعاينة',
        'preview_returned' => 'إرجاع المعاينة',
        'published' => 'نشر نهائي',
        'executed' => 'الإنجاز والإغلاق',
        'rejected' => 'الرفض',
        'assigned_designer' => 'إسناد التصميم',
        'design_submitted' => 'رفع التصميم',
        'design_returned' => 'إعادة التصميم للمصمم',
        'approved' => 'الموافقة/الاعتماد',
    ];

    protected $table = 'workflow_actions';

    protected $fillable = [
        'workable_type', 'workable_id',
        'action', 'from_user_id', 'to_user_id', 'note', 'status',
    ];

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
}