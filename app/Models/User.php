<?php

namespace App\Models;

use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /*
     * أنواع الحسابات — العمود في القاعدة string وليس enum، والتعداد يُدار هنا:
     * للإضافة: مفتاح جديد هنا + خيار في قائمة النوع + فرع في landingRoute() إن له لوحة مخصصة.
     */
    public const TYPES = [
        'employee' => 'موظف',
        'beneficiary' => 'مستفيد',
        'student' => 'طالب',
        'super-admin' => 'سوبر أدمن',
        'rowaduna' => 'روادنا',
    ];

    public const TYPE_LABELS = self::TYPES;

    /**
     * وجهة ما بعد الدخول حسب النوع — null تعني البوابة الافتراضية.
     * الأنواع ذات اللوحات الخاصة تتحقق من صلاحية اللوحة، والوجهة البديلة مساحة العمل.
     */
    public function landingRoute(): ?string
    {
        return match ($this->type) {
            'student' => ($student = $this->student)
                ? route('admin.students.show', $student, absolute: false)
                : route('admin.profile', absolute: false),
            'beneficiary' => route('admin.beneficiary.dashboard', absolute: false),
            'rowaduna' => \App\Helpers\PermissionHelper::can($this, 'page:admin.rowaduna.dashboard', 'view')
                ? route('admin.rowaduna.dashboard', absolute: false)
                : route('admin.dashboard', absolute: false),
            default => null,
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ($this->type ?: '—');
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'activation_email_sent_at',
        'activation_email_count',
        'type',
        'job_title_id',
        'center_id',
        'project_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'activation_email_sent_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot(['center_id', 'project_id', 'cohort_id'])
            ->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'user_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_title_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
