<?php

namespace App\Models\Admin\MonthlyReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MonthlyReportTemplate extends Model
{
    protected $table = 'monthly_report_templates';

    protected $fillable = [
        'key', 'title_ar', 'slug', 'version', 'json_definition', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'json_definition' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class, 'template_id');
    }

    public function sections(): Collection
    {
        return collect($this->json_definition['sections'] ?? []);
    }

    public function headerMeta(): array
    {
        return $this->json_definition['header_meta'] ?? [];
    }
}