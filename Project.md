# وحدة إدارة المشاريع - Project Management Module

## نظرة عامة

وحدة إدارة المشاريع مسؤولة عن إدارة المهام ومتابعة تنفيذها داخل المنظمة. تدعم إنشاء المهام، تعيينها للمستخدمين، متابعة التنفيذ (منفذة / غير منفذة)، رصد التأخير، التغطية الإعلامية، التكاليف، والتجهيزات. تتكامل مع نظام الصلاحيات ونظام المستخدمين والمراكز.

---

## هيكل الجدول (Database Schema)

### 1. project_tasks

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| title | string(255) | اسم المهمة |
| purpose | text | **Nullable.** الغاية من المهمة |
| start_date | date | تاريخ البداية |
| end_date | date | تاريخ النهاية |
| needs_media_coverage | boolean | Default false. هل تحتاج تغطية إعلامية |
| needs_costs | boolean | Default false. هل تحتاج تكاليف محددة |
| costs_details | text | **Nullable.** تفاصيل التكاليف |
| needs_equipment | boolean | Default false. هل تحتاج تجهيزات |
| equipment_details | text | **Nullable.** تفاصيل التجهيزات |
| assigned_to | FK | -> users(id). المسند إليه (مطلوب) |
| created_by | FK | -> users(id). المنشئ (مطلوب) |
| center_id | FK | **Nullable.** -> centers(id) |
| status | string(255) | Default `pending`. `pending` \| `in_progress` \| `completed` \| `delayed` \| `cancelled` |
| executed | boolean | **Nullable.** هل تم التنفيذ (true/false/null) |
| not_executed_reason | text | **Nullable.** سبب عدم التنفيذ |
| has_delay | boolean | **Nullable.** هل يوجد تأخير |
| delay_reason | text | **Nullable.** سبب التأخير |
| media_coverage_done | boolean | **Nullable.** هل تمت التغطية الإعلامية |
| no_media_coverage_reason | text | **Nullable.** سبب عدم التغطية |
| execution_notes | text | **Nullable.** ملاحظات التنفيذ |
| timestamps | - | created_at, updated_at |
| softDeletes | timestamp | deleted_at |

---

## الموديل (Model)

### ProjectTask
- **الجدول:** `project_tasks`
- **SoftDeletes:** مدعومة
- **Casts:**
  - `start_date`, `end_date` -> `date`
  - `needs_media_coverage`, `needs_costs`, `needs_equipment` -> `boolean`
  - `executed`, `has_delay`, `media_coverage_done` -> `boolean`
- **Relationships:**
  - `assignedTo()` -> BelongsTo(User::class, 'assigned_to')
  - `createdBy()` -> BelongsTo(User::class, 'created_by')
  - `center()` -> BelongsTo(Center::class)

---

## المسارات (Routes)

البادئة: `/admin/projects` - الاسم: `admin.projects.*`
**ملاحظة:** يتم تسجيل مسارات المهام **قبل** `Route::resource('projects', ...)` لمنع التصادم مع wildcard.

### المهام
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /tasks | `ProjectTaskController@index` |
| GET | /tasks/create | `ProjectTaskController@create` |
| POST | /tasks | `ProjectTaskController@store` |
| GET | /tasks/{task} | `ProjectTaskController@show` |
| GET | /tasks/{task}/edit | `ProjectTaskController@edit` |
| PUT | /tasks/{task} | `ProjectTaskController@update` |
| DELETE | /tasks/{task} | `ProjectTaskController@destroy` |
| POST | /tasks/{task}/update-status | `ProjectTaskController@updateStatus` |

### أخرى
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /calendar | `ProjectTaskController@calendar` (Kanban board) |
| GET | /statistics | `ProjectTaskController@statistics` (إحصائيات) |

---

## الصلاحيات (Permissions)

الموديل المسجل في `PermissionController@modelGroups()`:
- `App\Models\Admin\ProjectTask` => 'المهام'

الأفعال المتاحة: `view`, `create`, `edit`, `delete`.

مستخدم `super-admin` لديه جميع الصلاحيات تلقائياً.

---

## قواعد الرؤية (Visibility Rules)

- **منشئ المهمة:** يرى المهام التي أنشأها (`created_by = user_id`)
- **المسند إليه:** يرى المهام المسندة إليه (`assigned_to = user_id`)
- **Super-admin:** يرى جميع المهام (بدون فلتر)
- تُطبق في: `index()`, `calendar()`, `statistics()` عبر `where(fn($q) => $q->where('created_by', $userId)->orWhere('assigned_to', $userId))`

---

## عرض التقويم الزمني (Kanban Calendar)

