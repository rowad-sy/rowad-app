<?php

namespace App\Models\Admin\ProjectDocs;

use App\Models\Admin\Project;
use App\Models\Concerns\RecordsWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AnnexDocument extends Model
{
    use RecordsWorkflow;

    protected $table = 'annex_documents';

    protected $fillable = [
        'template_id', 'template_version', 'title',
        'project_id', 'period', 'status', 'document_kind', 'page_count', 'data', 'cover_path',
        'created_by', 'assigned_to', 'signed_at',
    ];

    public const KIND_STRUCTURED = 'structured';
    public const KIND_EXTERNAL = 'external';

    public const EXTERNAL_TEMPLATE_KEY = 'uploaded-external';

    public const STATUSES = [
        'draft' => 'مسودة',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'page_count' => 'integer',
            'data' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AnnexTemplate::class, 'template_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(AnnexDocumentBlock::class, 'document_id');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(AnnexSignoff::class, 'document_id');
    }

    public function linkedFiles(): HasMany
    {
        return $this->hasMany(UploadedDocument::class, 'annex_document_id');
    }

    public function sourceFile(): ?UploadedDocument
    {
        return $this->linkedFiles()->latest('id')->first();
    }

    public function isExternal(): bool
    {
        return $this->document_kind === self::KIND_EXTERNAL;
    }

    public static function externalTemplate(): AnnexTemplate
    {
        return AnnexTemplate::firstOrCreate(
            ['key' => self::EXTERNAL_TEMPLATE_KEY],
            [
                'title_ar' => 'وثيقة مرفوعة (ملف خارجي)',
                'slug' => 'uploaded-external',
                'version' => 1,
                'default_page_count' => 1,
                'json_definition' => ['sections' => [], 'header_meta' => []],
                'is_active' => false,
            ]
        );
    }

    public function sections(): Collection
    {
        return $this->template?->sections() ?? collect();
    }

    /*
     * مؤشر "اكتمال القسم": كل أنواع القالب قيمتها تُحمّل في حقل واحد.
     */
    public function isBlockComplete(AnnexDocumentBlock $block): bool
    {
        $value = $block->json_value;

        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value)) {
            if (isset($value[0]) && is_array($value[0])) {
                return collect($value)->contains(fn ($row) => collect($row)->every(fn ($cell) => trim((string) $cell) !== ''));
            }

            return collect($value)->every(fn ($v) => trim((string) $v) !== '');
        }

        return trim((string) $value) !== '';
    }

    public function missingSections(): array
    {
        $missing = [];

        foreach ($this->sections() as $section) {
            $block = $this->blocks->firstWhere('block_key', $section['key'] ?? null);
            if (! $block || ! $this->isBlockComplete($block)) {
                $missing[] = $section['title'] ?? $section['key'] ?? '?';
            }
        }

        return $missing;
    }

    public function pages(): Collection
    {
        return $this->blocks->sortBy('page_number')->groupBy('page_number');
    }

    public function isLocked($section): bool
    {
        $block = $this->blocks->firstWhere('block_key', $section['key'] ?? null);
        return $block && $block->locked;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}