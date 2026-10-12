<?php

namespace App\Models\Admin\ProjectDocs;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
 * وثيقة مرفوعة (PDF) في الأرشيف — للوثائق القديمة والمستندات الجاهزة.
 * قد تكون مرتبطة بوثيقة نظامية (annex_document_id) عند رفعها كملف خارجي أو تحويلها.
 */
class UploadedDocument extends Model
{
    use SoftDeletes;

    protected $table = 'uploaded_documents';

    public const CATEGORIES = [
        'administrative' => 'إدارية',
        'financial' => 'مالية',
        'legal' => 'قانونية وعقود',
        'correspondence' => 'مراسلات',
        'technical' => 'فنية وتقنية',
        'hr' => 'موارد بشرية',
        'media' => 'إعلامية',
        'other' => 'أخرى',
    ];

    protected $fillable = [
        'title', 'description', 'category', 'document_date',
        'center_id', 'project_id', 'annex_document_id', 'file_path', 'file_name', 'file_size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function annexDocument(): BelongsTo
    {
        return $this->belongsTo(AnnexDocument::class, 'annex_document_id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function sizeLabel(): string
    {
        $kb = (int) $this->file_size > 0 ? $this->file_size / 1024 : 0;

        return $kb >= 1024
            ? number_format($kb / 1024, 1) . ' MB'
            : ($kb > 0 ? number_format($kb) . ' KB' : '—');
    }

    public function fileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