- يعرض المهام مقسمة حسب الحالة (قيد الانتظار، قيد التنفيذ، متأخرة، منفذة، ملغاة)
- فلتر حسب الشهر والسنة
- `query`: يبحث عن المهام التي تتداخل مع الشهر المحدد (`start_date <= year-month-end AND end_date >= year-month-start`)
- **CSS فقط** (لا مكتبات خارجية): `display: flex` للأعمدة، hover hints عبر `+` sibling selector
- يحتوي كل بطاقة على: اسم المهمة (مختصر)، التاريخ، المسند إليه
- Hover hint يعرض: الاسم الكامل، الغاية، المسند إليه، المدة، حالة التنفيذ + رابط عرض التفاصيل

---

## الإحصائيات (Statistics)

- بطاقات رقمية: إجمالي المهام، منفذة، قيد الانتظار، متأخرة، قيد التنفيذ، تم تنفيذها، لم تنفذ، فيها تأخير، تمت التغطية الإعلامية
- رسم بياني شريطي شهري (CSS bars بارتفاع نسبي)
- فلتر حسب السنة
- جميع الاستعلامات تحترم قواعد الرؤية (غير super-admin)

---

## الـ Views

| الملف | الوظيفة |
|-------|---------|
| `index.blade.php` | قائمة المهام مع فلترة (حالة، شهر، سنة، مركز) و hover hints |
| `form.blade.php` | إنشاء/تعديل مهمة مع toggle switches للتكاليف/التجهيزات/الإعلام |
| `show.blade.php` | تفاصيل المهمة + متابعة التنفيذ (راديو بوتونات التنفيذ/التأخير/الإعلام) |
| `calendar.blade.php` | Kanban board بخمسة أعمدة حسب الحالة |
| `statistics.blade.php` | بطاقات إحصائية + رسم بياني شريطي بالشهور |

---

## دوائر التحكم الرئيسية (Controller Methods)

### CRUD
- `index()` - قائمة مفلترة مع pagination (15 لكل صفحة)
- `create()` / `store()` - إنشاء مهمة جديدة (toggle switches تعالج بـ `$request->boolean()`)
- `edit()` / `update()` - تعديل المهمة (يتضمن حقل الحالة)
- `destroy()` - حذف ناعم (SoftDeletes)

### متابعة التنفيذ
- `updateStatus()` - تحديث حقول: `executed`, `not_executed_reason`, `has_delay`, `delay_reason`, `media_coverage_done`, `no_media_coverage_reason`, `execution_notes`
- validate: `nullable|boolean` للراديو، `nullable|string|max:2000` للنصوص

### أخرى
- `calendar()` - Kanban board مع فلتر شهر/سنة
- `statistics()` - إحصائيات مع فلتر سنة

---

## التعديلات الرئيسية التي تمت

### 2026-06-28: إنشاء وحدة إدارة المشاريع

- **المشكلة:** لا يوجد نظام لمتابعة المهام والمشاريع.
- **الحل:** تم إنشاء جدول `project_tasks` وموديل `ProjectTask` مع كامل CRUD و views.
- **التغييرات:**
  - `database/migrations/2026_06_28_000007_create_project_tasks_table.php` (جديد)
  - `app/Models/Admin/ProjectTask.php` (جديد)
  - `app/Http/Controllers/Admin/ProjectTaskController.php` (جديد - 10 دوال)
  - `resources/views/admin/projects/tasks/` (5 ملفات جديدة)
  - `routes/web.php`: 10 مسارات تحت `admin.projects.*`
  - `PermissionController@modelGroups()`: إضافة `App\Models\Admin\ProjectTask`
  - `master.blade.php`: إضافة قسم "إدارة المشاريع" في الشريط الجانبي (المهام، التقويم، الإحصائيات)
  - `database/seeders/ProjectTaskSeeder.php` (جديد - 10 مهام وهمية)

---

## مشاكل معروفة

لا توجد مشاكل معروفة حالياً.

---

## إرشادات للمطورين

- **ترتيب المسارات مهم:** مسارات `admin.projects.tasks.*` يجب أن تُسجل **قبل** `Route::resource('projects', ...)` لأن resource ستلتقط `{project}` وتتصادم مع `tasks`.
- **Toggle switches:** القيم المنطقية (needs_media_coverage, needs_costs, needs_equipment) تُعالج بـ `$request->boolean()` لتجنب مشكلة hidden field.
- **الراديو بوتونات:** في show.blade.php، الراديو بوتونات تستخدم ثلاث قيم (1=نعم, 0=لا, ''=لم يحدد) مع `null` في قاعدة البيانات.
- **Hover hints:** تستخدم CSS `+` sibling selector لإظهار popup عند hover (لا حاجة لـ JavaScript).
- **بعد تعديل أي Blade view:** قم بتشغيل `php artisan view:clear`
- **الرسوم البيانية:** في statistics.blade.php، الأعمدة الشهرية مبنية يدوياً بـ CSS (ارتفاع نسبي + flex) بدون مكتبات خارجية.
