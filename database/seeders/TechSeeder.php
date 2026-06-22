<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechEquipment;
use App\Models\Admin\Tech\TechIssue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TechSeeder extends Seeder
{
    public function run(): void
    {
        $centerIds = Center::pluck('id')->toArray();
        $projectIds = Project::pluck('id')->toArray();
        $employeeUserIds = User::where('type', 'employee')->pluck('id')->toArray();

        if (empty($centerIds) || empty($employeeUserIds)) {
            $this->command->warn('لا توجد مراكز أو مستخدمين من نوع موظف. تخطي بذر التقنية.');
            return;
        }

        // ── Equipment Types ──
        $equipmentTypes = [
            'حاسوب مكتبي', 'حاسوب محمول', 'طابعة', 'ماسح ضوئي',
            'راوتر', 'سويتش', 'خادم (سيرفر)', 'جهاز عرض (بروجكتور)',
            'كاميرا مراقبة', 'هاتف مكتبي', 'جهاز تابلت', 'شاشة عرض',
            'بطارية UPS', 'مايك وسماعات', 'طابعة باركود',
        ];

        $conditions = ['a', 'b', 'c', 'd', 'e'];
        $roomNumbers = ['G01', 'G02', 'G03', '101', '102', '103', '104', '105', '201', '202', '203', 'مستودع 1', 'مستودع 2', 'صالة 1', 'صالة 2'];

        $equipmentData = [
            // Center 1 - main center gets more equipment
            ['name' => 'حاسوب مكتبي Dell OptiPlex', 'type' => 'حاسوب مكتبي', 'serial_number' => 'DELL-OPT-001', 'condition' => 'a', 'room' => '101', 'center_idx' => 0],
            ['name' => 'حاسوب مكتبي Dell OptiPlex', 'type' => 'حاسوب مكتبي', 'serial_number' => 'DELL-OPT-002', 'condition' => 'a', 'room' => '101', 'center_idx' => 0],
            ['name' => 'حاسوب مكتبي Dell OptiPlex', 'type' => 'حاسوب مكتبي', 'serial_number' => 'DELL-OPT-003', 'condition' => 'b', 'room' => '102', 'center_idx' => 0],
            ['name' => 'حاسوب محمول Lenovo ThinkPad', 'type' => 'حاسوب محمول', 'serial_number' => 'LEN-TP-001', 'condition' => 'a', 'room' => '101', 'center_idx' => 0],
            ['name' => 'حاسوب محمول HP EliteBook', 'type' => 'حاسوب محمول', 'serial_number' => 'HP-ELITE-001', 'condition' => 'b', 'room' => '102', 'center_idx' => 0],
            ['name' => 'طابعة HP LaserJet', 'type' => 'طابعة', 'serial_number' => 'HPLJ-4001', 'condition' => 'a', 'room' => '101', 'center_idx' => 0],
            ['name' => 'طابعة Canon MF', 'type' => 'طابعة', 'serial_number' => 'CAN-MF-001', 'condition' => 'c', 'room' => '102', 'center_idx' => 0],
            ['name' => 'راوتر Cisco ISR', 'type' => 'راوتر', 'serial_number' => 'CIS-ISR-001', 'condition' => 'a', 'room' => 'مستودع 1', 'center_idx' => 0],
            ['name' => 'سويتش Cisco Catalyst', 'type' => 'سويتش', 'serial_number' => 'CIS-CAT-48P', 'condition' => 'a', 'room' => 'مستودع 1', 'center_idx' => 0],
            ['name' => 'خادم Dell PowerEdge', 'type' => 'خادم (سيرفر)', 'serial_number' => 'DELL-PE-R740', 'condition' => 'a', 'room' => 'مستودع 1', 'center_idx' => 0],
            ['name' => 'جهاز عرض Epson EB', 'type' => 'جهاز عرض (بروجكتور)', 'serial_number' => 'EPS-EB-2055', 'condition' => 'b', 'room' => 'صالة 1', 'center_idx' => 0],
            ['name' => 'كاميرا Hikvision DS', 'type' => 'كاميرا مراقبة', 'serial_number' => 'HIK-DS-2CD', 'condition' => 'a', 'room' => 'المدخل', 'center_idx' => 0],
            ['name' => 'بطارية APC UPS', 'type' => 'بطارية UPS', 'serial_number' => 'APC-SMT-1500', 'condition' => 'b', 'room' => 'مستودع 1', 'center_idx' => 0],
            ['name' => 'حاسوب محمول MacBook Air', 'type' => 'حاسوب محمول', 'serial_number' => 'APP-MBA-M2', 'condition' => 'a', 'room' => '201', 'center_idx' => 0],
            ['name' => 'طابعة HP DeskJet', 'type' => 'طابعة', 'serial_number' => 'HPDJ-2775', 'condition' => 'd', 'room' => '103', 'center_idx' => 0],
            // Other centers get 2-4 items each
            ['name' => 'حاسوب مكتبي HP ProDesk', 'type' => 'حاسوب مكتبي', 'serial_number' => 'HP-PRO-001', 'condition' => 'b', 'room' => 'G01', 'center_idx' => 1],
            ['name' => 'طابعة Brother HL', 'type' => 'طابعة', 'serial_number' => 'BR-HL-1201', 'condition' => 'c', 'room' => 'G01', 'center_idx' => 1],
            ['name' => 'راوتر TP-Link', 'type' => 'راوتر', 'serial_number' => 'TPL-ARCHE-R1', 'condition' => 'b', 'room' => 'G02', 'center_idx' => 1],
            ['name' => 'حاسوب مكتبي Dell Precision', 'type' => 'حاسوب مكتبي', 'serial_number' => 'DELL-PRE-001', 'condition' => 'a', 'room' => '201', 'center_idx' => 2],
            ['name' => 'جهاز عرض ViewSonic', 'type' => 'جهاز عرض (بروجكتور)', 'serial_number' => 'VSC-LS-750', 'condition' => 'b', 'room' => 'صالة 2', 'center_idx' => 2],
            ['name' => 'حاسوب محمول Acer Aspire', 'type' => 'حاسوب محمول', 'serial_number' => 'ACR-ASP-001', 'condition' => 'c', 'room' => '202', 'center_idx' => 2],
            ['name' => 'كاميرا مراقبة Dahua', 'type' => 'كاميرا مراقبة', 'serial_number' => 'DAH-IPC-001', 'condition' => 'a', 'room' => 'المدخل', 'center_idx' => 2],
            ['name' => 'حاسوب مكتبي Lenovo ThinkCentre', 'type' => 'حاسوب مكتبي', 'serial_number' => 'LEN-TC-001', 'condition' => 'b', 'room' => '101', 'center_idx' => 3],
            ['name' => 'طابعة Epson LQ', 'type' => 'طابعة', 'serial_number' => 'EPS-LQ-590', 'condition' => 'd', 'room' => '101', 'center_idx' => 3],
            ['name' => 'هاتف مكتبي Panasonic', 'type' => 'هاتف مكتبي', 'serial_number' => 'PAN-KX-001', 'condition' => 'a', 'room' => '101', 'center_idx' => 3],
            ['name' => 'سويتش D-Link', 'type' => 'سويتش', 'serial_number' => 'DLK-DGS-24P', 'condition' => 'b', 'room' => 'مستودع 2', 'center_idx' => 3],
            ['name' => 'حاسوب مكتبي HP EliteDesk', 'type' => 'حاسوب مكتبي', 'serial_number' => 'HP-ED-001', 'condition' => 'a', 'room' => '102', 'center_idx' => 4],
            ['name' => 'حاسوب محمول Dell Latitude', 'type' => 'حاسوب محمول', 'serial_number' => 'DELL-LAT-001', 'condition' => 'b', 'room' => '102', 'center_idx' => 4],
            ['name' => 'طابعة Xerox', 'type' => 'طابعة', 'serial_number' => 'XRX-WC-365', 'condition' => 'c', 'room' => '102', 'center_idx' => 4],
            ['name' => 'جهاز تابلت Samsung Galaxy Tab', 'type' => 'جهاز تابلت', 'serial_number' => 'SAM-TAB-A9', 'condition' => 'a', 'room' => '102', 'center_idx' => 4],
            ['name' => 'حاسوب مكتبي ASUS', 'type' => 'حاسوب مكتبي', 'serial_number' => 'ASUS-D-001', 'condition' => 'c', 'room' => 'G03', 'center_idx' => 5],
            ['name' => 'طابعة HP OfficeJet', 'type' => 'طابعة', 'serial_number' => 'HP-OJ-9010', 'condition' => 'e', 'room' => 'G03', 'center_idx' => 5],
            ['name' => 'راوتر MikroTik', 'type' => 'راوتر', 'serial_number' => 'MIK-RB-3011', 'condition' => 'a', 'room' => 'مستودع 2', 'center_idx' => 5],
            ['name' => 'حاسوب محمول Lenovo IdeaPad', 'type' => 'حاسوب محمول', 'serial_number' => 'LEN-IP-001', 'condition' => 'b', 'room' => '103', 'center_idx' => 6],
            ['name' => 'شاشة عرض LG', 'type' => 'شاشة عرض', 'serial_number' => 'LG-22MP-001', 'condition' => 'a', 'room' => '103', 'center_idx' => 6],
            ['name' => 'طابعة Brother DCP', 'type' => 'طابعة', 'serial_number' => 'BR-DCP-001', 'condition' => 'b', 'room' => '103', 'center_idx' => 6],
            ['name' => 'حاسوب مكتبي Dell Vostro', 'type' => 'حاسوب مكتبي', 'serial_number' => 'DELL-VOS-001', 'condition' => 'a', 'room' => '104', 'center_idx' => 7],
            ['name' => 'جهاز عرض BenQ', 'type' => 'جهاز عرض (بروجكتور)', 'serial_number' => 'BNQ-MH-560', 'condition' => 'c', 'room' => 'صالة 2', 'center_idx' => 7],
            ['name' => 'حاسوب محمول HP ProBook', 'type' => 'حاسوب محمول', 'serial_number' => 'HP-PB-001', 'condition' => 'b', 'room' => '104', 'center_idx' => 7],
            ['name' => 'كاميرا مراقبة Hikvision', 'type' => 'كاميرا مراقبة', 'serial_number' => 'HIK-DS-7616', 'condition' => 'a', 'room' => 'المدخل', 'center_idx' => 7],
            ['name' => 'حاسوب مكتبي Lenovo', 'type' => 'حاسوب مكتبي', 'serial_number' => 'LEN-D-001', 'condition' => 'b', 'room' => '105', 'center_idx' => 8],
            ['name' => 'طابعة Canon LBP', 'type' => 'طابعة', 'serial_number' => 'CAN-LBP-001', 'condition' => 'a', 'room' => '105', 'center_idx' => 8],
            ['name' => 'هاتف مكتبي Grandstream', 'type' => 'هاتف مكتبي', 'serial_number' => 'GRD-GXP-001', 'condition' => 'b', 'room' => '105', 'center_idx' => 8],
            ['name' => 'حاسوب مكتبي HP ProOne', 'type' => 'حاسوب مكتبي', 'serial_number' => 'HP-PO-001', 'condition' => 'c', 'room' => '201', 'center_idx' => 9],
            ['name' => 'طابعة Epson EcoTank', 'type' => 'طابعة', 'serial_number' => 'EPS-ET-4850', 'condition' => 'a', 'room' => '201', 'center_idx' => 9],
            ['name' => 'مايك وسماعات Logitech', 'type' => 'مايك وسماعات', 'serial_number' => 'LOG-ZONE-001', 'condition' => 'b', 'room' => 'صالة 1', 'center_idx' => 9],
        ];

        foreach ($equipmentData as $item) {
            $centerId = $centerIds[$item['center_idx'] % count($centerIds)];
            $centerProjects = Project::where('id', $projectIds[array_rand($projectIds)])->first();

            TechEquipment::firstOrCreate(
                ['serial_number' => $item['serial_number']],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'serial_number' => $item['serial_number'],
                    'condition' => $item['condition'],
                    'room' => $item['room'],
                    'center_id' => $centerId,
                    'project_id' => $centerProjects?->id ?? null,
                    'notes' => null,
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                ]
            );
        }

        $this->command->info('✓ تم بذر ' . count($equipmentData) . ' معدة تقنية');

        // ── Tech Issues ──
        $issueTitles = [
            'بطء شديد في الشبكة',
            'شاشة الكمبيوتر لا تعمل',
            'طابعة لا تطبع',
            'نسيت كلمة مرور النظام',
            'الإنترنت مقطوع',
            'عطل في جهاز العرض',
            'تثبيت برنامج جديد',
            'صيانة دورية للخادم',
            'إعادة ضبط الراوتر',
            'البريد الإلكتروني لا يعمل',
            'تحديث نظام التشغيل',
            'أعطال في سماعات الاجتماعات',
            'كاميرا المراقبة لا تسجل',
            'طلب توفير حاسوب محمول إضافي',
            'نسخ احتياطي للبيانات',
            'إعداد بريد إلكتروني جديد',
            'ربط طابعة شبكة جديدة',
            'تهيئة جهاز تابلت للعرض',
            'مشكلة في برنامج المحاسبة',
            'تثبيت تحديثات أمنية',
            'طلب تمديد كابل شبكة',
            'شاشة زرقاء (Blue Screen)',
            'عطل في بطارية UPS',
            'إعداد نظام حضور وانصراف',
            'إنشاء حساب مستخدم جديد',
            'تسريع جهاز بطيء',
            'تركيب سويتش جديد في المستودع',
            'تغيير كلمة سر الواي فاي',
            'تهيئة جهاز مستعمل لموظف جديد',
            'طلب ترقية ذاكرة RAM',
        ];

        $statuses = ['open', 'in_progress', 'completed', 'blocked'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $issueDescriptions = [
            'يعاني الموظف من بطء شديد في تصفح الإنترنت وفي التعامل مع الملفات على الشبكة الداخلية. نأمل فحص الشبكة والتأكد من سلامتها.',
            'تم إعادة تشغيل الجهاز أكثر من مرة ولكن الشاشة تبقى سوداء. يُرجى الكشف على الجهاز واستبدال الشاشة إن لزم الأمر.',
            'عند محاولة الطباعة تظهر رسالة خطأ أن الطابعة غير متصلة أو غير جاهزة. تم فحص الكابلات والتوصيلات سليمة.',
            'قام الموظف بتغيير كلمة المرور ونسيها. يرجى إعادة تعيين كلمة المرور لحسابه في النظام.',
            'لا توجد خدمة إنترنت في المكتب بالكامل منذ الصباح. تم إعادة تشغيل الراوتر الرئيسي ولكن دون جدوى.',
            'جهاز العرض (بروجكتور) لا يضيء عند التشغيل ويصدر صوت صفارة مستمر. يحتاج إلى صيانة أو استبدال.',
            'نحتاج إلى تثبيت برنامج Microsoft Office 2024 على 3 أجهزة في قسم المحاسبة.',
            'موعد الصيانة الدورية للخادم الرئيسي يقترب. نأمل جدولة زيارة للصيانة في أقرب وقت ممكن.',
            'الراوتر يفصل ويوصل بشكل مستمر. تم إعادة تشغيله عدة مرات ولكن المشكلة ما زالت قائمة.',
            'لا يمكن للموظف الوصول إلى بريده الإلكتروني منذ يومين. تظهر رسالة فشل الاتصال بالخادم.',
            'يظهر إشعار بتوفر تحديث لنظام Windows 11. يرجى تحديث الأجهزة في قسم الإدارة.',
            'سماعات الاجتماعات تصدر صوت تشويش أثناء المكالمات. تم فحص الإعدادات وهي سليمة.',
            'كاميرات المراقبة في المدخل الرئيسي لا تسجل منذ 3 أيام. يرجى فحص النظام.',
            'نحتاج حاسوب محمول إضافي للموظف الجديد الذي سيبدأ الأسبوع القادم.',
            'طلب عمل نسخة احتياطية شاملة لجميع ملفات الخادم ونقلها إلى القرص الصلب الخارجي.',
        ];

        $responses = [
            null,
            'تم فحص المشكلة وإعادة تشغيل الخادم. الموقع يعمل الآن بشكل طبيعي.',
            'تم إصلاح العطل واستبدال كابل الشبكة التالف. يرجى التأكد من عمل الخدمة.',
            'تمت إعادة تعيين كلمة المرور وإرسالها إلى البريد البديل.',
            'تم فحص الجهاز وتبين وجود مشكلة في القرص الصلب. سيتم استبداله الأسبوع القادم.',
            'تم حل المشكلة بتحديث تعريفات الطابعة. يرجى تجربة الطباعة مرة أخرى.',
            'تمت الصيانة الدورية وتنظيف المراوح واستبدال المعجون الحراري.',
            'تم تثبيت التحديثات الأمنية وإعادة تشغيل الأجهزة. جميع الأنظمة تعمل بشكل طبيعي.',
            'تم إعداد البريد الإلكتروني وتفعيل الحساب. يمكن للمستخدم الوصول الآن.',
            'تم فحص كاميرات المراقبة وإعادة تشغيل نظام التسجيل. جميع الكاميرات تعمل الآن.',
        ];

        $count = 0;
        foreach ($issueTitles as $i => $title) {
            $status = $statuses[array_rand($statuses)];
            $hasResponse = in_array($status, ['completed', 'blocked']) || ($status === 'in_progress' && rand(0, 1));

            TechIssue::firstOrCreate(
                ['title' => $title],
                [
                    'description' => $issueDescriptions[array_rand($issueDescriptions)],
                    'center_id' => $centerIds[array_rand($centerIds)],
                    'project_id' => $projectIds[array_rand($projectIds)] ?? null,
                    'reported_by' => $employeeUserIds[array_rand($employeeUserIds)],
                    'assigned_to' => rand(0, 1) ? $employeeUserIds[array_rand($employeeUserIds)] : null,
                    'status' => $status,
                    'priority' => $priorities[array_rand($priorities)],
                    'admin_response' => $hasResponse ? $responses[array_rand($responses)] : null,
                    'resolved_at' => $status === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 60)),
                ]
            );
            $count++;
        }

        $this->command->info('✓ تم بذر ' . $count . ' تذكرة فنية');
    }
}
