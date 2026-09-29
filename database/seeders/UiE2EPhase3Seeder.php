<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\EventCard;
use App\Models\Admin\Logistics\Asset;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\Logistics\PurchaseRequestItem;
use App\Models\Admin\Logistics\Warehouse;
use App\Models\Admin\Logistics\WarehouseItem;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\Permission;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Physiotherapy\PhysioRoom;
use App\Models\Admin\Physiotherapy\PhysioSession;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectActivity;
use App\Models\Admin\ProjectPath;
use App\Models\Admin\ProjectTask;
use App\Models\Admin\Tech\TechEquipment;
use App\Models\Admin\Tech\TechIssue;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية معزولة للمرحلة 3 (المشاريع/اللوجستيات/التقنية/العلاج الفيزيائي) لاختبارات tests/e2e/ui-phase-3.mjs.
 * تعتمد على UiE2ESeeder (المراكز والمشروع والحسابات). لا تُستدعى من DatabaseSeeder ولا تُستخدم في الإنتاج.
 *
 *   php artisan db:seed --class=UiE2ESeeder --force && php artisan db:seed --class=UiE2EPhase3Seeder --force
 *
 * حسابات إضافية (كلمة المرور password):
 *   ops@test.local           موظف بصلاحيات عرض/إضافة/تعديل على وحدات المشاريع واللوجستيات والتقنية والعلاج الفيزيائي بلا نطاق
 *   scoped@test.local        نفس الوحدات لكن بنطاق «مركز الرواد الرئيسي…» فقط (لاختبار عزل البيانات)
 *   readonly@test.local      عرض فقط (لا إضافة/تعديل/حذف)
 */
