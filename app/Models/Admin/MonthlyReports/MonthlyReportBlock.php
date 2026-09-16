<?php

namespace App\Models\Admin\MonthlyReports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReportBlock extends Model
{
    protected $table = 'monthly_report_blocks';

    protected $fillable = [
        'report_id', 'block_key', 'json_value', 'updated_by', 'locked',
    ];

    protected function casts(): array
    {
        return [
            'json_value' => 'array',
            'locked' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class, 'report_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}