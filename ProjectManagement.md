# إدارة المشاريع - Project Management Module

## نظرة عامة

وحدة إدارة المشاريع مسؤولة عن:
- إنشاء ومتابعة المهام
- عرض تقويم زمني للمهام (Kanban)
- إحصائيات أداء المهام
- ربط المهام بالمراكز والمستخدمين

---

## هيكل الجدول (Database Schema)

### project_tasks

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| title | string(255) | اسم المهمة |
| purpose | text | Nullable. الغاية من المهمة |
| start_date | date | تاريخ البداية |
| end_date | date | تاريخ النهاية |
| needs_media_coverage | boolean | هل تحتاج تغطية إعلامية |
| needs_costs | boolean | هل تحتاج تكاليف محددة |
| costs_details | text | Nullable. تفاصيل التكاليف |
| needs_equipment | boolean | هل تحتاج تجهيزات معينة |
| equipment_details | text | Nullable. تفاصيل التجهيزات |
| assigned_to | FK | -> users(id). الشخص المسند إليه المهمة |
| created_by | FK | -> users(id). منشئ المهمة |
| center_id | FK | Nullable. -> centers(id). المركز (المدينة) |
| status | string(255) | `pending` \| `in_progress` \| `completed` \| `delayed` \| `cancelled` |
| executed | boolean | Nullable. هل تم التنفيذ |
| not_executed_reason | text | Nullable. سبب عدم التنفيذ |
| has_delay | boolean | Nullable. هل هناك تأخير |
| delay_reason | text | Nullable. سبب التأخير |
| media_coverage_done | boolean | Nullable. هل تمت التغطية الإعلامية |
| no_media_coverage_reason | text | Nullable. سبب عدم التغطية |
| execution_notes | text | Nullable. ملاحظات التنفيذ |
| timestamps | - | created_at, updated_at |
| softDeletes | - | deleted_at |

---

## الموديل (Model)

### ProjectTask
**Namespace:** `App\Models\Admin\ProjectTask`

**Casts:**
- `start_date` و `end_date` => `date`
- `needs_media_coverage`, `needs_costs`, `needs_equipment` => `boolean`
- `executed`, `has_delay`, `media_coverage_done` => `boolean`

**Relationships:**
- `assignedTo()` -> `BelongsTo(User::class, 'assigned_to')` - الشخص المسند إليه
- `createdBy()` -> `BelongsTo(User::class, 'created_by')` - منشئ المهمة
- `center()` -> `BelongsTo(Center::class)` - المركز/المدينة

---

## المسارات (Routes)

البادئة: `/admin/projects` - الاسم: `admin.projects.*`

| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /projects/tasks | `ProjectTaskController@index` - قائمة المهام |
| GET | /projects/tasks/create | `ProjectTaskController@create` - فورم إضافة مهمة |
| POST | /projects/tasks | `ProjectTaskController@store` - حفظ مهمة جديدة |
| GET | /projects/tasks/{task} | `ProjectTaskController@show` - عرض تفاصيل المهمة |
| GET | /projects/tasks/{task}/edit | `ProjectTaskController@edit` - فورم تعديل |
| PUT | /projects/tasks/{task} | `ProjectTaskController@update` - تحديث المهمة |
| DELETE | /projects/tasks/{task} | `ProjectTaskController@destroy` - حذف المهمة |
| POST | /projects/tasks/{task}/update-status | `ProjectTaskController@updateStatus` - تحديث حالة التنفيذ |
| GET | /projects/calendar | `ProjectTaskController@calendar` - التقويم الزمني (Kanban) |
| GET | /projects/statistics | `ProjectTaskController@statistics` - الإحصائيات |

---

## نظام الصلاحيات

- **موديل الصلاحية:** `App\Models\Admin\ProjectTask`
- **الأفعال:** `view`, `create`, `edit`, `delete`
- تُعطى صلاحية الوصول للمستخدمين يدوياً من شاشة الصلاحيات

