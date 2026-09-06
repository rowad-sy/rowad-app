<?php

namespace Database\Seeders;

/*
 * سيدر محلي مخصص لتجربة "الشريحة الأولى من إدارة المشاريع" فقط — لا يُضاف
 * إلى DatabaseSeeder الرئيسي. يُشغّل يدوياً:
 *
 *   php artisan db:seed --class=DemoPurchaseCycleSeeder
 *
 * ينشئ:
 *  - مناصب وظيفية وهمية لدورة الشراء (مسؤول مشروع / لوجستي / مدير مشروع / مدير المشاريع / مدير المالية)
 *  - مستخدمين تجريبيين (is_active، email_verified_at، must_change_password=false)
 *    مرتبطين بسجلات hr_employees بنطاقات حقيقية (مركز 7 = عفرين)
 *  - صفوف صلاحيات (model_names = PurchaseRequest + page:admin.project-manager.dashboard
 *    + page:admin.project-officer.dashboard بنطاقات صحيحة)
 *  - طلبات شراء في كل مراحل الدورة (pending/priced/pm_approved/pm2_approved/approved/executed/rejected)
 *    مع بنود وسجل workflow كامل.
 *  - قالبَي وثائق تجريبيين (بطاقة المشروع + فكرة المشروع) مع صلاحيات AnnexDocument/AnnexTemplate
 *    لنفس المستخدمين التجريبيين.
 *  - مسؤولاً إعلامياً تجريبياً + صلاحيات/بيانات الخطة الإعلامية (MediaPlan).
 *  - مسؤول حركة تجريبياً + صلاحيات/بيانات خطة الحركة (MovementPlan) عبر المراحل.
 *  - صلاحيات + بيانات تجريبية لصفحة إدارة المقررات الموحّدة (Course): ICDL بعروض
 *    (المشروع التقني)، كوافيرة بمستويات (المشروع المهني)، تاسع بمواد وامتحانات (أثر).
 *
 * الحسابات (كلمة السر: password):
 *  demo.officer@rowad.app   — مسؤول مشروع (مركز عفرين)
 *  demo.logistics@rowad.app — لوجستي مركز عفرين
 *  demo.pm@rowad.app        — مدير مشروع (مركز عفرين)
 *  demo.pm2@rowad.app       — مدير المشاريع
 *  demo.finance@rowad.app   — مدير المالية
 *  demo.media@rowad.app     — مسؤول إعلامي (مركز عفرين)
 *  demo.moveofficer@rowad.app — مسؤول حركة (مركز عفرين)
 */

