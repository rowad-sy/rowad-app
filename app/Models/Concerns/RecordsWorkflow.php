<?php

namespace App\Models\Concerns;

use App\Models\WorkflowAction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/*
 * نمط الإحالة/الموافقة العام (قسم 9 في الدراسة):
 * كل كيان يمكن أن يسجّل سلسلة إجراءات (إنشاء/إحالة/موافقة/رفض/تسعير/تنفيذ...)
 * تظهر كشريط زمني في صفحة الكيان، ويُستخدم لاحقاً في كل الوحدات (خطة الحركة،
 * تصاميم الإعلانات، الخطة الإعلامية، طلبات الشراء).
 */
trait RecordsWorkflow
{
    public function workflowActions(): MorphMany
    {
        return $this->morphMany(WorkflowAction::class, 'workable');
    }

    public function logWorkflow(string $action, ?int $toUserId = null, ?string $note = null, ?string $status = null): WorkflowAction
    {
        return $this->workflowActions()->create([
            'action' => $action,
            'from_user_id' => auth()->id(),
            'to_user_id' => $toUserId,
            'note' => $note,
            'status' => $status,
        ]);
    }
}