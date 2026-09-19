# رواد — سجل متابعة العمل (Progress Tracker)

> هذا الملف لتتبع حالة العمل حتى تعود إليه في جلسة لاحقة.
> آخر تحديث: 2026-09-19

---

## المرحلة: التصميم الجديد + المسارات + بطاقة الفعالية + الهوية البصرية (2026-09-19) ✅

### الهوية البصرية الجديدة (دفء مجتمعي / نمط داكن تقني)
- `resources/css/app.css`: نظام متغيّرات CSS كامل. الفاتح = دفء مجتمعي (برتقالي/ذهبي/كريمي)، الداكن = نمط داكن تقني (`[data-theme="dark"]`).
- ربط متغيرات Bootstrap (`--bs-primary`, `btn-primary`, `bg-primary`, `table-light`…) بالهوية → كل الصفحات القديمة تبنّت الهوية تلقائياً.
- مبدّل الوضع الليلي/النهاري في `resources/js/app.js` (يحفظ في localStorage، زر في الترويسة/البوابة/تخطيط الدخول).
- اللوغو: `public/images/logo.png` + `public/favicon.ico` (مولّد من اللوغو) موصول في كل التخطيطات.

### الصفحة الرئيسية = تسجيل دخول فقط
- `/` للضيف → يحوّل لصفحة `/login` التي تعيد تصميم `components/layouts/auth-bootstrap.blade.php` باللوغو والهوية.

### البوابة بعد الدخول (`admin.portal`)
- `PortalController@portal` + `admin/portal/index.blade.php`: لوغو في الوسط وأربع أزرار دائرية (ملف تعريفي PDF خارجي، المعرفات، الفعاليات، شجرة المسارات) + زر «الذهاب للوحة التحكم» أعلى.
- تحويل الموظفين بعد الدخول إلى البوابة (login.blade + مسار `/`).

### المعرفات والفعاليات
- `admin.identities`: صفحة أنيقة بمعرّفات المؤسسة (موقع، فيس، إنستا، إكس، لينكدإن، يوتيوب، تيلغرام، واتساب، إيميل).
- `admin.events-timeline`: خط زمني يدمج بطاقات الفعاليات المعتمدة + أحداث الخطط الإعلامية (قادمة/سابقة).

### المسارات (المسار = مجموعة مشاريع)
- هجرة `project_paths` + أعمدة `projects`: `path_id`, `status` (فعال/تحت الدراسة/مغلق/معلق/داخلي), `code`.
- `ProjectPath` + تحديث `Project` (STATUSES/STATUS_BADGES/statusLabel).
- `ProjectPathController`: CRUD + اختيار عدة مشاريع + `tree` (شجرة قابلة للطي، حالات ملوّنة) + تصدير Excel (`BaseExport`) + PDF (`window.print` A4 أفقي مع اللوغو يساراً).
- **نُقلت روابط المشاريع والمسارات إلى قسم «إدارة المشاريع»** في الشريط الجانبي (أُزيلت من «الإدارة»).

### بطاقة الفعالية (`App\Models\Admin\EventCard`)
- هجرة `event_cards` بكل حقول النموذج الورقي (فقرات/لوجستيات/مشتريات/إعلام/مواصلات/موازنة بتكرار JSON + تقييم).
- تعتمد نظام الإحالة (`ManagesReferrals` + `RecordsWorkflow`) بخطوتَي `event_approve` ثم `event_finalize`.
- الدورة: مدير المشروع ينشئ ويحيل **لشخص واحد عبر قائمة منسدلة** → يوافق → تُحال لمدير المشاريع → يعتمد.
- `EventCardController` (index/create/store/show/edit/update/approve/reject/finalize/refer) + واجهات كاملة.
- أُضيفت لوحة مدير المشروع: بطاقات فعالياتي + «بانتظار موافقتي».

### صفحة المشروع المخصصة (Hub)
- `ProjectHubController` + `admin.projects.overview`: رأس بمشروع + حالة + اختصارات (طلاب/وثائق/تقرير شهري/خطة إعلامية/حركة/طلبات شراء/بطاقات فعاليات/إحصائيات) كلها مفلترة على المشروع.
- يُفتح من الشجرة أو من قائمة المشاريع. أُضيف فلتر `project_id` لواجهتي الخطط الإعلامية وخطط الحركة.

### الصلاحيات
- أُضيف `App\Models\Admin\EventCard` و `App\Models\Admin\ProjectPath` إلى `PermissionModelCatalog` ضمن «إدارة المشاريع».

### ملاحظات للتشغيل
- `php artisan migrate` تمّ. `npm run build` تمّ. كل المسارات الجديدة تمّت الموافقة عليها باختبار HTTP حي (بوابة/معرفات/خط زمني/شجرة/تصدير/دورة البطاقة كاملة 200).

---

## الهدف الكلي
تصميم وتنفيذ نظام تعليمي يغطي:
- **الروضة** (المستويات/الطفولة، مدرّس صف يعلّم كل المواد، مواد مشتركة لعدة صفوف)
- **رواد العلم** (صفوف 1-8)
- **أثر** (صفوف 9-12، مسار علمي/أدبي، مادة بأكثر من مدرّس)
- **معهد التدريب المهني** (مستويات تدريب: خياطة، كوافيرة...)
- **معهد التطوير الإداري** (دورات/دبلومات بمقررات ومواد)
- **خطة تدريبية أسبوعية** (جداول دروس متكررة بأسماء الدروس، مع منع تعارض المدرّس)

---

## المرحلة 1 — مكتملة (مقررات + مواد + عروض + درجات)

### الجداول الجديدة (هاجرّت بنجاح)
| الجدول | الغرض |
|---|---|
| `subjects` | مواد المقرر (One-to-Many مع courses). `course_id` nullable |
| `course_offerings` | عروض المقرر بمدرّس (حلّ ICDL صباحاً/مساءً): course+period+instructor_id |
| `student_subject_grades` | درجات كل مادة (student_enrollment_id + subject_id + grade) |

### موديلات جديدة (app/Models/Admin/Student/)
- `Subject`, `CourseOffering`, `StudentSubjectGrade`
- علاقات مضافة: `Course->subjects()`, `Course->offerings()`, `Period->offerings()`, `StudentEnrollment->grades()`

### واجهات
- `courses/form.blade.php`: أقسام المواد والعروض قابلة للتكرار (JS) + زر "درجات المواد" في ملف الطالب
- `StudentGradeController` + `students/enrollments/grades.blade.php`: إدخال درجات المواد (فورم منفصل) مع **متوسط موزون** حسب وزن كل مادة يُحدّث `student_enrollments.grade`

---

## المرحلة 2 — مكتملة (الصفوف/المستويات + الخطط التدريبية + واجهة الإدارة)

> آخر تحديث: 2026-08-29 — اكتملت واجهة إدارة المستويات وأُصلح خطأ في مزامنة الدروس.

### الجداول الجديدة (هاجرّت بنجاح)
| الجدول | الغرض |
|---|---|
| `academic_levels` | المستويات/الصفوف (الطفولة، الصف 9...، مستويات التدريب): project_id + name + type |
| `level_subjects` | pivot: أيّ مستويات → أي مواد (المواد لكل صف) |
| `level_subject_instructors` | مدرّسو المادة في الصف (متعدد، يحل "مادة بأكثر من مدرّس") |
| `training_plans` | خطة تدريبية لكل مشروع (دورة N شهر): start/end/status |
| `training_plan_lessons` | دروس أسبوعية: plan+level+subject+instructor+week+day+time+lesson_name |

### موديلات جديدة
`AcademicLevel`, `LevelSubjectInstructor`, `TrainingPlan`, `TrainingPlanLesson`
- علاقات: `Project->academicLevels()`, `Project->trainingPlans()`, `Subject->levels()`, `AcademicLevel->subjects()/subjectInstructors()/lessons()`

