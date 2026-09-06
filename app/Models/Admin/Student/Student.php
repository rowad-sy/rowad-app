<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Center;
use App\Models\Admin\Cohort;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'student_code', 'identity_type', 'identity_number', 'user_id',
        'first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en',
        'father_name', 'mother_name',
        'birth_date', 'birth_place', 'gender', 'nationality',
        'phone', 'email', 'address',
        'center_id', 'project_id', 'cohort_id',
        'status', 'enrollment_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrollment_date' => 'date',
        ];
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

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class, 'cohort_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_student')
            ->withPivot(['deleted_at'])
            ->whereNull('project_student.deleted_at');
    }

    public function allProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_student')
            ->withPivot(['deleted_at']);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'student_id');
    }
}
