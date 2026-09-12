<?php

namespace App\Models\Admin\ProjectDocs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnexDocumentBlock extends Model
{
    protected $table = 'annex_document_blocks';

    protected $fillable = [
        'document_id', 'block_key', 'page_number', 'json_value', 'updated_by', 'locked',
    ];

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'json_value' => 'array',
            'locked' => 'boolean',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AnnexDocument::class, 'document_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}