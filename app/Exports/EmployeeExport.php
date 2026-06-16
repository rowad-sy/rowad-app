<?php

namespace App\Exports;

use App\Models\Admin\Hr\Employee;
use Illuminate\Support\Collection;

class EmployeeExport extends BaseExport
{
    public static function fromIds(array $ids): self
    {
        $records = Employee::with(['center', 'department', 'project', 'user'])
            ->whereIn('id', $ids)
            ->orderBy('employee_code')
            ->get();

        return new self($records, [
            'كود الموظف', 'الاسم AR', 'الاسم EN', 'الحالة', 'رقم الهوية',
            'الجنس', 'الجنسية', 'تاريخ الميلاد', 'مكان الميلاد',
            'الحالة الاجتماعية', 'عدد الأولاد', 'المركز', 'الإدارة', 'المشروع',
            'البريد الإلكتروني', 'تاريخ التسجيل',
        ], [
            'employee_code',
            fn($r) => $r->first_name_ar . ' ' . $r->last_name_ar,
            fn($r) => trim($r->first_name_en . ' ' . $r->last_name_en),
            fn($r) => $r->status === 'active' ? 'فعال' : 'غير فعال',
            'id_number',
            fn($r) => $r->gender === 'male' ? 'ذكر' : 'أنثى',
            'nationality', 'birth_date', 'birth_place',
            fn($r) => match ($r->marital_status) { 'single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل', default => '' },
            'children_count',
            fn($r) => $r->center?->name,
            fn($r) => $r->department?->name_ar,
            fn($r) => $r->project?->name,
            fn($r) => $r->user?->email,
            fn($r) => $r->created_at?->format('Y-m-d'),
        ]);
    }

    public static function all(): self
    {
        return self::fromIds(Employee::pluck('id')->toArray());
    }
}
