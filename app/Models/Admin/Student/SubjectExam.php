<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectExam extends Model
{
    protected $table = 'subject_exams';

    protected $fillable = [
        'subject_id', 'name_ar', 'type', 'max_score', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}