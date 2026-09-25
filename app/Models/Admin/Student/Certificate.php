<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'certificates';

    protected $fillable = [
        'certificate_number', 'design_id', 'student_id', 'enrollment_id', 'signatory_set_id',
        'barcode_hash', 'issue_date', 'is_verified', 'verified_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(CertificateDesign::class, 'design_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function signatorySet(): BelongsTo
    {
        return $this->belongsTo(CertificateSignatorySet::class, 'signatory_set_id');
    }

    /*
     * اسم المقرر للعرض: تسجيل الشهادة ← مجموعة توقيعها ← مقرر التصميم،
     * وإن لم يوجد أي سياق والتسجيل الوحيد للطالب معروف نستخدمه.
     */
    public function resolvedCourseName(): ?string
    {
        $name = $this->enrollment?->course?->name_ar
            ?? $this->signatorySet?->course?->name_ar
            ?? $this->design?->course?->name_ar;

        if ($name) {
            return $name;
        }

        if (!$this->enrollment_id && !$this->signatory_set_id && !$this->design?->course_id) {
            $enrollment = $this->student?->enrollments()->get();
            if ($enrollment->count() === 1) {
                return $enrollment->first()?->course?->name_ar;
            }
        }

        return null;
    }
}
