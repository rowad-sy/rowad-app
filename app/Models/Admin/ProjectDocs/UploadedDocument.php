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
 * لا علاقة لها بدورة AnnexDocument؛ هي مكتبة مفتوحة بتصنيف تبويبي.
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
        'center_id', 'project_id', 'file_path', 'file_name', 'file_size', 'uploaded_by',
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