### عرض (شبه مكتمل — يعمل)
- `students/levels/index.blade.php` + `show.blade.php`: قائمة المستويات حسب المشروع + مواد/مدرّسو كل مستوى
- `students/training-plans/index.blade.php` + `show.blade.php`: قائمة الخطط + الجدول الأسبوعي (أسابيع/أيام/دروس/مدرّسون)
- روابط القائمة الجانبية (master.blade.php): "المستويات والصفوف" + "الخطط التدريبية"

### إنشاء/تعديل الخطة التدريبية — مكتملة ومُختبَرة ✅
- `TrainingPlanController`: `create/store/edit/update` + `syncLessons()` مع اكتشاف تعارض المدرّس.
- **إصلاح خطأ (مهم)**: كانت `syncLessons()` تحذف الدروس المُنشأة حديثاً لأن `whereNotIn('id', [])` يمسح الكل عندما تكون القائمة فارغة، وبسبب ترتيب العمليات (إنشاء ثم حذف). عولج بنقل الحذف **قبل** الحلقة (نفس نمط `CourseController`) — الآن الإنشاء/التعديل/الحذف/الكشف عن التعارض يعمل.
- `training-plans/form.blade.php`: نموذج إنشاء/تعديل مع قائمة دروس قابلة للتكرار (JS).
- Routes مضافة: `students.training-plans.create/store/edit/update`.

### واجهة إدارة المستويات/الصفوف — مكتملة ✅ (كانت غير موجودة)
- `AcademicLevelController`: أُضيفت `create/store/edit/update` + `syncSubjectAssignments()`.
- `levels/form.blade.php`: نموذج إنشاء/تعديل مستوى (حقول أساسية + قائمة مواد قابلة للتكرار، مع تحديد مدرّسي كل مادة (متعدد) والمدرّس الرئيسي عبر راديو مدعوم بـ JS).
- Routes مضافة: `students.levels.create/store/edit/update`.
- أزرار "إضافة مستوى/صف" في `levels/index` و"تعديل" في `levels/show`.
- **اختبار منطق المزامنة**: تأكد من عمل ربط المواد وإضافة/إزالة المدرّسين وتعيين المدرّس الرئيسي (اختبار آلي ناجح).

### إصلاح: زر التعديل المفقود في الخطط التدريبية ✅
- كانت صفحة `training-plans/index` تعرض زر "الجدول" فقط (يذهب إلى show) وصفحة `show` بلا زر تعديل، رغم أن مسار `edit` موجود ويعمل — تخفي المستخدم.
- أُضيف زر "تعديل" في `training-plans/index` (عمود الإجراءات، مغلق بصلاحية edit) وزر "تعديل" في `training-plans/show`.
- أعد `view:cache` وتم التأكيد. جرّب في المتصفح.

### ⚠️ اختبارات جارية (تحتاج تأكيداً نهائياً)
- `view:cache`: ✅ نجح (شامل النماذج الجديدة).
- نموذج الخطة (create/edit): ✅ كود الإصلاح مختبَر آلياً (إنشاء/تعديل/حذف/تعارض).
- نموذج المستوى (create/edit): ✅ منطق المزامنة مختبَر آلياً — يُفضَّل فتحه في المتصفح للتحقق البصري.

---

## المرحلة 3 — تكملة "الفوج" (Cohort) وإدارة المستويات والصلاحيات

> آخر تحديث: 2026-08-30 — اكتمل ربط الطالب بالفوج (فلترة JS) وعرضه في قائمة الطلاب، وأضيفت أفواج صباحي/مسائي في السيدر.

### الهدف
طبقة "فوج" (cohort): مسؤول يحصل على صلاحية على مركز + مشروع + فوج فقط، ولا يرى إلا فوجه. الفوج **مرتبط بالمشروع فقط**، والفلترة بالفوج **على الطلاب فقط**.

### مكتمل
- **Migration** `2026_08_30_000001_create_cohorts_table.php` + `..._000002_add_cohort_to_employees_table.php` — مُهاجَرتان بنجاح.
- **CohortController** (CRUD كامل) + views `admin/cohorts/{index,form}` + مسار `Route::resource('cohorts')` + بند "الأفواج" في القائمة الجانبية.
- **نظام الصلاحيات**: `PermissionHelper::can()` مع `$cohortId` + `getEffectiveScope()` تُرجع `cohort_ids` (sees_all يتطلب النطاقات الثلاثة null). `CheckPermission` يمرر `cohort_id` من الموظف الحالي. واجهة `permissions/form` + `index`.
- **نموذج الطالب**: حقل `cohort_id` (select يفلترون حسب المشروع عبر `data-project-id`) + تمرير `$cohorts` و `$defaultCohortId` من `$userEmployee->cohort_id`.
- **قائمة الطلاب**: فلتر "الفوج" + عمود بشارة الفوج (مع `with('cohort')`).
- **فلترة JS** `filterCohorts()` في `students/form.blade.php` تُظهر الأفواج المُطابقة للمشاريع المختارة فقط (تُستدعى مع `filterCoursesByProjects()`).
- **السيدر**: أضيف `cohort()` helper + فوج صباحي (KD-M) ومسائي (KD-E) للروضة (N=4 أفواج).

### إصلاح خطأ (مهم)
- `Class "App\Models\Admin\Student\Cohort" not found` عند `/admin/students` — كان `Student.php` يستخدم `Cohort::class` بدون `use` ضمن namespace `Admin\Student`؛ أُضيف `use App\Models\Admin\Cohort;`. ركض `optimize:clear`.

### ملاحظة بيانات (الروضة/رواد العلم)
- عند اختيار مشروع الروضة أو رواد العلم في نموذج الطالب، **لا تظهر مقررات** — السبب: لا يوجد أي مقرر `project_id=16 (الروضة)` أو `17 (رواد العلم)` في DB (كل المقررات للمشاريع 2/18/19). الفلترة البرمجية `filterCoursesByProjects()` سليمة.
- قرار آلية تسجيل طلاب الروضة/المشاريع (مقررات أم مستويات/صفوف) **مؤجل** من المستخدم.

### تحقق
- ✅ `php artisan view:cache` نجح.
- ✅ `php -l` على `ScenarioDemoSeeder` و `StudentController` لا أخطاء.
- ✅ `db:seed --class=ScenarioDemoSeeder` نجح وأنشأ الأفواج.

---

## المرحلة 4 — حقول الموظف في المستخدم (مسمى وظيفي + مركز + مشروع)

> آخر تحديث: 2026-09-01 — أُضيفت حقول وظيفية لمستخدمي نوع "موظف" مع فلاتر وبحث واستيراد/تصدير Excel.

### الهدف
عندما يكون نوع المستخدم = "موظف"، تظهر حقول: **المسمى الوظيفي** (من `hr_job_positions`) + **المركز** + **المشروع**. الغاية: البحث المباشر عن الموظف أو مسماه، والفلترة في الجدول الرئيسي بالمركز/المشروع. مستقلة عن جدول الموظفين في الموارد البشرية.

### مكتمل
- **Migration** `2026_09_01_075848_add_employee_fields_to_users_table.php`: أضاف `job_title_id` (FK→hr_job_positions), `center_id` (FK→centers), `project_id` (FK→projects) إلى `users` (nullable، nullOnDelete). **مُهاجَر**.
- **User model**: fillable + علاقات `jobTitle()`, `center()`, `project()`.
- **UserController**:
  - `index`: +`with(['jobTitle','center','project'])` + فلاتر (center_id/project_id/job_title_id) + بحث يشمل `jobTitle.title_ar/title_en` + `official_email`.
  - `create`/`edit`: يمرر `$centers`, `$projects`, `$jobTitles`.
  - `store`/`update`: validation + حفظ `job_title_id/center_id/project_id`.
  - `export`/`import`: تصدير/استيراد Excel.
