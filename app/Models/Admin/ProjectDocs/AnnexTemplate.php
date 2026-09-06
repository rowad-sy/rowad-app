<?php

namespace App\Models\Admin\ProjectDocs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AnnexTemplate extends Model
{
    protected $table = 'annex_templates';

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

    public function documents(): HasMany
    {
        return $this->hasMany(AnnexDocument::class, 'template_id');
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