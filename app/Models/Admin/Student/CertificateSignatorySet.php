<?php

namespace App\Models\Admin\Student;

use App\Models\Admin\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateSignatorySet extends Model
{
    protected $table = 'certificate_signatory_sets';

    protected $fillable = [
        'name', 'course_id', 'period_id', 'center_id',
        'instructor_signer_id', 'center_manager_signer_id', 'project_manager_signer_id',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'period_id' => 'integer',
            'center_id' => 'integer',
            'instructor_signer_id' => 'integer',
            'center_manager_signer_id' => 'integer',
            'project_manager_signer_id' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function instructorSigner(): BelongsTo
    {
        return $this->belongsTo(CertificateSigner::class, 'instructor_signer_id');
    }

    public function centerManagerSigner(): BelongsTo
    {
        return $this->belongsTo(CertificateSigner::class, 'center_manager_signer_id');
    }

    public function projectManagerSigner(): BelongsTo
    {
        return $this->belongsTo(CertificateSigner::class, 'project_manager_signer_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'signatory_set_id');
    }

    public function signerFor(?string $role): ?CertificateSigner
    {
        return match ($role) {
            'instructor' => $this->instructorSigner,
            'center_manager' => $this->centerManagerSigner,
            'project_manager' => $this->projectManagerSigner,
            default => null,
        };
    }

    /*
     * حلّ مجموعة التوقيعات تلقائياً حسب سياق الدورة:
     * تطابق تام (مقرر+فترة+مركز) ثم (مقرر+فترة) ثم (مقرر فقط).
     * إذا طابقت عدة مجموعات (مثلاً عدة مدربين لنفس المقرر والمركز)
     * لا نخمّن — نُرجع null ليختبري الإصدار المجموعة صراحةً.
     */
    public static function resolve(?int $courseId, ?int $periodId, ?int $centerId): ?self
    {
        if (!$courseId) {
            return null;
        }

        $unique = fn($query) => $query->count() === 1 ? $query->first() : null;

        $exact = $unique(static::where('course_id', $courseId)
            ->where('period_id', $periodId)
            ->where('center_id', $centerId));
        if ($exact) {
            return $exact;
        }

        if ($periodId !== null && $centerId !== null) {
            $noCenter = $unique(static::where('course_id', $courseId)
                ->where('period_id', $periodId)
                ->whereNull('center_id'));
            if ($noCenter) {
                return $noCenter;
            }
        }

        return $unique(static::where('course_id', $courseId)
            ->whereNull('period_id')
            ->whereNull('center_id'));
    }
}
