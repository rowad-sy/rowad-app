<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $table = 'hr_employee_documents';

    protected $fillable = ['employee_id', 'document_type', 'file_path', 'original_name'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