### صلاحيات الصفحات
- `page:admin.projects.tasks.index` - قائمة المهام
- `page:admin.projects.calendar` - التقويم الزمني
- `page:admin.projects.statistics` - الإحصائيات

---

## قواعد الرؤية (Visibility)

- **منشئ المهمة** يرى جميع المهام التي أنشأها
- **المسند إليه** يرى جميع المهام المسندة إليه
- **مستخدم super-admin** يرى كل المهام
- يتم التحقق في `index()`, `calendar()`, `statistics()` في `ProjectTaskController`

---

## الصفحات (Views)

### 1. قائمة المهام `tasks/index.blade.php`
- جدول يعرض المهام مع مرشحات (الحالة، الشهر، السنة، المركز)
- Hover popup على اسم المهمة يعرض معلومات سريعة
- أزرار: عرض، تعديل، حذف

### 2. إضافة/تعديل مهمة `tasks/form.blade.php`
- عند الإضافة: خيارات التغطية الإعلامية، التكاليف، التجهيزات (Switch مع إظهار/إخفاء الحقول)
- عند التعديل: إظهار حقل الحالة لتحديثها يدوياً
- ربط بالمركز والمستخدم المسند إليه

### 3. عرض المهمة `tasks/show.blade.php`
- جدول تفاصيل المهمة
- جدول المتطلبات الإضافية
- **كارد متابعة التنفيذ:**
  - هل تم تنفيذ المهمة؟ (نعم/لا/لم يحدد) + سبب عدم التنفيذ
  - هل هناك تأخير؟ (نعم/لا/لم يحدد) + سبب التأخير
  - هل تمت التغطية الإعلامية؟ (نعم/لا/غير مطبق) + سبب عدم التغطية
  - ملاحظات التنفيذ
  - زر حفظ التحديث

### 4. التقويم الزمني `tasks/calendar.blade.php`
- عرض Kanban (لوحة) بخمسة أعمدة: قيد الانتظار، قيد التنفيذ، متأخرة، منفذة، ملغاة
- تصفية بالشهر والسنة
- Hover على البطاقة يعرض hint بمعلومات المهمة والشخص المسند إليه
- **لا يستخدم أي مكتبة خارجية** - CSS Grid + Flexbox خالص

### 5. الإحصائيات `tasks/statistics.blade.php`
- إجمالي المهام
- عدد المهام حسب الحالة (منفذة، قيد الانتظار، متأخرة، قيد التنفيذ)
- عدد المهام المنفذة / غير المنفذة
- عدد المهام المتأخرة
- عدد المهام ذات التغطية الإعلامية
- رسم بياني شريطي (CSS خالص) يظهر توزيع المهام حسب الشهر

---

## التحكم (Controller)

### ProjectTaskController
**المسار:** `app/Http/Controllers/Admin/ProjectTaskController.php`

| الدالة | الوصف |
|--------|-------|
| `index()` | عرض القائمة مع فلترة |
| `create()` | فورم الإضافة |
| `store()` | حفظ مع التحقق من صحة البيانات |
| `show()` | عرض التفاصيل مع التحميل المسبق للعلاقات |
| `edit()` | فورم التعديل |
| `update()` | تحديث مع التحقق |
| `updateStatus()` | تحديث حالة التنفيذ فقط (من صفحة العرض) |
| `destroy()` | حذف ناعم (Soft Delete) |
| `calendar()` | عرض لوحة Kanban للشهر والسنة المحددة |
| `statistics()` | عرض إحصائيات المهام |

---

## تاريخ التعديلات

### 2026-06-28: إنشاء الوحدة
- إنشاء `project_tasks` table
- إنشاء `ProjectTask` model مع العلاقات والـ casts
- إنشاء `ProjectTaskController` مع 10 دوال
- إنشاء 5 صفحات عرض: index, form, show, calendar, statistics
- إضافة المسارات تحت `admin.projects.*`
- إضافة الصلاحيات إلى `PermissionController@modelGroups`
- إضافة روابط السايدبار تحت قسم "إدارة المشاريع"
- إنشاء توثيق `ProjectManagement.md`