- **users/form.blade.php**: قسم "بيانات الموظف" (`employee-fields`) يظهر عند type=employee: قائمة مسمى (من hr_job_positions) + مركز + مشروع — مع toggle JS.
- **users/index.blade.php**: فلاتر (المركز/المشروع/المسمى) + عمودا "المسمى الوظيفي" و"المركز / المشروع" (تظهر عندما type=employee أو الكل) + أزرار تصدير/استيراد + Modal استيراد.
- **UserExport** (`app/Exports/UserExport.php`): 8 أعمدة (الاسم/البريد/الرسمي/النوع/المسمى/المركز/المشروع/الحالة) مع ترجمة الأنواع والحالات + تطبيق الفلاتر.
- **UserImport** (`app/Imports/UserImport.php`): قراءة **موضعية** للأعمدة (تجنّباً لمشكلة transliteration للعربية في `WithHeadingRow`)، يحلّل المسمى/المركز/المشروع من الأسماء إلى IDs (وينشئ مسمى جديداً إن لم يوجد)، ينشئ user غير مفعّل بكلمة مرور افتراضية `Password@123`.
- **Routes**: `users.export` (GET) و `users.import` (POST) قبل resource (لتجنب تعارض `{user}`).

### ملاحظة تقنية (مهمة)
`Maatwebsite\Excel` مع `WithHeadingRow` يحوّل رؤوس الأعمدة **العربية** إلى مفاتيح مفرّغة صوتياً (`الاسم`→`alasm`، `البريد الإلكتروني`→`albryd_alalktrony`). لذلك اعتمد الـ Import على القراءة **بالترتيب الموضعي** (index 0=الاسم ... 7=الحالة) بدلاً من أسماء الأعمدة — يضمن التوافق مع ملف الـ Export.

### تحقق
- ✅ `php -l` لكل الملفات المعدّلة.
- ✅ `php artisan view:cache` نجح.
- ✅ `php artisan route:list --path=users` — المسارات صحيحة وقبل `{user}`.
- ✅ اختبار save/retrieve للحقول (omar employee ← مسمى/مركز/مشروع) ثم reset.
- ✅ اختبار Export (ملف صحيح 8 أعمدة) + Import (يُنشئ user ويحلّل المسمى/المركز/المشروع) — نجح وعُوّدت البيانات نظيفة.
- ✅ `/admin/users` و `/admin/users/create` يعرضان 200 عبر HTTP kernel (سوبر أدمن).

---

## المرحلة 5 — الشريحة الأولى من الدراسة (إدارة المشاريع + دورة طلب الشراء)

> آخر تحديث: 2026-09-05 — اكتملت دورة طلب الشراء كاملة (تسعير مقيد → مدير مباشر يوقّع ويقفل → مدير مشاريع → مالية → تنفيذ) مع داشبوردي "مدير المشروع" و"مسؤول المشروع"، وبيانات وهمية قابلة للتجربة.

### الهدف
الشريحة الأولى من ملف الدراسة `project-files/stage 1/إدارة المشاريع`: لوحتا **مدير المشروع** و**مسؤول المشروع** + دورة طلب الشراء متعددة المراحل مقيدة الخطوات (صاحب الخطوة الحالية فقط يتصرف) مع قفل الطلب نهائياً بعد موافقة المدير المباشر.

### مكتمل ✅
- **داشبورد مدير المشروع** `admin/project-manager/dashboard.blade.php` + **داشبورد مسؤول المشروع** `admin/project-officer/dashboard.blade.php` — بطاقات إحصائية + بطاقات دورة الشراء بحالاتها + جدول آخر الطلبات بشارات ملونة. الكونترولران يمرران `$scopeCenter/$scopeProject/$scopeCohort`. محميان بـ `@canPermission`.
- **صفحة تسعير مقيد** `logistics/purchase-requests/price.blade.php`: بند + `unit_price` لكل بند + إجمالي فوري (JS) + رقم ميزانية، ويُحال الطلب للمدير المباشر بعد الحفظ.
- **صفحة عرض الطلب** `show.blade.php` أُعيد بناؤها: دورة الموافقات (اللوجستي/المدير/مدير المشاريع/المالية)، بطاقة التوقيع، قسم الإجراء المتاح لكل حالة (المرسل إليه فقط)، سجل زمني `workflowActions`، وتبويب "مقفول نهائياً" بعد التوقيع.
- **قرارات الكونترولر**: `price()` (التسعير المقيد بفحص `refer_to_logistics_id`)، `managerDecide()` (موافقة تُقفل الطلب `locked_at/locked_by` + إحالة pm2)، `pm2Decide()` (إحالة مالية)، `financeDecide()` (اعتماد نهائي)، `execute()` (التنفيذ للوجستي/سوبر أدمن فقط)، `destroy()` ممنوع على المقفول (403).
- **اختيارات الإحالة الافتراضية (14.7)**: `refer_to_pm2_id` ← صلاحية عالمية لصفحة PM ← أول مرشح؛ `refer_to_finance_id` ← سوبر أدمن ← أول مرشح؛ مع `$candidates` للقوائم.
- **الصلاحيات**: `page:admin.project-manager.dashboard` (نطاق مركز أو عام) و`page:admin.project-officer.dashboard` (نطاق مركز). روابط في الـ sidebar وبطاقة في `admin/home`.
- **السيدر الوهمي** `DemoPurchaseCycleSeeder`: مستخدمون (12–16) + موظفون (41–45) + صلاحيات (8–14) + **7 طلبات شراء** بأرقام **#10–16** تمثل كل حالة (pending/priced/pm_approved/pm2_approved/approved/executed/rejected) مع بنود وworkflowActions.
- **إصلاح أعمدة الإحالة**: جميع نداءات `$this->request(...)` السبعة في السيدر تمرر معرفات `$logistics/$pm/$pm2/$finance` — بعد تنظيف الطلبات القديمة (`cleanup_demo.php` بحذف PR-2026-* القديمة) وإعادة السيدر، التحقق `verify_seed2.php` أظهر الحقول مملوءة (L=13 PM=14 PM2=15 FIN=16).

### الاختبار النهائي ⚓ الكامل
`php artisan tinker` لـ `cycle_test.php` (راجع بالرقم المرجعي `request_number` بدلاً من الـ id الصلب):
- **31 فحصاً كلها PASS** — أذونات العشرة + الدورة كاملة (price→managerDecide→pm2Decide→financeDecide→execute) على PR-2026-00001 داخل transaction+rollback + منع destroy على المقفول + مسار reject على PR-2026-00004.
- ملاحظات تقنية: `Request::setContainer` غير موجود في Laravel 12 (`validate()` macro تستخدم `validator()` العام)؛ `assertEq` تقارن قيماً رقمية بمسامحة النوع (int vs float)؛ تتبّع الفشل عبر `$GLOBALS` (نطاق tinker المحلي غير النطاق العام).

### تحقق
- ✅ `php -l` على كل الملفات المعدّلة.
- ✅ `php artisan view:cache` نجح.
- ✅ السيدر أُعيد تشغيله بنجاح بعد التنظيف.
- ✅ جدول المستخدمين من 3-9 → 10-16 (بعد تنظيف الطلبات القديمة وإعادة البذر).

### بيانات الاختبار
- المقام الأدوار: demo.officer / demo.logistics / demo.pm / demo.pm2 / demo.finance @ `rowad.app` — كلمة المرور `password`.
- المركز 7 = عفرين، المشروع 3 = معهد الرواد للعلوم التقنية؛ jobs: 8=مسؤول مشروع، 9=لوجستي، 10=مدير مشروع، 11=مدير المشاريع، 12=مدير المالية.

---

## المرحلة 6 — وثائق المشاريع (نظام annex: قوالب JSON + وثائق + اعتمادات + طباعة A4)

> آخر تحديث: 2026-09-05 — اكتمل نظام وثائق المشاريع: قوالب بأقسام JSON محرّرة، وثائق تُنشأ من القوالب بملكية/قفل قسم، دورة حالة (مسودة → قيد المراجعة → معتمد/مرفوض) مع إعادة فتح، وطباعة A4 بهوية المؤسسة.