class UiE2EPhase3Seeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@test.local')->firstOrFail();
        $c1 = Center::where('name', 'like', 'مركز الرواد الرئيسي%')->firstOrFail();
        $c2 = Center::where('name', 'مركز ب')->firstOrFail();
        $project = Project::firstOrCreate(['name' => 'مشروع التعليم المجتمعي']);
        $project2 = Project::firstOrCreate(['name' => 'مشروع الصحة النفسية']);

        // ---- الحسابات ----
        $mk = fn (string $n, string $e) => User::updateOrCreate(['email' => $e], ['name' => $n, 'type' => 'employee', 'password' => bcrypt('password'),
            'must_change_password' => false, 'is_active' => true, 'email_verified_at' => now()]);
        $models = [
            'App\Models\Admin\Project', 'App\Models\Admin\ProjectPath', 'App\Models\Admin\ProjectTask', 'App\Models\Admin\ProjectActivity',
            'App\Models\Admin\MediaPlan', 'App\Models\Admin\MovementPlan', 'App\Models\Admin\EventCard', 'App\Models\Admin\Center',
            'App\Models\Admin\Logistics\PurchaseRequest', 'App\Models\Admin\Logistics\Warehouse', 'App\Models\Admin\Logistics\Asset',
            'App\Models\Admin\Logistics\ApprovalRule', 'App\Models\Admin\Logistics\LogisticsSetting',
            'App\Models\Admin\Tech\TechIssue', 'App\Models\Admin\Tech\TechEquipment', 'App\Models\User',
            'App\Models\Admin\Physiotherapy\PhysioPatient', 'App\Models\Admin\Physiotherapy\PhysioRoom',
            'page:admin.physiotherapy.followups.index', 'page:admin.physiotherapy.transfers.index', 'page:admin.physiotherapy.statistics.index',
        ];
        $grant = function (User $u, array $flags, array $scope = []) use ($models) {
            Permission::updateOrCreate(['user_id' => $u->id], array_merge(['model_names' => $models, 'can_view' => true, 'can_create' => false,
                'can_edit' => false, 'can_delete' => false], $flags, $scope));
        };
        $ops = $mk('موظف العمليات', 'ops@test.local');
        $scoped = $mk('موظف بنطاق مركز', 'scoped@test.local');
        $ro = $mk('موظف عرض فقط', 'readonly@test.local');
        $grant($ops, ['can_create' => true, 'can_edit' => true]);
        $grant($scoped, ['can_create' => true, 'can_edit' => true], ['center_id' => $c1->id]);
        $grant($ro, []);

        // ---- المشاريع ----
        $path = ProjectPath::firstOrCreate(['name' => 'مسار التنمية المجتمعية'], ['code' => 'PATH-1', 'description' => 'مسار تجريبي']);
        Project::whereKey($project->id)->update(['path_id' => $path->id, 'status' => 'active', 'code' => 'PRJ-EDU', 'description' => 'مشروع تجريبي للاختبارات']);
        Project::whereKey($project2->id)->update(['status' => 'studying', 'code' => 'PRJ-MH']);
        foreach ([[$c1, 'مهمة زيارة ميدانية — مركز رئيسي', 'pending'], [$c2, 'مهمة تدريب — مركز ب', 'in_progress'], [$c1, 'مهمة متأخرة اختبارية', 'pending']] as $i => [$c, $t, $st]) {
            ProjectTask::withTrashed()->firstOrCreate(['title' => $t], ['purpose' => 'غاية اختبارية', 'start_date' => now()->addDays($i)->toDateString(),
                'end_date' => now()->addDays($i + ($i === 2 ? -10 : 5))->toDateString(), 'assigned_to' => $ops->id, 'created_by' => $admin->id,
                'center_id' => $c->id, 'status' => $st]);
        }
        ProjectActivity::firstOrCreate(['responsible' => 'منسق النشاط', 'activity_date' => now()->toDateString()], ['project_id' => $project->id, 'center_id' => $c1->id,
            'beneficiary' => 'جهة مستفيدة', 'male_count' => 5, 'female_count' => 7, 'progress' => 'سير النشاط بشكل جيد', 'created_by' => $admin->id]);
        $plan = MediaPlan::firstOrCreate(['month_date' => now()->startOfMonth()->toDateString(), 'center_id' => $c1->id, 'project_id' => $project->id],
            ['created_by' => $admin->id, 'status' => 'draft', 'note' => 'خطة إعلامية تجريبية']);
        MediaPlanEvent::firstOrCreate(['media_plan_id' => $plan->id, 'event_name' => 'فعالية تغطية اختبارية'], ['event_date' => now()->addDays(3)->toDateString(), 'event_time' => '10:00', 'location' => 'قاعة الاختبار']);
        MovementPlan::firstOrCreate(['request_number' => 'MV-TEST-1'], ['created_by' => $admin->id, 'center_id' => $c1->id, 'project_id' => $project->id,
            'movement_date' => now()->addDays(2)->toDateString(), 'from_location' => 'دمشق', 'to_location' => 'حلب', 'purpose' => 'زيارة ميدانية', 'status' => 'review']);
        EventCard::firstOrCreate(['name' => 'بطاقة فعالية اختبارية'], ['project_id' => $project->id, 'center_id' => $c1->id, 'event_date' => now()->addDays(10)->toDateString(),
            'location' => 'قاعة', 'organizer' => 'المنظم', 'status' => 'draft', 'created_by' => $admin->id]);

        // ---- اللوجستيات ----
        $wh = Warehouse::firstOrCreate(['name' => 'مخزن المركز الرئيسي'], ['center_id' => $c1->id, 'notes' => 'مخزن تجريبي']);
        Warehouse::firstOrCreate(['name' => 'مخزن مركز ب'], ['center_id' => $c2->id]);
        WarehouseItem::firstOrCreate(['warehouse_id' => $wh->id, 'name' => 'ورق A4'], ['quantity' => 120, 'unit' => 'رزمة', 'status' => 'available', 'description' => 'مادة اختبارية']);
        Asset::firstOrCreate(['asset_code' => 'AST-001'], ['name' => 'حاسوب محمول', 'type' => 'أجهزة', 'center_id' => $c1->id, 'status' => 'جيد', 'room_number' => '12']);
        Asset::firstOrCreate(['asset_code' => 'AST-002'], ['name' => 'طاولة اجتماعات', 'type' => 'أثاث', 'center_id' => $c2->id, 'status' => 'صيانة']);
        foreach ([['PR-P3-1', 'pending', $c1, 1250.75], ['PR-P3-2', 'priced', $c1, 480.0], ['PR-P3-3', 'approved', $c2, 9800.5]] as [$no, $st, $c, $total]) {
            $pr = PurchaseRequest::firstOrCreate(['request_number' => $no], ['user_id' => $ops->id, 'specifications' => 'مواصفات '.$no, 'status' => $st, 'center_id' => $c->id,
                'project_id' => $project->id, 'quantity' => 5, 'unit' => 'قطعة', 'expected_unit_price' => $total / 5, 'expected_total_price' => $total]);
            PurchaseRequestItem::firstOrCreate(['purchase_request_id' => $pr->id, 'description' => 'بند '.$no], ['quantity' => 5, 'unit' => 'قطعة', 'unit_price' => $total / 5, 'total_price' => $total]);
        }

        // ---- التقنية ----
        TechEquipment::firstOrCreate(['name' => 'خادم الملفات', 'center_id' => $c1->id], ['type' => 'خادم', 'serial_number' => 'SN-123-ABC', 'condition' => 'b', 'room' => 'غرفة الخوادم']);
        TechEquipment::firstOrCreate(['name' => 'طابعة الطابق الثاني', 'center_id' => $c2->id], ['type' => 'طابعة', 'serial_number' => 'SN-999', 'condition' => 'd']);
        foreach ([['لا يعمل الإنترنت في المكتب', $c1, 'open', 'high'], ['طلب تثبيت برنامج', $c2, 'in_progress', 'low'], ['شاشة معطلة', $c1, 'completed', 'urgent']] as $i => [$t, $c, $st, $pr]) {
            TechIssue::firstOrCreate(['title' => $t], ['description' => 'وصف تفصيلي للتذكرة رقم '.($i + 1), 'center_id' => $c->id, 'project_id' => $project->id,
                'reported_by' => $ops->id, 'assigned_to' => $i === 1 ? $ops->id : null, 'status' => $st, 'priority' => $pr]);
        }

        // ---- العلاج الفيزيائي ----
        $room1 = PhysioRoom::firstOrCreate(['name' => 'غرفة العلاج 1', 'center_id' => $c1->id], ['description' => 'غرفة تجريبية', 'is_active' => true]);
        $room2 = PhysioRoom::firstOrCreate(['name' => 'غرفة العلاج 2', 'center_id' => $c2->id], ['is_active' => true]);
        foreach ([['مريض تجريبي أ', $c1, $room1, 'male', false], ['مريضة تجريبية ب', $c1, $room1, 'female', true], ['مريض تجريبي ج', $c2, $room2, 'male', false]] as [$n, $c, $r, $g, $tr]) {
            $p = PhysioPatient::firstOrCreate(['name' => $n], ['center_id' => $c->id, 'gender' => $g, 'phone' => '+963 944 000 111', 'registration_date' => now()->subDays(20)->toDateString(),
                'room_id' => $r->id, 'therapist_id' => $ops->id, 'is_transferred' => $tr, 'medical_history' => 'ملاحظات طبية تجريبية (ليست تشخيصًا)', 'created_by' => $admin->id]);
            foreach ([10, 5] as $k => $d) {
                PhysioSession::firstOrCreate(['patient_id' => $p->id, 'session_date' => now()->subDays($d)->toDateString()], ['session_number' => $k + 1, 'what_done' => 'تمرين مقاومة '.($k + 1), 'therapist_id' => $ops->id, 'created_by' => $admin->id]);
            }
        }
    }
}
