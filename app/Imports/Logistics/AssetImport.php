<?php

namespace App\Imports\Logistics;

use App\Models\Admin\Logistics\Asset;
use App\Support\RecordAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        // 1) تحقق من الملف كاملًا قبل أي كتابة: نطاق البيانات الفعلية لكل صف (تنسيق الملف الحالي لا يحمل مركزًا ولا مشروعًا،
        //    فالصف بلا مركز/مشروع لا يطابق صلاحية إنشاء مقيّدة بمركز أو مشروع؛ ولا يُخمَّن ولا يُسنَد تلقائيًا).
        foreach ($rows as $i => $row) {
            if (! RecordAccess::allows(auth()->user(), Asset::class, 'create', null, null)) {
                // الصف 1 هو صف العناوين، فأول بيانات = الصف 2
                throw new AssetImportRejected('تعذّر الاستيراد: الصف '.($i + 2).' بلا مركز/مشروع ضمن نطاق صلاحية الإنشاء الخاصة بك. لم يُحفظ أي أصل.');
            }
        }

        // 2) الحفظ ذري: أي فشل يتراجع عن الكل (السلوك الحالي للإنشاء بلا تغيير)
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                Asset::create([
                    'code' => $row['الكود'] ?? null,
                    'name' => $row['الاسم'] ?? '',
                    'value' => (float) ($row['القيمة'] ?? 0),
                    'status' => 'new',
                    'notes' => $row['ملاحظات'] ?? null,
                ]);
            }
        });
    }
}
