<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function collection(): Collection
    {
        return User::with(['jobTitle', 'center', 'project'])
            ->when($this->filters['type'] ?? null, function ($q, $type) {
                return $q->where('type', $type);
            })
            ->when($this->filters['center_id'] ?? null, function ($q, $v) {
                return $q->where('center_id', $v);
            })
            ->when($this->filters['project_id'] ?? null, function ($q, $v) {
                return $q->where('project_id', $v);
            })
            ->when($this->filters['job_title_id'] ?? null, function ($q, $v) {
                return $q->where('job_title_id', $v);
            })
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('jobTitle', function ($q) use ($search) {
                            $q->where('title_ar', 'like', "%{$search}%")
                                ->orWhere('title_en', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'الاسم', 'البريد الإلكتروني', 'البريد الرسمي', 'النوع', 'المسمى الوظيفي',
            'المركز', 'المشروع', 'الحالة',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->official_email ?? '—',
            match ($user->type) {
                'super-admin' => 'سوبر أدمن',
                'employee' => 'موظف',
                'beneficiary' => 'مستفيد',
                'student' => 'طالب',
                default => 'عادي',
            },
            $user->jobTitle?->title_ar ?? '—',
            $user->center?->name ?? '—',
            $user->project?->name ?? '—',
            $user->is_active ? 'نشط' : ($user->email_verified_at ? 'مغلق' : 'غير مفعّل'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}