### الهدف
مرحلة أ من ملف الدراسة: قوالب وثائق مرنة تُعرَّف بـ JSON (أقسام من أنواع fields/paragraph/table/list + بيانات رأس اختيارية)، وتُنشأ من كل قالب وثائق بأقسام يُعبّئها الموظفون (مع إمكانية قفل قسم)، ثم دورة اعتماد موثّقة عبر سجل اعتمادات (توقيع) مع مراجعة/رفض/إعادة فتح.

### الجداول الجديدة (مُهاجَرت — `2026_09_05_000003_create_annex_document_tables`)
| الجدول | الغرض |
|---|---|
| `annex_templates` | قوالب الوثائق: `key` فريد + `title_ar` + `version` + `json_definition` (sections + header_meta اختياري) |
| `annex_documents` | الوثائق: template/version + title + project/center/period + status (draft/under_review/approved/rejected) + created_by + signed_at |
| `annex_document_blocks` | أقسام الوثيقة: `block_key` = section.key + `json_value` (JSON) + `updated_by` + `locked` |
| `annex_signoffs` | سجل الاعتمادات: user + role_label + action (approve/reject/comment) + note |

### موديلات جديدة
`AnnexTemplate` / `AnnexDocument` / `AnnexDocumentBlock` / `AnnexSignoff` (`app/Models/Admin/ProjectDocs/`)
- `AnnexTemplate->sections()/headerMeta()`؛ `AnnexDocument->sections()/isBlockComplete()/missingSections()/isLocked()` مع `RecordsWorkflow`.

### كنترولر / مسارات
- `AnnexTemplateController` (CRUD بلا show؛ رفع `version` عند تغيّر `json_definition`) + `AnnexDocumentController` (index/show/create/store/edit/update/submit/reopen/signoff/printDocument/destroy).
- مسارات يدوية في `routes/web.php` ضمن `admin.project-docs.*` (تتضمن `documents.submit/reopen/signoff/print` — ليست resource).
- `reopen()`: فقط لـ `rejected` — يفتح كل الأقسام (`locked=false`) ويعيد `draft` ويسجّل `logWorkflow('reopen', ...)` ثم يوجّه لصفحة التعبئة. **يقضي على الاتجاه السابق** (كانت إعادة الفتح زراً داخل نموذج signoff).

### أبرز القرارات
- كل قسم = كتلة واحدة بقيمتها `json_value` (JSON) بدل أعمدة ثابتة — أي قالب جديد يُضاف بلا هجرة.
- `submit()` يقفل كل القطع؛ `approve` يقرّ نهائياً مع `signed_at`؛ `reject` يبقي القطع مقفولة حتى `reopen` (زر في صفحة العرض تظهر للمنشئ فقط).
- صفحة `show` مركز إجراءات الحالة (إرسال/إعادة فتح/توقيع/تعليق/حذف/طباعة)؛ صفحة `edit` للتعبئة فقط مع تنبيهات حسب الحالة.
- `print` عرض HTML مستقل (بلا master) — `@page A4` + هوية المؤسسة + 3 خانات توقيع + تذييل مولّد من النظام.

### السيدر
- أُضيف شقّ annex إلى `DemoPurchaseCycleSeeder` (idempotent لكل قسم): قالبا `DEMO-project-card` (بطاقة المشروع) و`DEMO-project-idea` (فكرة المشروع) + صلاحيات AnnexDocument/AnnexTemplate للمستخدمين 12–16 (مسؤول/لوجستي/مدير/مدير مشاريع/مالية) في نطاق مركز 7.

### التحقق
- ✅ `php artisan migrate` نجح (جدول annex الأربعة).
- ✅ `php -l` على كل ملفات المرحلة (كنترولرات/موديلات/migration/سيدر/مسارات).
- ✅ `php artisan view:cache` نجح.
- ✅ إعادة تشغيل السيدر أنشأ القالبين التجريبيين + 5 صفوف صلاحيات annex (من دون تكرار بيانات دورة الشراء).
- ✅ فحص tinker شامل: إنشاء ← تعبئة 4 أقسام (fields/paragraph/table/list) ← submit (under_review وكل القطع مقفلة) ← reject ← **reopen** (draft وفكّ القفل) ← submit ← approve (signed_at) — الشريط الزمني `create > update > submit > reject > reopen > submit > approve` — وكل الصفحات (show/edit/print/index/forms) تُعرض بلا أخطاء.
- ملاحظة فحص: تعبئة القيم مباشرة عبر query-builder (`->update()`) تتجاوز محوّل JSON فيفشل عمود `json` — التطبيق الحقيقي يستخدم نموذج `$block->update()` فيعمل، والفحص عُدّل ليطابقه.

### بيانات الاختبار
- القالبان التجريبيان ظاهران في المتصفح من رابط "قوالب الوثائق": بطاقة المشروع، فكرة المشروع.
- الأدوار: demo.officer(12) / demo.logistics(13) / demo.pm(14) / demo.pm2(15) / demo.finance(16) — كلمة السر `password`.

---

## المرحلة 7 — الخطة الإعلامية (Media Plan)

> آخر تحديث: 2026-09-05 — اكتمل تنفيذ وحدة الخطة الإعلامية وفق مواصفة `project-files/stage 1/الخطة الإعلامية.txt`: خطة شهرية بمجموعة فعاليات + منع تعارض المواعيد (غير فريد في DB + فحص في الكنترولر) + تعليق الإعلامي على المواعيد.

### الجداول الجديدة (مُهاجَرت — `2026_09_05_000004_create_media_plans_table.php`)
| الجدول | الغرض |
|---|---|
| `media_plans` | الخطة: `month_date` + center_id/project_id + created_by + note |
| `media_plan_events` | فعاليات الخطة بالعشرة أعمدة (التاريخ/المكتب/اسم الفعالية/اليوم/الساعة/الموقع/المسؤول/الملخص/نوع التغطية/ملاحظات) مع **unique** `media_plan_events_no_conflict` على `(media_plan_id, event_date, event_time)` |
| `media_plan_comments` | تعليق الإعلامي على فعالية (event + user + comment) |

### موديلات جديدة
- `MediaPlan` (+`conflicts()` تُرجع مفاتيح (تاريخ|ساعة) المتكررة لعرضها)، `MediaPlanEvent` (علاقات plan/responsible/comments)، `MediaPlanComment`.

### كنترولر / مسارات / واجهات
- `MediaPlanController`: index/create/store/show/edit/update/destroy + `storeEvent` (بفحص التعارض ضد DB) + `destroyEvent` + `addComment`. فحص تعارض ضمن الدفعة (`assertBatchHasNoConflicts`) + التقاط `QueryException` كشبكة أمان. عند الحاجة يُملأ المركز/المشروع تلقائياً من سجل الموظف؛ اليوم يُحسب تلقائياً من التاريخ إن تُرك فارغاً.
- مسارات `admin.media-plans.*` (ضمن مجموعة admin) + `admin.media-plans.events.store/destroy/comment`.
- مشاهدات: `admin/media-plans/{index, form, show}` + جزئية `_event_row` (صف فعالية قابل للتكرار بالـ JS).
- `PermissionController::modelGroups()`: أُضيف `MediaPlan` ضمن "إدارة المشاريع" + رابط "الخطة الإعلامية" في sidebar (أيقونة megaphone) مغلق بـ `@canPermission`.

### السيدر
- أُضيف شقّ `mediaPlanDemo()` إلى `DemoPurchaseCycleSeeder` (idempotent لكل قسم): مستخدم `demo.media@rowad.app` (مسؤول إعلامي - عفرين، الموظف DEMO-PM-MED) + 4 صفوف صلاحيات MediaPlan (officer=full، pm/pm2=view، media=view+edit) + خطة تجريبية بفعاليتين وتعلّق من الإعلامي.

