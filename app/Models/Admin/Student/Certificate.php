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
}
