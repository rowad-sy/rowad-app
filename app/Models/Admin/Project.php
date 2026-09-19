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

    protected $fillable = ['name', 'description', 'path_id', 'status', 'code'];

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
