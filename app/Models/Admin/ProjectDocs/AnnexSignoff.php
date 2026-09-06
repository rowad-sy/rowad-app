<?php

namespace App\Models\Admin\ProjectDocs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnexSignoff extends Model
{
    protected $table = 'annex_signoffs';

    protected $fillable = [
        'document_id', 'user_id', 'role_label', 'action', 'note',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AnnexDocument::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}