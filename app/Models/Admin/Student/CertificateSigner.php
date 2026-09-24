<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CertificateSigner extends Model
{
    protected $table = 'certificate_signers';

    public const ROLES = [
        'instructor' => 'المدرب',
        'center_manager' => 'مدير المركز',
        'project_manager' => 'مسؤول المشروع',
    ];

    protected $fillable = ['code', 'name_ar', 'role', 'signature_path'];

    /*
     * رمز يُستخدم في الاستيراد/التصدير: الرمز الفعلي أو مولّد من المعرّف.
     */
    public function importCode(): string
    {
        return $this->code ?: 'SGN-' . $this->id;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function isUsedInSets(): bool
    {
        return CertificateSignatorySet::where('instructor_signer_id', $this->id)
            ->orWhere('center_manager_signer_id', $this->id)
            ->orWhere('project_manager_signer_id', $this->id)
            ->exists();
    }
}
