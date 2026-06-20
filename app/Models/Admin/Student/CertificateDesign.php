<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateDesign extends Model
{
    protected $table = 'certificate_designs';

    protected $fillable = [
        'name', 'course_id', 'template_image', 'fields_config', 'year',
    ];

    protected function casts(): array
    {
        return [
            'fields_config' => 'array',
            'year' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'design_id');
    }
}