### التحقق
- ✅ `php artisan migrate` نجح.
- ✅ `php -l` على الكنترولر/الموديلات/migration/السيدر/المسارات/`PermissionController`.
- ✅ `php artisan view:cache` نجح (شامل ثلاث مشاهدات + الجزئية).
- ✅ `php artisan route:list --name=media-plans` — كل المسارات مسجّلة صحيحة.
- ✅ إعادة تشغيل السيدر أنشأ المستخدم/الصلاحيات/الخطة التجريبية من دون تكرار بيانات دورة الشراء.
- ✅ فحص tinker (FFQCN عبر stdin): إنشاء خطة + فعاليات → `conflicts()` = 0 → إدراج فعالية بنفس (التاريخ+الساعة) رفضه **unique constraint** → تعليق إعلامي (comments=1) → حذف نظيف.

### بيانات الاختبار
- `demo.media@rowad.app` — مسؤول إعلامي (مركز عفرين)، كلمة السر `password`.
- الخطة التجريبية لشهر الآن: فعاليتان (ورشة توعية صحية + توزيع مستلزمات مدرسية) + تعليق الإعلامي على الأولى.

### مواصفة الوحدة (المرجعية)
- فورم: يملؤها **مسؤول المشروع** بين 25–30 من الشهر؛ يراها **مسؤول / مدير / مسؤول إعلامي للمركز نفسه**.
- عمود الفعالية: التاريخ، المكتب، اسم الفعالية، اليوم، الساعة، موقع الفعالية، المسؤول عن الفعالية، ملخص الفعالية، نوع التغطية المطلوبة، ملاحظات.
- منطق العمل: لا يجوز أكثر من فعالية بنفس الوقت (تعارض)، والإعلامي يستطيع إبداء رأيه بالمواعيد (تعليق).

---

## المرحلة 8 — خطة الحركة (Movement Plan) + بطاقات الوصول السريع

> آخر تحديث: 2026-09-05 — اكتمل تنفيذ وحدة خطة الحركة (بلا مواصفة — حسب الوصف الشفهي) مع بطاقات "وصول سريع" في لوحتَي مدير/مسؤول المشروع وزر التذكرة التقنية وصلاحية إنشاء الخطة الإعلامية لمدير المشروع.

### الجداول الجديدة (مُهاجَرت — `2026_09_05_000005_create_movement_plans_table.php`)
| الجدول | الغرض |
|---|---|
| `movement_plans` | الخطة: request_number (MOV-السنة-000X) + created_by + مركز/مشروع + movement_date + departure/return_time + from/to_location + purpose + notes + refer_to_movement_officer_id + assigned_by/at + completed_at + status + reason |
| `movement_plan_recipients` | متابِعون متعددون: movement_plan_id + user_id + role_label + note (مستقل عن جذر الخطة) |

### دورة العمل (داخل الكنترولر)
1. مدير المشروع ينشئ → `review` → إدارة المشاريع **approve** (مع اختيار `movement_officer_id`) وستحيل لمسؤول الحركة، أو **reject** لسبب.
2. مسؤول الحركة (approved) → **assign** بحذف القديم وإعادة إنشاء المتابِعين (سائق/مدير مركز/لوجستي/الكل) → `assigned`.
3. **complete** → `completed`. كل خطوة تسجَّل `workflowActions` وتظهر في صفحة show.

### نفذ: موديلات/كنترولر/مسارات/مشاهدات
- موديلان `MovementPlan` (RecordsWorkflow + STATUSES + creator/center/project/movementOfficer/assigner/recipients) و`MovementPlanRecipient`.
- `MovementPlanController` (middleware صلاحيات view/create/edit/delete + index بنطاق حسب الدور + تفريغ `nextRequestNumber` بـ `MovementPlan::count()`). فحص يدوي: إذا حُدِّد وقتا الانطلاق/العودة معاً وكان العودة أسبق → `back()->withErrors(['return_time' => 'وقت العودة يجب أن يكون بعد وقت الانطلاق.'])` (لا قاعدة `after` لأن العودة قد تكون اليوم التالي).
- مسارات `admin.movement-plans.*` (group: index/create/store/show/approve/reject/assign/complete/destroy) بعد مجموعة media-plans.
- مشاهدات: `index` (فلاتر status/center + جدول + audit + حذف + ترقيم)، `form` (نموذج إنشاء)، `show` (تفاصيل + جدول المتابِعين + سجل الحركة + لوحات إجراءات حسب status)، وجزئية `_recipient_row` (صف متابِع قابل للتكرار بـ `__INDEX__` + إضافة/حذف JS).

### بطاقات الوصول السريع
- جزئية `resources/views/admin/partials/_shortcuts.blade.php` تُضمَّن في داشبوردَي مدير ومسؤول المشروع؛ كل بطاقة مغلقة بـ `@canPermission`: خطة حركة جديدة/خطط الحركة (MovementPlan)، خطة إعلامية جديدة (MediaPlan-create)، تذكرة تقنية جديدة (TechIssue-create)، طلب شراء جديد (PurchaseRequest-create). رابط "خطة الحركة" في sidebar أيقونة truck.

### السيدر (`DemoPurchaseCycleSeeder`)
- شقّ `movementPlanDemo()` idempotent: مستخدم `demo.moveofficer@rowad.app` (مسؤول حركة - عفرين، DEMO-PM-MOV) + 5 صفوف صلاحيات MovementPlan (pm=view+create، pm2=view+edit، moveOfficer=view+create+edit، officer/logistics=view) + 4 خطط تجريبية (review/approved/assigned بثلاثة متابعين/completed) بسجل workflow كامل.
- `grantMediaCreateToPm()`: تفعيل create لـ MediaPlan لحساب demo.pm (idempotent).
- `grantTechIssueShortcut()`: منح view+create لـ TechIssue لـ demo.pm و demo.officer (idempotent).

### التحقق
- ✅ `php artisan migrate` مُهاجَرت جداول الحركة.
- ✅ `php -l` على الكنترولر/الموديلات/السيدر/`PermissionController` بلا أخطاء.
- ✅ `php artisan view:cache` نجح (شامل المشاهدات الجديدة + جزئية `_recipient_row` + بطاقات الاختصارات).
- ✅ `route:list --name=movement-plans` — المسارات التسعة كلها مسجّلة.
- ✅ السيدر: أنشأ شقّ الحركة + صلاحيات MediaPlan-create لـ pm وTechIssue، وإعادة تشغيله تجاوزه بدور (idempotent).
- ✅ فحص tinker (FQCN عبر stdin): index بـ demo.pm (4 خطط) → store → approve (pm2) → assign (multi-recipients=2) → complete (workflow 4 خطوات)؛ فحص نطاق index بـ demo.officer؛ فحص حارس وقت العودة (302 + error «وقت العودة يجب أن يكون بعد وقت الانطلاق»); reject (pm2) → status rejected + reason. حُذفت بيانات الفحص بعدها (بقي 4 مخططات تجريبية).
- ✅ **اختبارات Pest**: `tests/Feature/MovementPlanTest.php` **(19 اختباراً)** + `tests/Feature/DashboardShortcutsTest.php` **(3 اختبارات)** — **22/22 نجحت (74 تأكيداً)**: guest/403/إنشاء/فشل فحص وقت العودة/approve/رفض الأطوار غير المطابقة/reject/assign متعدد + استبدال المستفيدين/complete/نطاق index (غير المرتبطين لا يرون خطط الآخرين، والمتابِعون يرون)/delete، **وعرض صفحات create + show في كل أطوار الدورة (review/approved/assigned/completed/rejected)**، **وعرض لوحتَي مدير/مسؤول المشروع مع بطاقات الوصول السريع كلها** (و403 بدونهما). البيانات عبر sqlite :memory: + RefreshDatabase — بلا أي مكتبة إضافية.
- ✅ **إصلاح جذري (403)**: 5 اختبارات كانت تفشل بـ403 لأن معاملات دالة الكنترولر كانت `$plan` بينما معامل المسار `{movement_plan}` — فيفشل Route Model Binding ويُحقن الـ Model فارغاً (id=NULL) → `abort_if($plan->status !== …)` يُطلق 403 حتى لمستخدم مخوّل. أُعيدت التسمية إلى `$movementPlan` في `show/approve/reject/assign/complete/destroy` فنجحت كل الفحوص.
- ✅ **إعادة تعيين المتابِعين**: `assign()` يسمح الآن بـ approved **أو** assigned (إعادة توزيع ما دامت الخطة قيد المتابعة) — يستبدل المستفيدين السابقين (delete ثم create)؛ رفضُه في review ما زال 403.
- ✅ حُذف `tests/Feature/DebugApproveTest.php` (تشخيص مؤقت).
- ملاحظة: اختبارات قالب Breeze/Volt الافتراضية (Auth/Dashboard/Settings/Registration) فاشلة مسبقاً في هذا المشروع (تشير إلى `Route [dashboard]` / `settings.profile` غير المعرّفة بعد تغيير التوجيه إلى لوحات حسب الدور) — لا علاقة لها بالمرحلة 8.

