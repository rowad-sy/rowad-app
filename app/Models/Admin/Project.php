<?php

namespace App\Models\Admin;

use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\TrainingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    /*
     * حالات المشروع — لكل حالة لون في الشجرة والواجهات:
     * فعال / تحت الدراسة / مغلق / معلق / داخلي
     */
    public const STATUSES = [
        'active' => 'فعال',
        'studying' => 'تحت الدراسة',
        'closed' => 'مغلق',
        'pending' => 'معلق',
        'internal' => 'داخلي',
    ];

    public const STATUS_BADGES = [
        'active' => 'status-active',
        'studying' => 'status-studying',
        'closed' => 'status-closed',
        'pending' => 'status-pending',
        'internal' => 'status-internal',
    ];

    protected $fillable = ['name', 'description', 'path_id', 'status', 'code', 'sort_order'];

    /**
     * ترتيب المشاريع في كل النظام: ترتيب المسار أولاً، ثم رقم المشروع داخل
     * مساره. المسارات غير المرتبة تأتي بعد المرتبة، والمشاريع بلا مسار أخيراً.
     */
    public function scopeOrdered($query)
    {
        $pathOrder = ProjectPath::query()
            ->select('id as ordering_path_id')
            ->addSelect('sort_order as ordering_path_rank')
            ->addSelect('name as ordering_path_name');

        return $query
            ->leftJoinSub($pathOrder, 'ordered_project_paths', function ($join) {
                $join->on('ordered_project_paths.ordering_path_id', '=', 'projects.path_id');
            })
            ->orderByRaw('CASE WHEN projects.path_id IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByRaw('COALESCE(ordered_project_paths.ordering_path_rank, 999999) ASC')
            ->orderBy('ordered_project_paths.ordering_path_name')
            ->orderBy('ordered_project_paths.ordering_path_id')
            ->orderByRaw('COALESCE(projects.sort_order, 999999) ASC')
            ->orderBy('projects.name')
            ->orderBy('projects.id');
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(ProjectPath::class, 'path_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ($this->status ?: 'بدون حالة');
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'status-pending';
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_project')->withTimestamps();
    }

    public function academicLevels(): HasMany
    {
        return $this->hasMany(AcademicLevel::class)->orderBy('sort_order');
    }

    public function trainingPlans(): HasMany
    {
        return $this->hasMany(TrainingPlan::class)->orderBy('start_date');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }
}
