<?php

namespace App\Exports\Logistics;

use App\Exports\BaseExport;
use App\Models\Admin\Logistics\Asset;
use App\Support\RecordAccess;

class AssetExport extends BaseExport
{
    public static function all(): self
    {
        // النطاق: فقط الأصول التي تسمح بها صلاحية العرض للمستخدم الحالي (RecordAccess). التحميل المسبق يقتصر على العلاقات
        // الموجودة فعلًا في النموذج (room وassetCategory غير معرّفتين فكان التصدير يفشل بـ500)؛ الأعمدة والرؤوس كما هي.
        $records = RecordAccess::scopeQuery(Asset::with(['center', 'project', 'recipient']), Asset::class, 'view')
            ->orderBy('created_at', 'desc')
            ->get();

        return new self($records, [
            'الكود', 'الاسم', 'التصنيف', 'الحالة', 'المركز', 'المشروع',
            'الغرفة', 'المستلم', 'القيمة', 'الملاحظات', 'تاريخ الإنشاء',
        ], [
            'code', 'name',
            fn($r) => $r->assetCategory?->name,
            fn($r) => match ($r->status) { 'new' => 'جديد', 'in_use' => 'قيد الاستخدام', 'maintenance' => 'صيانة', 'scrapped' => 'مستبعد', default => $r->status },
            fn($r) => $r->center?->name,
            fn($r) => $r->project?->name,
            fn($r) => $r->room?->name,
            fn($r) => $r->recipient?->name,
            'value', 'notes',
            fn($r) => $r->created_at?->format('Y-m-d'),
        ]);
    }
}
