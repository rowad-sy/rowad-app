<?php

namespace App\Models\Admin\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
    protected $table = 'hr_work_schedules';

    /**
     * معنى day_of_week المخزَّن (وهو ما يكتبه نموذج الموظف والجدول الافتراضي في Employee::booted):
     * 0=السبت، 1=الأحد، ... 6=الجمعة (أسبوع يبدأ السبت؛ الافتراضي: الأحد–الخميس دوام والجمعة والسبت عطلة).
     * هذا يختلف عن ترقيم Carbon (0=الأحد)، لذا تُحوَّل التواريخ عبر indexForDate() عند القراءة.
     */
    public const DAY_NAMES = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

    protected $fillable = ['employee_id', 'day_of_week', 'start_time', 'end_time', 'is_day_off'];

    /** فهرس day_of_week المخزَّن لتاريخ معيّن (Carbon: 0=الأحد ← 1، 6=السبت ← 0، 5=الجمعة ← 6) */
    public static function indexForDate(\Carbon\CarbonInterface $date): int
    {
        return ($date->dayOfWeek + 1) % 7;
    }

    protected function casts(): array
    {
        return [
            'is_day_off' => 'boolean',
            'start_time' => 'string',
            'end_time' => 'string',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