use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MovementPlan;
use App\Models\Admin\MovementPlanRecipient;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoPurchaseCycleSeeder extends Seeder
{
    public function run(): void
    {
        $purchaseDone = PurchaseRequest::where('request_number', 'like', 'PR-2026-%')->exists();
        $annexDone = AnnexTemplate::where('key', 'like', 'DEMO-%')->exists();
        $mediaDone = Permission::where('model_names', 'like', '%MediaPlan%')->exists();
        $movementDone = Permission::where('model_names', 'like', '%MovementPlan%')->exists();
        $coursesDone = Course::where('name_ar', 'like', '%(تجريبي)%')->exists();

        if ($purchaseDone && $annexDone && $mediaDone && $movementDone && $coursesDone) {
            fwrite(STDOUT, "Demo purchase-cycle and annex data already exist — skipping.\n");
            return;
        }

        $center = Center::findOrFail(7);
        $project = Project::findOrFail(3);

        DB::transaction(function () use ($center, $project, $purchaseDone, $annexDone, $mediaDone, $movementDone, $coursesDone) {
            $officer  = $this->user('مسؤول مشروع - عفرين', 'demo.officer@rowad.app', 'مسؤول مشروع');
            $logistics = $this->user('لوجستي مركز عفرين', 'demo.logistics@rowad.app', 'لوجستي');
            $pm       = $this->user('مدير مشروع عفرين', 'demo.pm@rowad.app', 'مدير مشروع');
            $pm2      = $this->user('مدير المشاريع', 'demo.pm2@rowad.app', 'مدير المشاريع');
            $finance  = $this->user('مدير المالية', 'demo.finance@rowad.app', 'مدير المالية');

            $this->employee($officer, 'DEMO-PM-OFF', 'أحمد', 'المنسق', $center->id, $project->id, null);
            $this->employee($logistics, 'DEMO-PM-LOG', 'يامن', 'اللوجستي', $center->id, null, null);
            $this->employee($pm, 'DEMO-PM-MGR', 'خالد', 'المدير', $center->id, $project->id, null);
            $this->employee($pm2, 'DEMO-PM-DIR', 'مازن', 'الرئيسي', null, null, null);
            $this->employee($finance, 'DEMO-FIN-MGR', 'ريم', 'المالية', null, null, null);

            if (! $purchaseDone) {
                $this->purchaseCycle($officer, $logistics, $pm, $pm2, $finance, $center, $project);
            }

            if (! $annexDone) {
                $this->annexDemo($officer, $logistics, $pm, $pm2, $finance, $center);
            }

            if (! $mediaDone) {
                $this->mediaPlanDemo($officer, $pm, $pm2, $center, $project);
            }

            if (! $movementDone) {
                $this->movementPlanDemo($officer, $logistics, $pm, $pm2, $center, $project);
            }

            if (! $coursesDone) {
                $this->coursesDemo($officer, $pm, $pm2, $center);
            }

            $this->grantMediaCreateToPm($pm, $center);
            $this->grantTechIssueShortcut($officer, $pm, $center);
        });
    }

    private function purchaseCycle(
        User $officer,
        User $logistics,
        User $pm,
        User $pm2,
        User $finance,
        Center $center,
        Project $project,
    ): void {
        // ── الصلاحيات (لا تتكرّر لأن الجارد أعلاه يمنع إعادة التشغيل) ──
        Permission::create([
            'user_id' => $officer->id,
            'model_names' => ['App\\Models\\Admin\\Logistics\\PurchaseRequest'],
            'center_id' => $center->id,
            'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
        ]);
        Permission::create([
            'user_id' => $officer->id,
            'model_names' => ['page:admin.project-officer.dashboard'],
            'center_id' => $center->id,
            'can_view' => true,
        ]);
        Permission::create([
            'user_id' => $logistics->id,
            'model_names' => ['App\\Models\\Admin\\Logistics\\PurchaseRequest'],
            'center_id' => $center->id,
            'can_view' => true, 'can_edit' => true,
        ]);
        Permission::create([
            'user_id' => $pm->id,
            'model_names' => ['App\\Models\\Admin\\Logistics\\PurchaseRequest'],
            'center_id' => $center->id,
            'can_view' => true, 'can_edit' => true,
        ]);
        Permission::create([
            'user_id' => $pm->id,
            'model_names' => ['page:admin.project-manager.dashboard'],
            'center_id' => $center->id,
            'can_view' => true,
        ]);
        Permission::create([
            'user_id' => $pm2->id,
            'model_names' => ['App\\Models\\Admin\\Logistics\\PurchaseRequest'],
            'center_id' => $center->id,
            'can_view' => true, 'can_edit' => true,
        ]);
        Permission::create([
            'user_id' => $finance->id,
            'model_names' => ['App\\Models\\Admin\\Logistics\\PurchaseRequest'],
            'center_id' => $center->id,
            'can_view' => true, 'can_edit' => true,
        ]);

        // ── طلبات الشراء عبر المراحل ──
        $t0 = now()->subDays(7)->startOfDay();

        $this->request($officer, $center, $project, $t0, 'pending', [
            ['وصف' => 'ورق طباعة A4', 'ك' => 40, 'وحدة' => 'رزمة', 'س' => 0, 'ملاحظة' => 'يُسعَّر لاحقاً'],
            ['وصف' => 'حبر طابعات', 'ك' => 12, 'وحدة' => 'خرطوشة', 'س' => 0, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id);

        $this->request($officer, $center, $project, $t0->copy()->addHours(6), 'priced', [
            ['وصف' => 'كراسي مكتبية', 'ك' => 10, 'وحدة' => 'قطعة', 'س' => 15000, 'ملاحظة' => null],
            ['وصف' => 'طاولات اجتماعات', 'ك' => 2, 'وحدة' => 'قطعة', 'س' => 120000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addHours(6)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0142', $t0->copy()->addDays(1), 'B-2026-0142'),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0142');

        $lockedAt = $t0->copy()->addDays(2);
        $this->request($officer, $center, $project, $t0->copy()->addHours(9), 'pm_approved', [
            ['وصف' => 'أجهزة حاسوب محمول', 'ك' => 3, 'وحدة' => 'جهاز', 'س' => 450000, 'ملاحظة' => 'مواصفات قياسية'],
            ['وصف' => 'شاشات 24 بوصة', 'ك' => 3, 'وحدة' => 'شاشة', 'س' => 95000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addHours(9)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0150', $t0->copy()->addDays(1), 'B-2026-0150'),
            $this->step('pm_approved', 'pm_approved', $pm->id, $pm2->id, 'وافق مدير المشروع وقفل الطلب نهائياً', $lockedAt),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0150', $lockedAt, $pm->id);

        $this->request($officer, $center, $project, $t0->copy()->addHours(11), 'pm2_approved', [
            ['وصف' => 'ثلاجة حفظ الأدوية', 'ك' => 1, 'وحدة' => 'قطعة', 'س' => 850000, 'ملاحظة' => null],
            ['وصف' => 'سجلات حفظ طبية', 'ك' => 20, 'وحدة' => 'ملف', 'س' => 3000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addHours(11)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0201', $t0->copy()->addDays(1), 'B-2026-0201'),
            $this->step('pm_approved', 'pm_approved', $pm->id, $pm2->id, 'وافق مدير المشروع وقفل الطلب نهائياً', $t0->copy()->addDays(2)),
            $this->step('pm2_approved', 'pm2_approved', $pm2->id, $finance->id, 'وافق مدير المشاريع وأحاله لمدير المالية', $t0->copy()->addDays(3)),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0201', $t0->copy()->addDays(2), $pm->id);

        $approvedAt = $t0->copy()->addDays(4);
        $this->request($officer, $center, $project, $t0->copy()->addHours(14), 'approved', [
            ['وصف' => 'مولد كهرباء احتياطي', 'ك' => 1, 'وحدة' => 'جهاز', 'س' => 2200000, 'ملاحظة' => null],
            ['وصف' => 'سلك توصيل كهربائي', 'ك' => 2, 'وحدة' => 'شريط', 'س' => 15000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addHours(14)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0255', $t0->copy()->addDays(1), 'B-2026-0255'),
            $this->step('pm_approved', 'pm_approved', $pm->id, $pm2->id, 'وافق مدير المشروع وقفل الطلب نهائياً', $t0->copy()->addDays(2)),
            $this->step('pm2_approved', 'pm2_approved', $pm2->id, $finance->id, 'وافق مدير المشاريع وأحاله لمدير المالية', $t0->copy()->addDays(3)),
            $this->step('approved', 'approved', $finance->id, null, 'اعتمد مدير المالية الطلب نهائياً', $approvedAt),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0255', $t0->copy()->addDays(2), $pm->id, $approvedAt);

        $executedAt = $t0->copy()->addDays(5);
        $this->request($officer, $center, $project, $t0->copy()->addDays(4)->addHours(2), 'executed', [
            ['وصف' => 'دراجات هوائية للنشاط', 'ك' => 5, 'وحدة' => 'دراجة', 'س' => 130000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addDays(4)->addHours(2)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0300', $t0->copy()->addDays(4)->addHours(8), 'B-2026-0300'),
            $this->step('pm_approved', 'pm_approved', $pm->id, $pm2->id, 'وافق مدير المشروع وقفل الطلب نهائياً', $t0->copy()->addDays(4)->addHours(14)),
            $this->step('pm2_approved', 'pm2_approved', $pm2->id, $finance->id, 'وافق مدير المشاريع وأحاله لمدير المالية', $t0->copy()->addDays(4)->addHours(20)),
            $this->step('approved', 'approved', $finance->id, null, 'اعتمد مدير المالية الطلب نهائياً', $t0->copy()->addDays(5)->startOfDay()),
            $this->step('executed', 'executed', $logistics->id, null, 'تم تنفيذ طلب الشراء', $executedAt),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0300', $t0->copy()->addDays(4)->addHours(14), $pm->id, $t0->copy()->addDays(5)->startOfDay());

        $this->request($officer, $center, $project, $t0->copy()->addHours(16), 'rejected', [
            ['وصف' => 'مكيفات صحراوية', 'ك' => 6, 'وحدة' => 'جهاز', 'س' => 180000, 'ملاحظة' => null],
        ], [
            $this->step('create', 'pending', $officer->id, $logistics->id, 'تم إنشاء طلب الشراء وإحالته للتسعير', $t0->copy()->addHours(16)),
            $this->step('priced', 'priced', $logistics->id, $pm->id, 'تم التسعير — رقم الميزانية B-2026-0311', $t0->copy()->addDays(1)->addHours(2), 'B-2026-0311'),
            $this->step('rejected', 'rejected', $pm->id, null, 'رفض مدير المشروع الطلب لعدم كفاية الميزانية', $t0->copy()->addDays(2)->addHours(4)),
        ], $logistics->id, $pm->id, $pm2->id, $finance->id, 'B-2026-0311');

        fwrite(STDOUT, "Demo purchase-cycle users, permissions and requests created.\n");
    }

    private function annexDemo(
        User $officer,
        User $logistics,
        User $pm,
        User $pm2,
        User $finance,
        Center $center,
    ): void {
        // ── قالبان تجريبيان لوثائق المشاريع ──
        AnnexTemplate::create([
            'key' => 'DEMO-project-card',
            'title_ar' => 'بطاقة المشروع',
            'slug' => 'project-card',
            'version' => 1,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => ['وثيقة تعريف المشروع تُرفق في ملف المشروع الرئيسي وتبقى مرجعاً أثناء التنفيذ.'],
                'sections' => [
                    [
                        'key' => 'basic',
                        'title' => 'البيانات الأساسية',
                        'type' => 'fields',
                        'fields' => ['اسم المشروع', 'نوع المشروع', 'المنطقة', 'مدير المشروع', 'تاريخ البدء'],
                    ],
                    [
                        'key' => 'summary',
                        'title' => 'ملخص المشروع',
                        'type' => 'paragraph',
                        'assignee_role' => 'مدير المشروع',
                    ],
                    [
                        'key' => 'indicators',
                        'title' => 'المؤشرات المستهدفة',
                        'type' => 'table',
                        'columns' => ['المؤشر', 'القيمة المستهدفة', 'الوحدة'],
                    ],
                    [
                        'key' => 'outputs',
                        'title' => 'المخرجات الرئيسية',
                        'type' => 'list',
                    ],
                ],
            ],
        ]);

        AnnexTemplate::create([
            'key' => 'DEMO-project-idea',
            'title_ar' => 'فكرة المشروع',
            'slug' => 'project-idea',
            'version' => 1,
            'is_active' => true,
            'json_definition' => [
                'header_meta' => ['نموذج مقترح مشروع جديد — يُعبأ قبل رفع الوثيقة للاعتماد.'],
                'sections' => [
                    [
                        'key' => 'idea',
                        'title' => 'الفكرة',
                        'type' => 'paragraph',
                        'assignee_role' => 'مسؤول مشروع',
                    ],
                    [
                        'key' => 'problem',
                        'title' => 'المشكلة والحل',
                        'type' => 'fields',
                        'fields' => ['المشكلة المستهدفة', 'الحل المقترح', 'الفئة المستفيدة', 'الموقع المقترح'],
                    ],
                    [
                        'key' => 'budget',
                        'title' => 'الميزانية التقديرية',
                        'type' => 'table',
                        'columns' => ['البند', 'التكلفة التقديرية', 'ملاحظات'],
                    ],
                    [
                        'key' => 'risks',
                        'title' => 'المخاطر المحتملة',
                        'type' => 'list',
                    ],
                ],
            ],
        ]);

        // ── صلاحيات AnnexDocument / AnnexTemplate للمستخدمين التجريبيين ──
        $docModel = 'App\\Models\\Admin\\ProjectDocs\\AnnexDocument';
        $tplModel = 'App\\Models\\Admin\\ProjectDocs\\AnnexTemplate';

        foreach ([
            [$officer->id, [$docModel], true, true, true, true],
            [$logistics->id, [$docModel], true, false, false, false],
            [$pm->id, [$docModel], true, false, true, false],
            [$pm2->id, [$docModel, $tplModel], true, true, true, false],
            [$finance->id, [$docModel], true, false, false, false],
        ] as [$uid, $models, $view, $create, $edit, $delete]) {
            Permission::create([
                'user_id' => $uid,
                'model_names' => $models,
                'center_id' => $center->id,
                'can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete,
            ]);
        }

        fwrite(STDOUT, "Demo annex templates + permissions created.\n");
    }

    private function mediaPlanDemo(
        User $officer,
        User $pm,
        User $pm2,
        Center $center,
        Project $project,
    ): void {
        $media = $this->user('مسؤول إعلامي - عفرين', 'demo.media@rowad.app', 'مسؤول إعلامي');
        $this->employee($media, 'DEMO-PM-MED', 'ندى', 'الإعلامية', $center->id, $project->id, null);

        $model = 'App\\Models\\Admin\\MediaPlan';

        foreach ([
            [$officer->id, true, true, true, true],
            [$pm->id, true, false, false, false],
            [$pm2->id, true, false, false, false],
            [$media->id, true, false, true, false],
        ] as [$uid, $view, $create, $edit, $delete]) {
            Permission::create([
                'user_id' => $uid,
                'model_names' => [$model],
                'center_id' => $center->id,
                'can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete,
            ]);
        }

        $plan = MediaPlan::create([
            'month_date' => now()->firstOfMonth(),
            'center_id' => $center->id,
            'project_id' => $project->id,
            'created_by' => $officer->id,
            'note' => 'خطة إعلامية تجريبية — شهر ' . now()->locale('ar')->translatedFormat('F'),
        ]);

        $first = $events = $plan->events()->create([
            'event_date' => now()->firstOfMonth()->addDays(2),
            'office' => 'مكتب عفرين',
            'event_name' => 'ورشة توعية صحية',
            'day' => now()->firstOfMonth()->addDays(2)->locale('ar')->translatedFormat('l'),
            'event_time' => '10:00:00',
            'location' => 'مركز الرواد - عفرين',
            'responsible_user_id' => $officer->id,
            'summary' => 'ورشة توعية حول النظافة العامة يستفيد منها أهالي المنطقة.',
            'coverage_type' => 'تصوير + تقرير',
            'notes' => null,
        ]);

        $plan->events()->create([
            'event_date' => now()->firstOfMonth()->addDays(5),
            'office' => 'مكتب عفرين',
            'event_name' => 'توزيع مستلزمات مدرسية',
            'day' => now()->firstOfMonth()->addDays(5)->locale('ar')->translatedFormat('l'),
            'event_time' => '13:00:00',
            'location' => 'مدرسة الشهيد - عفرين',
            'responsible_user_id' => $officer->id,
            'summary' => 'توزيع حقائب وقرطاسية على الطلاب في بداية العام.',
            'coverage_type' => 'تقرير مصور',
            'notes' => null,
        ]);

        $first->comments()->create([
            'user_id' => $media->id,
            'comment' => 'الموعد مناسب لكن يفضل تقديمه إلى الساعة التاسعة صباحاً لتغطية أفضل للضوء.',
        ]);

        fwrite(STDOUT, "Demo media officer, MediaPlan permissions + sample plan created.\n");
    }

    private function movementPlanDemo(
        User $officer,
        User $logistics,
        User $pm,
        User $pm2,
        Center $center,
        Project $project,
    ): void {
        $moveOfficer = $this->user('مسؤول حركة - عفرين', 'demo.moveofficer@rowad.app', 'مسؤول حركة');
        $this->employee($moveOfficer, 'DEMO-PM-MOV', 'سائد', 'الحركة', $center->id, $project->id, null);

        $model = 'App\\Models\\Admin\\MovementPlan';

        foreach ([
            [$officer->id, true, false, false, false],
            [$logistics->id, true, false, false, false],
            [$pm->id, true, true, false, false],
            [$pm2->id, true, false, true, false],
            [$moveOfficer->id, true, true, true, false],
        ] as [$uid, $view, $create, $edit, $delete]) {
            Permission::create([
                'user_id' => $uid,
                'model_names' => [$model],
                'center_id' => $center->id,
                'can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete,
            ]);
        }

        $t0 = now()->subDays(2)->startOfDay();
        $number = fn (): string => 'MOV-' . now()->year . '-' . str_pad((string) (MovementPlan::count() + 1), 4, '0', STR_PAD_LEFT);

        // 1) بانتظار مراجعة إدارة المشاريع
        $review = MovementPlan::create([
            'request_number' => $number(),
            'created_by' => $pm->id,
            'center_id' => $center->id,
            'project_id' => $project->id,
            'movement_date' => now()->addDays(1)->toDateString(),
            'departure_time' => '09:00',
            'return_time' => '15:30',
            'from_location' => 'مكتب الرواد - عفرين',
            'to_location' => 'المخيمات الشرقية - عفرين',
            'purpose' => 'جولة ميدانية لمتابعة توزيع المستلزمات المدرسية على المستفيدين.',
            'notes' => 'خطة تجريبية بانتظار إدارة المشاريع.',
            'status' => 'review',
            'created_at' => $t0,
            'updated_at' => $t0,
        ]);
        $review->workflowActions()->create([
            'action' => 'create', 'status' => 'review', 'from_user_id' => $pm->id, 'to_user_id' => null,
            'note' => 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', 'created_at' => $t0, 'updated_at' => $t0,
        ]);

        $approvedAt = $t0->copy()->addHours(8);
        // 2) أُحيلت لمسؤول الحركة
        $approved = MovementPlan::create([
            'request_number' => $number(),
            'created_by' => $pm->id,
            'center_id' => $center->id,
            'project_id' => $project->id,
            'movement_date' => now()->addDays(2)->toDateString(),
            'departure_time' => '08:00',
            'return_time' => '12:00',
            'from_location' => 'مكتب الرواد - عفرين',
            'to_location' => 'المخبز الآلي',
            'purpose' => 'نقل مواد غذائية للمركز من المخبز الآلي.',
            'notes' => null,
            'refer_to_movement_officer_id' => $moveOfficer->id,
            'status' => 'approved',
            'created_at' => $t0->copy()->addHours(2),
            'updated_at' => $approvedAt,
        ]);
        $approved->workflowActions()->create([
            'action' => 'create', 'status' => 'review', 'from_user_id' => $pm->id, 'to_user_id' => null,
            'note' => 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', 'created_at' => $t0->copy()->addHours(2), 'updated_at' => $t0->copy()->addHours(2),
        ]);
        $approved->workflowActions()->create([
            'action' => 'approve', 'status' => 'approved', 'from_user_id' => $pm2->id, 'to_user_id' => $moveOfficer->id,
            'note' => 'وافقت إدارة المشاريع وأُحيلت لمسؤول الحركة', 'created_at' => $approvedAt, 'updated_at' => $approvedAt,
        ]);

        $assignedAt = $t0->copy()->addDays(1)->addHours(3);
        // 3) قيد المتابعة (متعدد المتابعين)
        $assigned = MovementPlan::create([
            'request_number' => $number(),
            'created_by' => $pm->id,
            'center_id' => $center->id,
            'project_id' => $project->id,
            'movement_date' => now()->addDays(3)->toDateString(),
            'departure_time' => '10:00',
            'return_time' => '17:00',
            'from_location' => 'مكتب الرواد - عفرين',
            'to_location' => 'مناطق متفرقة - عفرين',
            'purpose' => 'جولة توعية صحية ميدانية بمشاركة الفريق اللوجستي.',
            'notes' => null,
            'refer_to_movement_officer_id' => $moveOfficer->id,
            'assigned_by' => $moveOfficer->id,
            'assigned_at' => $assignedAt,
            'status' => 'assigned',
            'created_at' => $t0->copy()->addHours(5),
            'updated_at' => $assignedAt,
        ]);
        foreach ([
            [$logistics->id, 'لوجستي'],
            [$officer->id, 'تنسيق ميداني'],
            [$pm->id, 'مدير مشروع'],
        ] as [$uid, $label]) {
            MovementPlanRecipient::create([
                'movement_plan_id' => $assigned->id,
                'user_id' => $uid,
                'role_label' => $label,
                'created_at' => $assignedAt,
                'updated_at' => $assignedAt,
            ]);
        }
        $assigned->workflowActions()->create([
            'action' => 'create', 'status' => 'review', 'from_user_id' => $pm->id, 'to_user_id' => null,
            'note' => 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', 'created_at' => $t0->copy()->addHours(5), 'updated_at' => $t0->copy()->addHours(5),
        ]);
        $assigned->workflowActions()->create([
            'action' => 'approve', 'status' => 'approved', 'from_user_id' => $pm2->id, 'to_user_id' => $moveOfficer->id,
            'note' => 'وافقت إدارة المشاريع وأُحيلت لمسؤول الحركة', 'created_at' => $t0->copy()->addHours(5)->addHours(3), 'updated_at' => $t0->copy()->addHours(5)->addHours(3),
        ]);
        $assigned->workflowActions()->create([
            'action' => 'assign', 'status' => 'assigned', 'from_user_id' => $moveOfficer->id, 'to_user_id' => null,
            'note' => 'حدّد مسؤول الحركة المستفيدين للمتابعة', 'created_at' => $assignedAt, 'updated_at' => $assignedAt,
        ]);

        $completedAt = $t0->copy()->addDays(1)->addHours(9);
        // 4) منجزة
        $completed = MovementPlan::create([
            'request_number' => $number(),
            'created_by' => $pm->id,
            'center_id' => $center->id,
            'project_id' => $project->id,
            'movement_date' => $t0->copy()->subDays(1)->toDateString(),
            'departure_time' => '07:30',
            'return_time' => '11:15',
            'from_location' => 'مكتب الرواد - عفرين',
            'to_location' => 'المقر الرئيسي - منطقة الشمال',
            'purpose' => 'تسليم مستندات المشروع لإدارة المشاريع.',
            'notes' => 'تمت بنجاح.',
            'refer_to_movement_officer_id' => $moveOfficer->id,
            'assigned_by' => $moveOfficer->id,
            'assigned_at' => $t0->copy()->addDays(1)->addHours(2),
            'completed_at' => $completedAt,
            'status' => 'completed',
            'created_at' => $t0->copy()->subHours(2),
            'updated_at' => $completedAt,
        ]);
        MovementPlanRecipient::create([
            'movement_plan_id' => $completed->id,
            'user_id' => $logistics->id,
            'role_label' => 'لوجستي',
            'created_at' => $t0->copy()->addDays(1)->addHours(2),
            'updated_at' => $t0->copy()->addDays(1)->addHours(2),
        ]);
        foreach ([
            ['create', 'review', $pm->id, null, 'تم إنشاء خطة الحركة وإحالتها لإدارة المشاريع', $t0->copy()->subHours(2)],
            ['approve', 'approved', $pm2->id, $moveOfficer->id, 'وافقت إدارة المشاريع وأُحيلت لمسؤول الحركة', $t0->copy()->addHours(3)],
            ['assign', 'assigned', $moveOfficer->id, null, 'حدّد مسؤول الحركة المستفيدين للمتابعة', $t0->copy()->addDays(1)->addHours(2)],
            ['complete', 'completed', $moveOfficer->id, null, 'أُنجزت خطة الحركة', $completedAt],
        ] as [$action, $status, $from, $to, $note, $at]) {
            $completed->workflowActions()->create([
                'action' => $action, 'status' => $status, 'from_user_id' => $from, 'to_user_id' => $to,
                'note' => $note, 'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        fwrite(STDOUT, "Demo movement officer, MovementPlan permissions + sample plans created.\n");
    }

    private function coursesDemo(User $officer, User $pm, User $pm2, Center $center): void
    {
        $model = 'App\\Models\\Admin\\Student\\Course';

        foreach ([
            [$officer->id, true, true, true, false],
            [$pm->id, true, true, true, false],
            [$pm2->id, true, true, true, false],
        ] as [$uid, $view, $create, $edit, $delete]) {
            Permission::create([
                'user_id' => $uid,
                'model_names' => [$model],
                'center_id' => $center->id,
                'can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete,
            ]);
        }

        $makeExams = function (Course $course, string $subjectName, int $sortOrder): void {
            $subject = $course->subjects()->firstOrCreate(
                ['name_ar' => $subjectName],
                ['sort_order' => $sortOrder]
            );
            $subject->exams()->firstOrCreate(
                ['name_ar' => 'امتحان قبلي', 'type' => 'pre'],
                ['max_score' => 20, 'sort_order' => 0]
            );
            $subject->exams()->firstOrCreate(
                ['name_ar' => 'امتحان نهائي', 'type' => 'final'],
                ['max_score' => 100, 'sort_order' => 1]
            );
        };

        // 1) المشروع التقني (3): ICDL بعروض صباحية/مسائية (مسمّيات أنواع التدريب)
        $tech = Project::find(3);
        if ($tech) {
            $period = Period::updateOrCreate(
                ['project_id' => $tech->id, 'name_ar' => 'الفترة الأولى 2026'],
                [
                    'year' => now()->year,
                    'start_date' => now()->startOfYear(),
                    'end_date' => now()->endOfYear(),
                    'is_active' => true,
                ]
            );

            $icdl = Course::updateOrCreate(
                ['project_id' => $tech->id, 'name_ar' => 'ICDL — أساسيات الحاسوب (تجريبي)'],
                [
                    'name_en' => 'ICDL Basics',
                    'description' => 'دورة ICDL شاملة بمسارات صباحية ومسائية.',
                    'duration' => 60,
                ]
            );
            $icdl->periods()->syncWithoutDetaching([$period->id]);
            $makeExams($icdl, 'مقدمة في نظم التشغيل', 0);
            $makeExams($icdl, 'معالجة النصوص Word', 1);
            $makeExams($icdl, 'الجداول الإلكترونية Excel', 2);
            $icdl->offerings()->firstOrCreate(
                ['period_id' => $period->id, 'name_ar' => 'دورة صباحية'],
                ['session_time' => '09:00 - 12:00']
            );
            $icdl->offerings()->firstOrCreate(
                ['period_id' => $period->id, 'name_ar' => 'دورة مسائية'],
                ['session_time' => '15:00 - 18:00']
            );
        }

        // 2) المشروع المهني (2): كوافيرة بمستويات تدريب
        $vocational = Project::find(2);
        if ($vocational) {
            $hair = Course::updateOrCreate(
                ['project_id' => $vocational->id, 'name_ar' => 'كوافيرة (تجريبي)'],
                [
                    'name_en' => 'Hairdressing',
                    'description' => 'دورة تصفيف شعر بمستويين تدريبيين.',
                    'duration' => 90,
                ]
            );
            foreach ([['مستوى أول', 0], ['مستوى ثانٍ', 1]] as [$name, $order]) {
                $hair->levels()->firstOrCreate(
                    ['name_ar' => $name],
                    ['project_id' => $hair->project_id, 'type' => 'level', 'sort_order' => $order]
                );
            }
            $makeExams($hair, 'أساسيات الكوافير', 0);
            $makeExams($hair, 'قص وتصفيف', 1);
        }

        // 3) المشروع الأكاديمي (18): تاسع بمواد وامتحانات شبه كاملة
        $academic = Project::find(18);
        if ($academic) {
            $gradeNine = Course::updateOrCreate(
                ['project_id' => $academic->id, 'name_ar' => 'تاسع (تجريبي)'],
                [
                    'name_en' => 'Grade 9',
                    'description' => 'صف تاسع بمواده وامتحاناته (قبلي/نهائي).',
                    'duration' => 180,
                ]
            );
            $makeExams($gradeNine, 'الرياضيات', 0);
            $makeExams($gradeNine, 'اللغة العربية', 1);
            $makeExams($gradeNine, 'العلوم', 2);
        }

        fwrite(STDOUT, "Demo course permissions + sample courses created (ICDL/كوافيرة/تاسع).\n");
    }

    private function grantMediaCreateToPm(User $pm, Center $center): void
    {
        $row = Permission::where('user_id', $pm->id)
            ->where('model_names', 'like', '%MediaPlan%')
            ->first();

        if ($row) {
            if (! $row->can_create) {
                $row->update(['can_create' => true, 'center_id' => $center->id]);
                fwrite(STDOUT, "Granted MediaPlan create to demo.pm (existing row updated).\n");
            }
            return;
        }

        Permission::create([
            'user_id' => $pm->id,
            'model_names' => ['App\\Models\\Admin\\MediaPlan'],
            'center_id' => $center->id,
            'can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false,
        ]);
        fwrite(STDOUT, "Granted MediaPlan create to demo.pm.\n");
    }

    private function grantTechIssueShortcut(User $officer, User $pm, Center $center): void
    {
        foreach ([$officer, $pm] as $u) {
            $row = Permission::where('user_id', $u->id)
                ->where('model_names', 'like', '%TechIssue%')
                ->first();

            if ($row) {
                if (! $row->can_create) {
                    $row->update(['can_view' => true, 'can_create' => true, 'center_id' => $center->id]);
                    fwrite(STDOUT, "Granted TechIssue create to {$u->email} (existing row updated).\n");
                }
                continue;
            }

            Permission::create([
                'user_id' => $u->id,
                'model_names' => ['App\\Models\\Admin\\Tech\\TechIssue'],
                'center_id' => $center->id,
                'can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false,
            ]);
            fwrite(STDOUT, "Granted TechIssue create to {$u->email}.\n");
        }
    }

    private function user(string $name, string $email, string $jobTitleAr): User
    {
        $job = JobPosition::firstOrCreate(
            ['title_ar' => $jobTitleAr],
            ['title_en' => str_replace(' ', '-', $jobTitleAr)]
        );

        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'type' => 'employee',
                'job_title_id' => $job->id,
            ]
        );
    }

    private function employee(User $user, string $code, string $first, string $last, ?int $centerId, ?int $projectId, ?int $cohortId): Employee
    {
        return Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_code' => $code,
                'first_name_ar' => $first,
                'last_name_ar' => $last,
                'gender' => ($first === 'ريم' || $first === 'سارة') ? 'female' : 'male',
                'status' => 'active',
                'center_id' => $centerId,
                'project_id' => $projectId,
                'cohort_id' => $cohortId,
            ]
        );
    }

    private function step(string $action, string $status, ?int $from, ?int $to, ?string $note, Carbon $at): array
    {
        return compact('action', 'status', 'from', 'to', 'note', 'at');
    }

    private function request(
        User $officer,
        Center $center,
        Project $project,
        Carbon $createdAt,
        string $status,
        array $items,
        array $steps,
        int $logisticsId,
        int $directManagerId,
        int $pm2Id,
        int $financeId,
        ?string $budgetNumber = null,
        ?Carbon $lockedAt = null,
        ?int $lockedBy = null,
        ?Carbon $approvedAt = null,
    ): PurchaseRequest {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['ك'] * $item['س'];
        }

        $pr = PurchaseRequest::create([
            'request_number' => 'PR-2026-' . str_pad((string) (PurchaseRequest::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'user_id' => $officer->id,
            'center_id' => $center->id,
            'project_id' => $project->id,
            'specifications' => collect($items)->pluck('وصف')->implode('، '),
            'expected_total_price' => $total,
            'status' => $status,
            'notes' => 'طلب شراء تجريبي — دورة الموافقات ' . $status,
            'refer_to_logistics_id' => $logisticsId,
            'refer_to_direct_manager_id' => $directManagerId,
            'refer_to_pm2_id' => $pm2Id,
            'refer_to_finance_id' => $financeId,
            'budget_number' => $budgetNumber,
            'locked_at' => $lockedAt,
            'locked_by' => $lockedBy,
            'approved_at' => $approvedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        foreach ($items as $item) {
            $lineTotal = $item['ك'] * $item['س'];
            $pr->items()->create([
                'description' => $item['وصف'],
                'quantity' => $item['ك'],
                'unit' => $item['وحدة'],
                'unit_price' => $item['س'],
                'total_price' => $lineTotal,
                'notes' => $item['ملاحظة'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        foreach ($steps as $step) {
            $pr->workflowActions()->create([
                'action' => $step['action'],
                'status' => $step['status'],
                'from_user_id' => $step['from'],
                'to_user_id' => $step['to'],
                'note' => $step['note'],
                'created_at' => $step['at'],
                'updated_at' => $step['at'],
            ]);
        }

        return $pr;
    }
}