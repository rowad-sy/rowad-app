<?php

namespace App\Models\Admin\Hr;

use App\Models\Admin\Center;
use App\Models\Admin\Cohort;
use App\Models\Admin\Department;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employees';

    protected $fillable = [
        'user_id', 'employee_code', 'status', 'id_number',
        'first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en',
        'father_name_ar', 'father_name_en', 'mother_name_ar', 'mother_name_en',
        'gender', 'marital_status', 'children_count', 'birth_date', 'birth_place', 'nationality',
        'center_id', 'department_id', 'project_id', 'cohort_id',
        'has_photo', 'has_cv', 'has_id_copy', 'has_qualification', 'has_experience_certs',
        'has_offer_letter', 'has_contract_doc', 'has_employee_data', 'has_job_description',
        'has_signature_movements', 'has_security_audit', 'has_reference_audit', 'has_code_of_conduct',
        'has_clearance', 'has_receipt', 'has_resignation',
        'has_verbal_warning_doc', 'has_written_warning_doc', 'has_termination_warning_doc',
        'has_termination_doc', 'has_blacklist_doc',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'children_count' => 'integer',
            'birth_date' => 'date',
            'has_photo' => 'boolean',
            'has_cv' => 'boolean',
            'has_id_copy' => 'boolean',
            'has_qualification' => 'boolean',
            'has_experience_certs' => 'boolean',
            'has_offer_letter' => 'boolean',
            'has_contract_doc' => 'boolean',
            'has_employee_data' => 'boolean',
            'has_job_description' => 'boolean',
            'has_signature_movements' => 'boolean',
            'has_security_audit' => 'boolean',
            'has_reference_audit' => 'boolean',
            'has_code_of_conduct' => 'boolean',
            'has_clearance' => 'boolean',
            'has_receipt' => 'boolean',
            'has_resignation' => 'boolean',
            'has_verbal_warning_doc' => 'boolean',
            'has_written_warning_doc' => 'boolean',
            'has_termination_warning_doc' => 'boolean',
            'has_termination_doc' => 'boolean',
            'has_blacklist_doc' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Employee $employee) {
            $defaults = [
                0 => ['start_time' => null, 'end_time' => null, 'is_day_off' => true],
                1 => ['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                2 => ['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                3 => ['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                4 => ['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                5 => ['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                6 => ['start_time' => null, 'end_time' => null, 'is_day_off' => true],
            ];
            foreach ($defaults as $day => $data) {
                $employee->workSchedules()->create([
                    'day_of_week' => $day,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'is_day_off' => $data['is_day_off'],
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class, 'cohort_id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class, 'employee_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class, 'employee_id');
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class, 'employee_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'employee_id');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class, 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class, 'employee_id');
    }

    public function notesRelation(): HasMany
    {
        return $this->hasMany(EmployeeNote::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class, 'employee_id');
    }
}
