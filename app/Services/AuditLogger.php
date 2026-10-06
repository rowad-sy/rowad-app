<?php

namespace App\Services;

use App\Models\AuditChange;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    protected static bool $silenced = false;

    protected static array $skipModels = [
        \App\Models\AuditLog::class,
        \App\Models\AuditChange::class,
    ];

    protected static array $fieldLabels = [];

    protected static int $silenceDepth = 0;

    public static function silenced(callable $callback): void
    {
        static::$silenceDepth++;
        try {
            $callback();
        } finally {
            static::$silenceDepth--;
        }
    }

    public static function isSilenced(): bool
    {
        return static::$silenceDepth > 0;
    }

    public static function shouldLog(Model $model, string $event): bool
    {
        if (static::isSilenced()) {
            return false;
        }

        $class = get_class($model);

        if (in_array($class, static::$skipModels)) {
            return false;
        }

        if (property_exists($model, 'auditDisabled') && $model->auditDisabled) {
            return false;
        }

        return true;
    }

    public static function log(Model $model, string $event): void
    {
        if (!static::shouldLog($model, $event)) {
            return;
        }

        $oldValues = null;
        $newValues = null;
        $changes = [];

        match ($event) {
            'created' => $newValues = static::serializeAttributes($model->getAttributes()),
            'deleted' => $oldValues = static::serializeAttributes($model->getOriginal()),
            'restored' => $newValues = static::serializeAttributes($model->getAttributes()),
            'updated' => $changes = static::computeDiff($model),
            default => null,
        };

        if ($event === 'updated' && empty($changes)) {
            return;
        }

        static::record(
            model: $model,
            event: $event,
            oldValues: $oldValues,
            newValues: $newValues,
            changes: $changes,
        );
    }

    public static function record(
        Model $model,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $changes = [],
        ?string $description = null,
        ?string $customEvent = null,
    ): AuditLog {
        $resolvedEvent = $customEvent ?? $event;

        $requestId = request()?->header('X-Request-ID')
            ?? Cache::get('audit_request_id')
            ?? Str::uuid()->toString();

        $user = auth()->user();

        $lastHash = Cache::remember('audit_last_hash', 60, function () {
            return AuditLog::latest('id')->value('hash');
        });

        $createdAt = now();

        $hashInput = implode('|', [
            $lastHash,
            $requestId,
            (string) ($user?->id),
            get_class($model),
            (string) $model->getKey(),
            $resolvedEvent,
            json_encode($oldValues ?? []),
            json_encode($newValues ?? []),
            $createdAt->toDateTimeString(),
        ]);

        $hash = hash('sha256', $hashInput);

        $log = AuditLog::create([
            'request_id' => $requestId,
            'user_id' => $user?->id,
            'user_type' => $user?->type ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent()
                ? substr(request()->userAgent(), 0, 255)
                : null,
            'model' => get_class($model),
            'model_name' => class_basename($model),
            'model_id' => $model->getKey(),
            'event' => $resolvedEvent,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'hash' => $hash,
            'prev_hash' => $lastHash,
            'created_at' => $createdAt,
        ]);

        foreach ($changes as $change) {
            AuditChange::create([
                'audit_log_id' => $log->id,
                'field' => $change['field'],
                'label' => $change['label'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
                'value_type' => $change['value_type'],
                'is_masked' => $change['is_masked'],
            ]);
        }

        Cache::put('audit_last_hash', $hash, 3600);

        return $log;
    }

    public static function recordEvent(
        string $modelClass,
        int|string|null $modelId,
        string $event,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Model $model = null,
    ): AuditLog {
        $dummyModel = $model ?? new $modelClass();
        $dummyModel->forceFill(['id' => $modelId]);

        if ($modelId && !$model) {
            $dummyModel->exists = true;
        }

        return static::record(
            model: $dummyModel,
            event: $event,
            oldValues: $oldValues,
            newValues: $newValues,
            description: $description,
            customEvent: $event,
        );
    }

    protected static function computeDiff(Model $model): array
    {
        $original = $model->getOriginal();
        $current = $model->getAttributes();

        $defaultExcludes = ['created_at', 'updated_at', 'deleted_at'];

        $modelExcludes = property_exists($model, 'auditExclude')
            ? $model->auditExclude
            : [];

        $excludes = array_merge($defaultExcludes, $modelExcludes);

        $masks = property_exists($model, 'auditMask')
            ? $model->auditMask
            : [];

        $labelMap = static::getFieldLabels(get_class($model));

        $changes = [];

        foreach ($current as $key => $value) {
            if (in_array($key, $excludes)) {
                continue;
            }

            if (!array_key_exists($key, $original)) {
                continue;
            }

            $old = $original[$key];

            $oldFormatted = static::formatValueForStorage($old);
            $newFormatted = static::formatValueForStorage($value);

            if ($oldFormatted === $newFormatted) {
                continue;
            }

            $isMasked = in_array($key, $masks);

            $changes[] = [
                'field' => $key,
                'label' => $labelMap[$key] ?? $key,
                'old_value' => $isMasked ? '***' : $oldFormatted,
                'new_value' => $isMasked ? '***' : $newFormatted,
                'value_type' => static::getValueType($value),
                'is_masked' => $isMasked,
            ];
        }

        return $changes;
    }

    protected static function serializeAttributes(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                $result[$key] = null;
                continue;
            }
            $result[$key] = static::formatValueForStorage($value);
        }
        return $result;
    }

    protected static function formatValueForStorage($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof \Illuminate\Support\Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($value)) {
            if (method_exists($value, 'getKey')) {
                return (string) $value->getKey();
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    protected static function getValueType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value) || is_float($value)) {
            return is_int($value) ? 'integer' : 'decimal';
        }
        if ($value instanceof \Illuminate\Support\Carbon || $value instanceof \DateTime) {
            return 'datetime';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }

    protected static function getFieldLabels(string $modelClass): array
    {
        if (isset(static::$fieldLabels[$modelClass])) {
            return static::$fieldLabels[$modelClass];
        }

        $shortName = class_basename($modelClass);

        $commonLabels = [
            'id' => 'المعرّف',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التعديل',
            'deleted_at' => 'تاريخ الحذف',
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'status' => 'الحالة',
            'notes' => 'الملاحظات',
            'center_id' => 'المركز',
            'project_id' => 'المشروع',
            'user_id' => 'المستخدم',
        ];

        $modelLabels = match ($shortName) {
            'Employee' => [
                'employee_code' => 'كود الموظف',
                'first_name_ar' => 'الاسم الأول بالعربية',
                'last_name_ar' => 'اللقب بالعربية',
                'first_name_en' => 'الاسم الأول بالإنجليزية',
                'last_name_en' => 'اللقب بالإنجليزية',
                'gender' => 'الجنس',
                'marital_status' => 'الحالة الاجتماعية',
                'department_id' => 'الإدارة',
                'has_photo' => 'يوجد صورة',
                'has_cv' => 'يوجد سيرة ذاتية',
            ],
            'Student' => [
                'student_code' => 'كود الطالب',
                'first_name_ar' => 'الاسم الأول بالعربية',
                'last_name_ar' => 'اللقب بالعربية',
                'first_name_en' => 'الاسم الأول بالإنجليزية',
                'last_name_en' => 'اللقب بالإنجليزية',
                'identity_type' => 'نوع الهوية',
                'identity_number' => 'رقم الهوية',
                'gender' => 'الجنس',
                'nationality' => 'الجنسية',
                'enrollment_date' => 'تاريخ التسجيل',
            ],
            'ProjectTask' => [
                'title' => 'عنوان المهمة',
                'purpose' => 'الغاية',
                'start_date' => 'تاريخ البداية',
                'end_date' => 'تاريخ النهاية',
                'assigned_to' => 'المسند إليه',
                'created_by' => 'المنشئ',
                'needs_media_coverage' => 'تغطية إعلامية',
                'needs_costs' => 'تكاليف',
                'needs_equipment' => 'تجهيزات',
                'executed' => 'تم التنفيذ',
                'has_delay' => 'يوجد تأخير',
                'media_coverage_done' => 'تمت التغطية الإعلامية',
            ],
            'PurchaseRequest' => [
                'request_number' => 'رقم الطلب',
                'expected_total_price' => 'السعر الإجمالي المتوقع',
                'status' => 'الحالة',
                'signature_path' => 'التوقيع',
            ],
            'Asset' => [
                'asset_code' => 'كود الأصل',
                'name' => 'اسم الأصل',
                'type' => 'النوع',
                'room_number' => 'رقم الغرفة',
                'recipient_id' => 'المستلم',
            ],
            'TechIssue' => [
                'title' => 'العنوان',
                'description' => 'الوصف',
                'priority' => 'الأولوية',
                'admin_response' => 'رد الإدارة',
                'resolved_at' => 'تاريخ الحل',
            ],
            'Permission' => [
                'model_names' => 'الموديلات',
                'model_id' => 'العنصر',
                'can_view' => 'عرض',
                'can_create' => 'إضافة',
                'can_edit' => 'تعديل',
                'can_delete' => 'حذف',
            ],
            'User' => [
                'name' => 'الاسم',
                'email' => 'البريد الإلكتروني',
                'is_active' => 'نشط',
                'type' => 'النوع',
            ],
            'LeaveRequest' => [
                'leave_type_id' => 'نوع الإجازة',
                'start_date' => 'تاريخ البداية',
                'end_date' => 'تاريخ النهاية',
                'days_count' => 'عدد الأيام',
                'reason' => 'السبب',
            ],
            'Salary' => [
                'base_salary' => 'الراتب الأساسي',
                'currency' => 'العملة',
                'salary_unit' => 'وحدة الراتب',
                'study_allowance' => 'علاوة الدراسة',
                'marriage_allowance' => 'علاوة الزواج',
                'experience_allowance' => 'علاوة الخبرة',
                'transport_allowance' => 'علاوة النقل',
                'food_allowance' => 'علاوة الطعام',
                'housing_allowance' => 'علاوة السكن',
                'mobile_allowance' => 'علاوة الموبايل',
                'risk_allowance' => 'علاوة الخطورة',
                'overtime_rate' => 'أجر إضافي',
                'deduction' => 'خصم',
                'total_salary' => 'إجمالي الراتب',
            ],
            'Contract' => [
                'contract_type' => 'نوع العقد',
                'job_position_id' => 'المسمى الوظيفي',
                'contract_start' => 'بداية العقد',
                'contract_end' => 'نهاية العقد',
                'start_date' => 'تاريخ البداية',
                'leave_date' => 'تاريخ المغادرة',
            ],
            'Warning' => [
                'date' => 'التاريخ',
                'reason' => 'السبب',
                'level' => 'المستوى',
                'is_folded' => 'مطوية',
            ],
            'MediaPlan' => [
                'month_date' => 'الشهر',
                'note' => 'ملاحظات',
                'status' => 'الحالة',
                'refer_to_direct_manager_id' => 'المدير المباشر',
                'refer_to_pm2_id' => 'مدير المشاريع',
                'refer_to_rowaduna_id' => 'مسؤول روادنا',
                'locked_at' => 'وقت القفل',
                'locked_by' => 'مانح القفل',
                'reason' => 'سبب الرفض',
            ],
            'MediaPlanEvent' => [
                'event_date' => 'تاريخ الفعالية',
                'event_time' => 'ساعة الفعالية',
                'event_name' => 'اسم الفعالية',
                'office' => 'المكتب',
                'location' => 'الموقع',
                'responsible_user_id' => 'المسؤول عن الفعالية',
                'summary' => 'الملخص',
                'coverage_type' => 'نوع التغطية',
                'coverage_status' => 'حالة التغطية',
                'refer_to_reporter_id' => 'المراسل',
                'not_covered_reason' => 'سبب عدم التغطية',
                'coverage_note' => 'ملاحظة التغطية',
                'media_items_url' => 'رابط المواد (درايف)',
                'publish_status' => 'حالة النشر',
                'refer_to_publisher_id' => 'المونتير/الناشر',
                'preview_url' => 'رابط المعاينة',
                'refer_to_reviewer_id' => 'مراجع المعاينة',
                'preview_feedback' => 'ملاحظات المعاينة',
                'publish_links' => 'روابط النشر الدائم',
                'published_by' => 'ناشر نهائي',
                'published_at' => 'وقت النشر',
            ],
            'AdDesignRequest' => [
                'title' => 'عنوان الطلب',
                'description' => 'المتطلبات',
                'due_date' => 'المطلوب قبل',
                'status' => 'الحالة',
                'refer_to_pm2_id' => 'مدير المشاريع',
                'refer_to_rowaduna_id' => 'مسؤول روادنا',
                'refer_to_designer_id' => 'المصمم',
                'refer_to_publisher_id' => 'الناشر',
                'design_url' => 'رابط التصميم',
                'design_note' => 'ملاحظة المصمم',
                'revision_note' => 'ملاحظات الإعادة',
                'publish_links' => 'روابط النشر',
                'reason' => 'سبب الرفض',
            ],
            'UploadedDocument' => [
                'title' => 'عنوان الوثيقة',
                'description' => 'الوصف',
                'category' => 'التصنيف',
                'document_date' => 'تاريخ الوثيقة',
                'file_path' => 'مسار الملف',
                'file_name' => 'اسم الملف',
                'file_size' => 'حجم الملف',
                'uploaded_by' => 'رافع الوثيقة',
            ],
            default => [],
        };

        static::$fieldLabels[$modelClass] = array_merge($commonLabels, $modelLabels);

        return static::$fieldLabels[$modelClass];
    }

    public static function setFieldLabels(string $modelClass, array $labels): void
    {
        static::$fieldLabels[$modelClass] = $labels;
    }

    public static function addSkipModel(string $modelClass): void
    {
        static::$skipModels[] = $modelClass;
    }

    public static function logPivot(
        Model $parent,
        string $relation,
        array $oldPivotIds,
        array $newPivotIds,
        ?string $description = null,
    ): AuditLog {
        $attached = array_diff($newPivotIds, $oldPivotIds);
        $detached = array_diff($oldPivotIds, $newPivotIds);

        $detail = [];
        if (!empty($attached)) {
            $detail['attached'] = $attached;
        }
        if (!empty($detached)) {
            $detail['detached'] = $detached;
        }

        return static::record(
            model: $parent,
            event: 'pivot',
            oldValues: ['ids' => $oldPivotIds],
            newValues: ['ids' => $newPivotIds],
            description: $description ?? "تحديث العلاقة {$relation}",
        );
    }

    public static function verifyChain(): array
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();
        $previousHash = null;
        $brokenAt = null;
        $totalVerified = 0;

        foreach ($logs as $log) {
            $expectedHash = hash('sha256', implode('|', [
                $previousHash,
                $log->request_id,
                (string) $log->user_id,
                $log->model,
                (string) $log->model_id,
                $log->event,
                json_encode($log->old_values ?? []),
                json_encode($log->new_values ?? []),
                $log->created_at->toDateTimeString(),
            ]));

            if ($expectedHash !== $log->hash) {
                $brokenAt = $log->id;
                break;
            }

            $previousHash = $log->hash;
            $totalVerified++;
        }

        return [
            'valid' => $brokenAt === null,
            'total_verified' => $totalVerified,
            'broken_at_id' => $brokenAt,
            'total_records' => $logs->count(),
        ];
    }
}
