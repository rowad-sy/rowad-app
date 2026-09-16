<?php

namespace App\Models\Admin\MonthlyReports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReportSignoff extends Model
{
    protected $table = 'monthly_report_signoffs';

    protected $fillable = [
        'report_id', 'user_id', 'role_label', 'action', 'note',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}