### بيانات الاختبار
- `demo.moveofficer@rowad.app` — مسؤول حركة (مركز عفرين)، كلمة السر `password`.
- 4 مخططات تجريبية: review (بانتظار إدارة المشاريع) / approved (أُحيلت لمسؤول الحركة) / assigned (3 متابِعين: لوجستي + تنسيق ميداني + مدير مشروع) / completed.

### ملاحظة
- خطة الحركة بُنيت بلا مواصفة مكتوبة — أي مواصفة رسمية لاحقاً قد تتطلب تعديل دورة الإجراءات/الحقول.

---

## المرحلة 9 — إدارة المقررات الموحّدة (صفحة واحدة لكل ملحقات المقرر)

> آخر تحديث: 2026-09-05 — اكتملت صفحة إدارة المقررات الموحّدة (مقرر + مستويات + مواد + امتحانات بعلاماتها + عروض من صفحة واحدة) مع زر "خطة تدريبية" لكل مقرر وصفحة مساعدة شاملة وبطاقة اختصار في لوحة مدير المشروع، واختبارات Pest 13/13 + قسم demo في `DemoPurchaseCycleSeeder`.

### الجداول الجديدة (مُهاجَرت)
| الملف | المحتوى |
|---|---|
| `2026_09_05_000006_create_subject_exams_table.php` | امتحانات المواد: subject_id (cascade) + name_ar + type (pre/post/quiz/final/other) + max_score (العلامة العليا) + sort_order |
| `2026_09_05_000007_add_course_id_to_academic_levels_table.php` | ربط المستوى بالمقرر: academic_levels.course_id (nullable + nullOnDelete + index) |

### موديلات / علاقات
- `SubjectExam` (جديد): exams of each subject.
- `Subject->exams()` HasMany (مرتب sort_order)؛ `Course->levels()` HasMany (بـ course_id)؛ `AcademicLevel->course_id` (fillable) + علاقة `course()`.

### CourseController (`app/Http/Controllers/Admin/Student/CourseController.php`)
- ثابت `LEVEL_TYPES` (grade/level/childhood/kindergarten/course).
- Middleware: view يشمل `help()` → `admin.students.courses.help`.
- `index()` موحّد: withCount (subjects/offerings/levels) + subjects بـ withCount exams + `$examsTotal` لكل مقرر + `->get()` بلا paginate (عدد المقررات محدود).
- `store/update` موسّعان: تحقق لـ levels/subjects/exams/offerings (بضمنها `*.id`) + `syncLevels()`/`syncSubjects()`/`syncExams()`/`syncOfferings()` (إنشاء/تحديث/حذف بنمط whereNotIn قبل الحلقة؛ صف الامتحان الفارغ الاسم يُتجاهل).
- **إصلاح مهم**: `$request->validate()` تُسقط مفاتيح الصفوف غير المعرّفة في القواعد (`subjects.*.id` إلخ) — فكان المكررون يُحذفون ويُعاد إنشاؤهم في كل حفظ بدل التحديث. أُضيفت قواعد `levels.*.id` / `subjects.*.id` / `subjects.*.exams.*.id` / `offerings.*.id` في store **و** update فصار التحديث في مكانه (تحقّق آلياً: subject/exam ids ثابتة بعد الحفظ).

### مشاهدات
- `courses/index.blade.php` أُعيدت بالكامل: بطاقات مقررات بعدادات (مواد/امتحانات/مستويات/عروض/فترات)، أزرار "خطة تدريبية" (تذهب لـ `admin.students.training-plans.create?course_id=...`) وتعديل/حذف/سجل، رأس بزري "معلومات ونصائح" و"إضافة مقرر"، فلاتر بحث/مشروع، حالة فارغة.
- **زر "خطة تدريبية" يجهّز النموذج تلقائياً (مكتمل الآن)**: `TrainingPlanController@create` يستقبل `course_id` — عند وروده يحمّل المقرر (project/levels/subjects) ويقيّد قائمتَي الصفوف/المستويات والمواد بعناصره ويعيد `$defaultProjectId`. النموذج: `project_id` مسبوق التحديد + بانر "تم تجهيز النموذج تلقائياً من المقرر" + `levels`/`subjects` المقيّدة سراً وعبر JS (خانة `$defaultProjectId` بلا أثر في وضع التعديل).
- `courses/form.blade.php` أُعيدت بالكامل: أساسيات + مكررات مستويات (اسم/نوع/رمز) ومواد (اسم/ساعات/وزن) وبكل مادة مكررات امتحانات (اسم/نوع/علامة عليا) وعروض (فترة/اسم/مدرّس/وقت) — JS عبر `@push('scripts')`.
- `courses/help.blade.php` (جديد): دليل شامل — المعمارية، أمثلة حسب طبيعة المشروع (أثر/الروضة/المهني/الإداري)، خطوة بخطوة، شرح زر "خطة تدريبية"، تعديل/حذف، ملاحظات (نطاق المشروع، العلامة العليا ليست نتيجة طالب).

### بطاقة اختصار
- أُضيفت بطاقة **"إدارة المقررات"** في `admin/partials/_shortcuts.blade.php` (مغلقة بـ `@canPermission('App\Models\Admin\Student\Course','view')`) → تظهر في لوحتَي مدير ومسؤول المشروع.

### اختبارات Pest (`tests/Feature/CourseTest.php` — 13 اختباراً/68+ تأكيداً، 13/13 ✅)
- guest→login / 403 بلا view / عرض index.
- store كامل: مقرر + periods sync + مستويات (مع course_id و project_id) + مواد + امتحانات (max_score) + عروض.
- 403 بلا create؛ عرض form مع create وممنوع بدونه.
- update في مكانه: إعادة تسمية مادة، تحديث علامة امتحان، حذف مادة/امتحان محذوفين، تجاهل صف امتحان فارغ الاسم.
- 403 بلا edit؛ destroy بلا delete ممنوع ومع delete يحذف التوابع cascade (والمستويات تفقد course_id بـ nullOnDelete).
- صفحة help للعارض وممنوعة بدون صلاحية.
- نطاق index: مستخدم بمشروع واحد يرى مقرراته فقط.
- بطاقة الاختصار "إدارة المقررات" تظهر في لوحة مدير المشروع.
- صفحة إنشاء الخطة التدريبية مع `?course_id=` تجهّز المشروع وقوائم المادة/الصف من المقرر (وتستبعد عناصر مقرر آخر).

### التحقق
- ✅ `php artisan migrate` — المهاجرتان نجحتا.
- ✅ `php -l` على CourseController بلا أخطاء.
- ✅ `php artisan view:cache` نجح (شامل index/form/help الجديدة).
- ✅ `route:list --name=courses` — 7 مسارات صحيحة.
- ✅ `CourseTest` 13/13 + `MovementPlanTest` + `DashboardShortcutsTest` — إجمالي 36/36 (146+ تأكيداً).
- ✅ `php artisan test` الكامل: 12 فشل فقط (نفس فشلات Breeze/Volt السابقة) — لا جديد.
- ✅ `DemoPurchaseCycleSeeder` — يُشغَّل مرتين بنجاح: المرة الأولى تُنشئ البيانات، المرة الثانية تتجاهل (idempotent) بمؤشر مبني على بيانات `(تجريبي)` في `name_ar`.

