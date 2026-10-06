<?php

namespace App\Models\Admin;

use App\Models\Concerns\ManagesReferrals;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlanEvent extends Model
{
    use ManagesReferrals;

    protected $table = 'media_plan_events';

    protected $fillable = [
        'media_plan_id', 'event_date', 'office', 'event_name', 'day',
        'event_time', 'location', 'responsible_user_id', 'summary',
        'coverage_type', 'notes',
        'execution_status', 'execution_note', 'execution_by', 'execution_at',
        'coverage_status', 'refer_to_reporter_id', 'not_covered_reason',
        'coverage_note', 'media_items_url',
        'publish_status', 'refer_to_publisher_id', 'preview_url',
        'refer_to_reviewer_id', 'preview_feedback', 'publish_links',
        'preview_reviewed_by', 'preview_reviewed_at', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'execution_at' => 'datetime',
            'preview_reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'publish_links' => 'array',
        ];
    }

    public const COVERAGE_STATUSES = [
        'pending' => 'بانتظار الإسناد لمراسل',
        'assigned' => 'بانتظار التغطية (لدى المراسل)',
        'covered' => 'تمت التغطية',
        'not_covered' => 'لم تتم التغطية',
    ];

    public const PUBLISH_STATUSES = [
        'none' => 'لم يبدأ النشر',
        'to_publish' => 'بانتظار النشر المؤقت (لدى المونتير)',
        'to_review' => 'بانتظار مراجعة مدير المشروع',
        'rework' => 'ملاحظات على المعاينة — بانتظار إعادة النشر',
        'to_final' => 'موافق على المعاينة — بانتظار النشر الدائم',
        'published' => 'منشور نهائياً',
    ];

    public const PLATFORMS = [
        'facebook' => 'فيسبوك',
        'instagram' => 'إنستغرام',
        'youtube' => 'يوتيوب',
        'tiktok' => 'تيك توك',
        'x' => 'إكس (تويتر)',
        'telegram' => 'تيليغرام',
        'website' => 'الموقع الإلكتروني',
        'other' => 'أخرى',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class, 'media_plan_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function executionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'execution_by');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_reporter_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_publisher_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_to_reviewer_id');
    }

    public function previewReviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preview_reviewed_by');
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MediaPlanComment::class, 'media_plan_event_id')->orderBy('created_at');
    }

    public function coverageLabel(): string
    {
        return self::COVERAGE_STATUSES[$this->coverage_status] ?? (string) $this->coverage_status;
    }

    public function publishLabel(): string
    {
        return self::PUBLISH_STATUSES[$this->publish_status] ?? (string) $this->publish_status;
    }

    /*
     * الحالة النهائية للفعالية: إمّا نُشرت نهائياً، أو لم تتم التغطية
     * (وقابل للجدولة تعيده إلى دورة التغطية).
     */
    public function isClosed(): bool
    {
        return $this->coverage_status === 'not_covered' || $this->publish_status === 'published';
    }

    /*
     * تسلسل خط أنابيب هذه الفعالية داخل الخطة (للتصفية والعدادات).
     */
    public function pipelineStage(): string
    {
        return match (true) {
            $this->coverage_status === 'pending' => 'pending_assign',
            $this->coverage_status === 'assigned' => 'with_reporter',
            $this->coverage_status === 'not_covered' => 'not_covered',
            $this->publish_status === 'to_publish' || $this->publish_status === 'rework' => 'with_publisher',
            $this->publish_status === 'to_review' => 'with_reviewer',
            $this->publish_status === 'to_final' => 'awaiting_final_publish',
            $this->publish_status === 'published' => 'published',
            default => 'covered',
        };
    }
}
