<?php

namespace App\Exports\Students;

use App\Exports\BaseExport;
use App\Models\Admin\Student\Student;

class StudentExport extends BaseExport
{
    public static function fromIds(array $ids): self
    {
        $records = Student::with(['center', 'projects'])
            ->whereIn('id', $ids)
            ->orderBy('student_code')
            ->get();

        return new self($records, [
            'كود الطالب', 'نوع الهوية', 'رقم الهوية',
            'الاسم الأول AR', 'الاسم الأخير AR', 'الاسم الأول EN', 'الاسم الأخير EN',
            'الجنس', 'تاريخ الميلاد', 'الجنسية',
            'الهاتف', 'البريد الإلكتروني',
            'المركز', 'المشاريع',
            'الحالة', 'تاريخ التسجيل',
        ], [
            'student_code',
            fn($r) => $r->identity_type ? ['national_id' => 'بطاقة هوية', 'passport' => 'جواز سفر', 'resident_id' => 'إقامة', 'other' => 'أخرى'][$r->identity_type] ?? $r->identity_type : '',
            'identity_number',
            'first_name_ar',
            'last_name_ar',
            'first_name_en',
            'last_name_en',
            fn($r) => $r->gender === 'male' ? 'ذكر' : 'أنثى',
            'birth_date',
            'nationality',
            'phone',
            'email',
            fn($r) => $r->center?->name,
            fn($r) => $r->projects->pluck('name')->implode('، '),
            fn($r) => match ($r->status) { 'active' => 'نشط', 'inactive' => 'غير نشط', 'graduated' => 'متخرج', 'suspended' => 'موقوف', default => $r->status },
            fn($r) => $r->enrollment_date?->format('Y-m-d'),
        ]);
    }

    public static function all(): self
    {
        return self::fromIds(Student::pluck('id')->toArray());
    }
}