### سيدر Demo للمقررات (`DemoPurchaseCycleSeeder` — `coursesDemo()`)
- يُشغَّل يدوياً: `php artisan db:seed --class=DemoPurchaseCycleSeeder --force` — يتجاهل تلقائياً إن وُجد مقرر "(تجريبي)" في DB.
- ينشئ:
  - **ICDL** (project 3 — التقني): Period "الفترة الأولى 2026" + 3 مواد (نظم تشغيل/Word/Excel) بامتحاني قبلي (20) ونهائي (100) + عرضان صباحية (09:00–12:00) ومسائية (15:00–18:00).
  - **كوافيرة** (project 2 — المهني): مستويان (أول/ثانٍ) + مادتان بامتحانين.
  - **تاسع** (project 18 — أثر): 3 مواد (رياضيات/عربية/علوم) بامتحانين.
- صلاحيات Course (+can_view +can_create +can_edit) لـ officer/pm/pm2 في centre 7.
- يُنشئ fallback الم chaired demo.officer/pm/pm2 وemploys في الدور ذاته (user + employee).

### بيانات / معلومات الاختبار
- بيانات demo: ICDL (عرضان صباحي/مسائي، 6 امتحانات)، كوافيرة (مستويان، 4 امتحانات)، تاسع (3 مواد، 6 امتحانات).
- `check_demo_courses.php` (مؤقت) استُخدم للتحقق من البيانات ثم حُذف.

---

## مشاكل محتاجة إصلاح/انتباه
- **لا توجد مقررات لمشروعي الروضة (16) ورواد العلم (17)** — يظهر نموذج الطالب من دون "المقرر" عند اختيار هذين المشروعين. ذات صلة بقرار آلية تسجيل الطلاب (مؤجل).

## خطوات تالية (Next Steps)
1. ✅ التحقق النهائي من `php artisan view:cache` ينجح بلا أخطاء.
2. ✅ إصلاح `syncLessons()` واختبار خطة التدريب (create + edit) آلياً (تعارض + إنشاء + تعديل + حذف).
3. ✅ تشغيل سيدر البيانات الوهمية: `php artisan db:seed --class=ScenarioDemoSeeder --force` (نجح).
4. ✅ **بناء واجهة إدارة المستويات/الصفوف**: إنشاء/تعديل مستوى + اختيار مواده + تعيين مدرّسيه (متعدد) — مكتملة.
5. ✅ **المرحلة 3 (الفوج)**: ربط الطالب بالفوج + فلترة + صلاحيات + سيدر — مكتملة.
6. ✅ **المرحلة 4 (حقول الموظف في المستخدم)**: فلاتر/بحث/استيراد/تصدير — مكتملة.
7. (أمامي) **التحقق البصري في المتصفح** لنماذج الخطة/المستوى ولواجهات الفوج والمستخدمين الجديدة.
8. (معلّق على قرار المستخدم) تحديد **آلية تسجيل طلاب الروضة/رواد العلم** (مقررات صفوف أم مستويات `academic_level`) — ثم تنفيذها.
9. (خارج النطاق المرحلي) فصول المدارس كما في الروضة (مدرّس صف) — مؤجلة.
10. ✅ **المرحلة 5 (الشريحة الأولى من الدراسة)**: داشبوردا مدير/مسؤول المشروع + دورة طلب الشراء + سيدر تجريبي — مكتملة ومُختبَرة آلياً (31 فحصاً).
11. (أمامي) **التحقق البصري في المتصفح** لدورة طلب الشراء والداشبوردين (تسجيل دخول بأدوار demo.* والتنقل بين قوائم التسعير/الموافقات/الاعتماد).
12. ✅ **المرحلة 6 (وثائق المشاريع annex)**: قوالب JSON + وثائق/أقسام + اعتمادات/رفض/إعادة فتح + طباعة A4 + سيدر + فحص آلي — مكتملة.
13. (أمامي) **التحقق البصري في المتصفح** لوثائق المشاريع: إنشاء وثيقة من قالب، تعبئة/قفل قسم، إرسال، رفض ثم إعادة فتح، اعتماد، وطباعة A4.
14. ✅ **المرحلة 7 (الخطة الإعلامية)**: migration + موديلات + كنترولر + مسارات + مشاهدات + صلاحيات/sidebar + سيدر تجريبي + فحص الآلي — مكتملة.
15. (أمامي) **التحقق البصري في المتصفح** للخطة الإعلامية: تسجيل دخول بـ `demo.officer` (إنشاء خطة/فعاليات)، إضافة تعارض ثم ملاحظة الرفض، تعليق من `demo.media` على موعد، وعرضها من `demo.pm`/`demo.pm2`.
16. ✅ **المرحلة 8 (خطة الحركة + الوصول السريع)**: migration + موديلات + كنترولر + مسارات + مشاهدات (index/form/show/_recipient_row) + بطاقات اختصارات + sidebar + صلاحيات/سيدر (movement + MediaPlan-create لـ pm + TechIssue) + فحص آلي + **اختبارات Pest 22/22** — مكتملة.
17. (أمامي) **التحقق البصري في المتصفح** لخطة الحركة: `demo.pm` ينشئ → `demo.pm2` يعتمد/يرفض → `demo.moveofficer` يوزّع متابعين متعددين → `demo.moveofficer` ينهي كنُنجزة، وعرض بطاقات الوصول السريع في اللوحتين.
18. ✅ **المرحلة 9 (إدارة المقررات الموحّدة)**: migration (subject_exams + course_id في academic_levels) + SubjectExam + علاقات + CourseController (LEVEL_TYPES + help + syncLevels/Subjects/Exams/Offerings) + مشاهدات index/form/help + بطاقة اختصار + إصلاح فقدان `*.id` في validate + **اختبارات Pest 12/12** — مكتملة.
19. (أمامي) **التحقق البصري في المتصفح** لصفحة إدارة المقررات: إضافة مقرر بمواد وامتحانات بعلامات عليا ومستويات وعروض، تعديل كامل (تأكيد تحديث الموجود لا تكراره)، زر "خطة تدريبية"، صفحة "معلومات ونصائح"، وبطاقة الاختصار في لوحة مدير المشروع.
20. ✅ **الهوية البصرية لوثائق المشاريع**: خلفية مائية كاملة لكل صفحة (`Picture1.jpg`/`public/branding/`) + هوامش padding على المحتوى فقط + خط Tajawal محلي (`public/fonts/Tajawal-*.ttf`) + ألوان برتقالية `--accent/#f6a13a` — مكتملة.
21. ✅ **قوالب وثائق المشاريع الإنتاجية (6)** من ملفات "ملحقات مشروع" المرجعية: بطاقة المشروع، فكرة المشروع، الدراسة الأولية، استمارة تحديد معايير المستفيدين، التقرير الشهري، تقرير نهاية المشروع — عبر سيدر `AnnexTemplatesSeeder` (idempotent: `updateOrCreate`).
22. (أمامي) **التحقق البصري في المتصفح** للقوالب الجديدة: إنشاء وثيقة من كل قالب، تعبئة أقسامه، ثم "طباعة / حفظ PDF" بالهوية الجديدة.
23. (اختياري) القالبان التجريبيان القديمان `DEMO-project-card`/`DEMO-project-idea` لا يزالان مفعّلين بنفس الاسم — يمكن إيقافهما من واجهة القوالب أو حذفهما (إن لم توجد وثائق منهما).
24. ✅ **صفحة معلومات ونصائح للوثائق**: مسار `documents/help` (قبل `{document}`) + دالة `help()` في الكنترولر + `resources/views/admin/project-docs/documents/help.blade.php` + زر «معلومات» في index/show — بشرح تفصيلي (القوالب مقابل الوثائق، أنواع الأقسام، دورة الحياة، الوثائق المشتركة بين مستخدمين، الطباعة/PDF).
25. ✅ **لوحة مدير المشاريع (جديدة ومستقلة عن لوحة مدير المشروع)**: `ProjectsManagerController@dashboard` + مسار `admin/projects-manager` (صلاحية `page:admin.projects-manager.dashboard`) + عرض `admin/projects-manager/dashboard.blade.php` — نظرة شاملة (كل المشاريع/المراكز/مدرائها) تضم: اختصارات وصول سريع (`_shortcuts_projects_manager.blade.php`)، ملخص عام (مشاريع/مراكز/طلاب/مدراء)، المهام (إجمالي/انتظار/متأخرة/منجزة) + مهام قادمة للتقويم، خطط الحركة (توزيع الحالات + قائمة بانتظار مراجعة إدارة المشاريع)، الخطط الإعلامية (شهرية + أحدث الخطط بعدّاد فعاليات)، وثائق المشاريع (قيد الاعتماد + آخر تغييرات الوثائق `WorkflowAction`)، طلبات الشراء (إجمالي + بموجودي للتوقيع + الأحدث)، فريق مدراء المشاريع — + رابط في الشريط الجانبي + بطاقة في الرئيسية (HomeController). فحص التصيير مع `demo.pm2` نجح.
26. ✅ **سيدر صلاحيات لوحة مدير المشاريع**: `ProjectsManagerDashboardPermissionSeeder` (idempotent — شُغّل) يمنح `demo.pm2` صلاحية اللوحة + عرض Project/ProjectTask/Student/Employee + يفتح نطاق الصفوف الحالية (حركة/إعلامية/وثائق/شراء) على كل المراكز.

---

## السيدر الوهمي (local only)
- الملف: `database/seeders/ScenarioDemoSeeder.php`
- **ليس** مضافاً إلى `DatabaseSeeder` الرئيسي (لا يعمل تلقائياً)
- يشغّل يدوياً: `php artisan db:seed --class=ScenarioDemoSeeder --force`
- ينشئ: 5 مشاريع، 8 معلّمين وهميين، المستويات/المواد/المدرّسين لكل سيناريو، 3 خطط تدريبية بدروس أسبوعية، وأفواج الروضة (صباحي/مسائي)

---

## تحققات/ملاحظات تقنية
- بيانات المشروع/الموظفين الحقيقية موجودة مسبقاً (projects 1-3، 3 موظفين) — السيدر أنشأ مشاريع جديدة بأرقام أعلى (16-19)
- معرفات الاختبار: levels تبدأ من ~22، subjects ~29، instructors موجودة
- أخطاء "Undefined variable $errors" في الاختبارات اليدوية هي **خطأ بيئة فقط** (يوفّرها Laravel تلقائياً في المتصفح عبر ShareErrorsFromSession) — لا تعني مشكلة في الكود
- أخطاء "Attempt to read property is_active on null" في test بالمصدر من عدم وجود مستخدم مسجّل (auth) — في المتصفح يوجد مستخدم
- `Excel::store` في Laravel 11 يخزّن بالدور الافتراضي في `storage/app/private/`
- مفاتيح حية: المشاريع 16=الروضة، 17=رواد العلم، 18=أثر، 19=التطوير الإداري، 2=التدريب المهني. المراكز 1=جرابلس، 2=اعزاز، 7=عفرين. المستخدمون 1=Super Admin، 3=omar (employee)، 4/5=طلاب.
- طباعة الوثائق: خلفية `print.blade.php` بحجم A4 حقيقي (`background-size: 210mm 297mm; repeat`) — صورة كاملة لكل ورقة بلا مطّ (بدلاً من `100% 100%` التي تمد الصورة على كل طول الوثيقة). المعاينة بعرض `210mm`.

---

## الملفات ذات الصلة
- Migrations: `database/migrations/2026_08_27_0000{01..08}_*.php`, `2026_08_30_000001_create_cohorts_table.php`, `2026_08_30_000002_add_cohort_to_employees_table.php`, `2026_09_01_075848_add_employee_fields_to_users_table.php`
- موديلات: `app/Models/Admin/Student/{Subject, CourseOffering, StudentSubjectGrade, AcademicLevel, LevelSubjectInstructor, TrainingPlan, TrainingPlanLesson, Cohort}.php`, `app/Models/User.php`
- Controllers: `app/Http/Controllers/Admin/Student/{Course, StudentGrade, AcademicLevel, TrainingPlan, Student}Controller.php`, `app/Http/Controllers/Admin/{User, Cohort, Permission}Controller.php` (و `ProfileController`)
- Views: `resources/views/admin/students/{courses/, enrollments/grades.blade.php, levels/, training-plans/, index.blade.php, form.blade.php}`, `resources/views/admin/users/{index,form}.blade.php`, `resources/views/admin/cohorts/{index,form}.blade.php`
- Exports/Imports: `app/Exports/UserExport.php`, `app/Imports/UserImport.php`
- Routes: `routes/web.php` (قسم students + cohorts + users export/import قبل resource)
- Sidebar: `resources/views/admin/layouts/master.blade.php`
- Seeder: `database/seeders/ScenarioDemoSeeder.php`
- **المرحلة 7**: `database/migrations/2026_09_05_000004_create_media_plans_table.php` + `app/Models/Admin/{MediaPlan, MediaPlanEvent, MediaPlanComment}.php` + `app/Http/Controllers/Admin/MediaPlanController.php` + `resources/views/admin/media-plans/{index, form, show}.blade.php` + `_event_row.blade.php`
- **المرحلة 8**: `database/migrations/2026_09_05_000005_create_movement_plans_table.php` + `app/Models/Admin/{MovementPlan, MovementPlanRecipient}.php` + `app/Http/Controllers/Admin/MovementPlanController.php` + `resources/views/admin/movement-plans/{index, form, show, _recipient_row}.blade.php` + `resources/views/admin/partials/_shortcuts.blade.php` + `PermissionController::modelGroups()` + `tests/Feature/{MovementPlanTest, DashboardShortcutsTest}.php`
- **المرحلة 9**: `database/migrations/2026_09_05_000006_create_subject_exams_table.php` + `2026_09_05_000007_add_course_id_to_academic_levels_table.php` + `app/Models/Admin/Student/SubjectExam.php` (+ علاقة `exams()` في `Subject` و`levels()` في `Course` و`course_id` في `AcademicLevel`) + `app/Http/Controllers/Admin/Student/CourseController.php` + `resources/views/admin/students/courses/{index, form, help}.blade.php` + بطاقة "إدارة المقررات" في `admin/partials/_shortcuts.blade.php` + `tests/Feature/CourseTest.php` + قسم demo في `database/seeders/DemoPurchaseCycleSeeder.php` (دالة `coursesDemo()` + مؤشر `$coursesDone` في `run()`)
- **الهوية البصرية للطباعة**: `resources/views/admin/project-docs/documents/print.blade.php` (خلفية `Picture1.jpg` كاملة + padding 30/18mm + `Tajawal Local` + برتقالي) + `public/branding/Picture1.jpg` — التحقّق: `/branding/Picture1.jpg` و`/fonts/Tajawal-Bold.ttf` يعيدان 200.
- **المرحلة 10 (قوالب الوثائق الإنتاجية)**: `database/seeders/AnnexTemplatesSeeder.php` (6 قوالب: `project-card`, `project-idea`, `project-preliminary-study`, `beneficiary-criteria`, `project-monthly-report`, `project-final-report` — تُشغَّل بـ `php artisan db:seed --class=AnnexTemplatesSeeder`)
- **صفحة معلومات الوثائق**: مسار `documents/help` في `routes/web.php` (قبل `{document}`) + `help()` في `AnnexDocumentController` + `resources/views/admin/project-docs/documents/help.blade.php` + زر «معلومات» في `documents/{index, show}.blade.php`