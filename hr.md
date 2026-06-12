# تطبيق الموارد البشرية (HR)

## هيكل المجلدات

```
app/
├── Http/Controllers/Admin/Hr/
│   ├── EmployeeController.php
│   ├── JobPositionController.php
│   └── WarningController.php
├── Http/Controllers/Admin/ProfileController.php   ← عام (ليس HR)
├── Models/Admin/Hr/
│   ├── Employee.php
│   ├── JobPosition.php
│   └── Warning.php
├── Models/Admin/Department.php                    ← عام (مثل Center و Project)
├── Exports/
│   └── EmployeeExport.php
├── Imports/
│   └── EmployeeImport.php

resources/views/admin/
├── hr/employees/
│   ├── index.blade.php
│   ├── form.blade.php      (tabs + inlines)
│   └── show.blade.php
├── hr/job-positions/
│   ├── index.blade.php
│   └── form.blade.php
├── hr/warnings/
│   ├── index.blade.php
│   └── form.blade.php
├── departments/                                   ← عام
│   ├── index.blade.php
│   └── form.blade.php
└── profile/
    └── index.blade.php                            ← صفحة البروفايل

database/
├── migrations/                                    ← الملفات الرئيسية
│   ├── 2026_xx_xx_000007_create_departments_table.php      ← عام
│   ├── 2026_xx_xx_000100_create_hr_employees_table.php
│   ├── 2026_xx_xx_000101_create_hr_employee_educations_table.php
│   ├── 2026_xx_xx_000102_create_hr_employee_contacts_table.php
│   ├── 2026_xx_xx_000200_create_hr_job_positions_table.php
│   ├── 2026_xx_xx_000201_create_hr_work_schedules_table.php
│   ├── 2026_xx_xx_000202_create_hr_contracts_table.php
│   ├── 2026_xx_xx_000203_create_hr_salaries_table.php
│   ├── 2026_xx_xx_000300_create_hr_employee_documents_table.php
│   ├── 2026_xx_xx_000301_create_hr_warnings_table.php
│   └── 2026_xx_xx_000302_create_hr_employee_notes_table.php
└── migrations/hr/                                 ← (مقترح - اختياري)
    └── ... يمكن وضع ملفات HR هنا
```

---

## ملاحظة: هل نضع الميغريشنز في مجلد منفصل للـ HR؟

**هل لارافيل يسمح؟** نعم. في Laravel 11، يمكنك إضافة مسارات مخصصة في `bootstrap/app.php`:

```php
->withMigrations([
    database_path('migrations'),
    database_path('migrations/hr'),
])
```

**هل هو جيد؟** له إيجابيات وسلبيات:

| الإيجابيات | السلبيات |
|-----------|---------|
| تنظيم أفضل للكود | `migrate:fresh` يتطلب تسجيل كل المسارات |
| فصل كامل بين النواة والـ HR | صعوبة تحديد ترتيب التنفيذ بين المجلدات |
| سهل الحذف إذا ألغيت الـ HR | تعقيد إضافي غير ضروري لمشروع بهذا الحجم |

**التوصية:** الأفضل الالتزام بمجلد `migrations/` واحد مع تسمية منظمة:
- `0000xx` للنواة (centers, projects, departments, users, ...)
- `0001xx` للـ HR (employees, job_positions, ...)
- `0002xx` للميزات الأخرى (students, health, ...)

هذا يبقي الأمر بسيطاً ويتبع convention لارافيل الافتراضي.

---

## 1. قاعدة البيانات

### 1.0 جدول الإدارات (departments) — عام (Core)

هذا الجدول عام مثل `centers` و `projects`، وليس مخصصاً للـ HR فقط.

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint (PK) | |
| name_ar | string(100) | اسم الإدارة AR |
| name_en | string(100) | اسم الإدارة EN |
| description | text | |
| is_active | boolean default true | |
| timestamps | | |

**الموديل:** `app/Models/Admin/Department.php`  
**الكود:** `app/Http/Controllers/Admin/DepartmentController.php` (CRUD كامل مع صلاحيات)  
**الفيو:** `resources/views/admin/departments/`  
**السايدبار:** تحت قسم الإدارة، بعد المشاريع وقبل المجموعات  
**السيدر:** بيانات افتراضية (إدارة العمليات، إدارة المشاريع، إدارة الموارد البشرية، إدارة الشؤون المالية، ...)

### 1.1 جدول الموظفين (employees)

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint (PK) | |
| user_id | bigint FK→users (nullable) | اختياري، يُربط لاحقاً |
| employee_code | string(20) UNIQUE | كود الموظف (مثل OND520) |
| status | enum('active','inactive') | الحالة |
| id_number | string(50) | رقم الهوية/جواز السفر |
| first_name_ar | string(100) | الاسم AR |
| last_name_ar | string(100) | اللقب AR |
| first_name_en | string(100) | |
| last_name_en | string(100) | |
| father_name_ar | string(100) | |
| father_name_en | string(100) | |
| mother_name_ar | string(100) | |
| mother_name_en | string(100) | |
| gender | enum('male','female') | |
| marital_status | enum('single','married','divorced','widowed') | |
| children_count | integer default 0 | |
| birth_date | date | |
| birth_place | string(100) | (مدن سورية) |
| nationality | string(100) | الجنسية |
| center_id | bigint FK→centers | |
| department_id | bigint FK→departments | |
| project_id | bigint FK→projects | |
| has_photo | boolean | صورة شخصية |
| has_cv | boolean | |
| has_id_copy | boolean | |
| has_qualification | boolean | |
| has_experience_certs | boolean | |
| has_offer_letter | boolean | |
| has_contract_doc | boolean | |
| has_employee_data | boolean | |
| has_job_description | boolean | |
| has_signature_movements | boolean | |
| has_security_audit | boolean | |
| has_reference_audit | boolean | |
| has_code_of_conduct | boolean | |
| has_clearance | boolean | |
| has_receipt | boolean | |
| has_resignation | boolean | |
| has_verbal_warning_doc | boolean | |
| has_written_warning_doc | boolean | |
| has_termination_warning_doc | boolean | |
| has_termination_doc | boolean | |
| has_blacklist_doc | boolean | |
| notes | text | ملاحظات عامة |
| timestamps | | |
| soft_deletes | | |

### 1.2 جدول المؤهلات العلمية (employee_educations)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| qualification | string(200) |
| specialization | string(200) |
| university | string(200) ← مقترح |
| grade | string(50) ← مقترح (ممتاز، جيد جداً، ...) |
| graduation_year | year |

### 1.3 جدول معلومات التواصل (employee_contacts)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| type | string(50) (منزل، طوارئ، واتسآب، بريد إلكتروني) |
| value | string(200) |
| is_primary | boolean default false ← مقترح |

### 1.4 جدول المناصب الوظيفية (job_positions)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| title_ar | string(200) |
| title_en | string(200) |
| description_ar | text |
| description_en | text |
| timestamps | |

### 1.5 جدول أوقات الدوام (work_schedules)

لكل موظف سجل لكل يوم دوام (Saturday إلى Friday)، مع إمكانية تحديد أوقات مختلفة لكل يوم:

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint (PK) | |
| employee_id | bigint FK→employees | |
| day_of_week | tinyint (0=Sat, 1=Sun, 2=Mon, 3=Tue, 4=Wed, 5=Thu, 6=Fri) | |
| start_time | time (nullable) | بداية الدوام |
| end_time | time (nullable) | نهاية الدوام |
| is_day_off | boolean default false | إجازة أسبوعية |

مثال: أحمد = 6 سجلات (السبت 10:00→14:00، الأحد 13:00→16:00، الإثنين 09:00→17:00، ...)

### 1.6 جدول العقود (contracts)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| contract_type | string(50) ← مقترح (دائم، مؤقت، موسمي، تجريبي) |
| job_position_id | bigint FK→job_positions |
| start_date | date |
| contract_start | date |
| contract_end | date (nullable) |
| leave_date | date (nullable) |

### 1.7 جدول الراتب (salaries)

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint (PK) | |
| employee_id | bigint FK→employees | |
| currency | string(3) default 'SYP' ← مقترح |
| salary_unit | string(50) | وحدة الراتب (شهري، يومي، ...) |
| base_salary | decimal(12,2) | الراتب الأساسي ← مقترح |
| study_allowance | decimal(10,2) | تعويض دراسات |
| marriage_allowance | decimal(10,2) | تعويض زواج |
| experience_allowance | decimal(10,2) | بدل خبرة |
| transport_allowance | decimal(10,2) | بدل نقل ← مقترح |
| food_allowance | decimal(10,2) | بدل طعام ← مقترح |
| housing_allowance | decimal(10,2) | بدل سكن ← مقترح |
| mobile_allowance | decimal(10,2) | بدل هاتف ← مقترح |
| risk_allowance | decimal(10,2) | بدل خطورة ← مقترح |
| overtime_rate | decimal(10,2) | قيمة ساعة إضافية ← مقترح |
| deduction | decimal(10,2) | خصومات ← مقترح |
| total_salary | decimal(12,2) | الراتب الإجمالي |

### 1.8 جدول ملفات الموظف (employee_documents)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| document_type | string(50) (صورة، سيرة ذاتية، هوية، عقد، ...) |
| file_path | string(500) |
| original_name | string(255) |

### 1.9 جدول التنبيهات (warnings)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| date | date |
| reason | text |
| level | enum('verbal','written','termination') |
| is_folded | boolean |
| fold_reason | text (nullable) |
| folded_at | datetime (nullable) |
| timestamps |

### 1.10 جدول ملاحظات الموظف (employee_notes)

| العمود | النوع |
|--------|------|
| id | bigint (PK) |
| employee_id | bigint FK→employees |
| note | text |
| user_id | bigint FK→users |
| timestamps |

---

## 2. المسارات (Routes)

### 2.1 صفحة البروفايل (عامة — كل المستخدمين النشطين)

```
GET    /admin/profile              → ProfileController@index     (عرض البروفايل)
PUT    /admin/profile              → ProfileController@update    (تحديث المعلومات)
PUT    /admin/profile/password     → ProfileController@password  (تغيير كلمة المرور)
```

بدون `permission middleware` — فقط `auth` والمستخدم active.

### 2.2 مسارات HR

كلها تحت `admin/hr/` ومحمية بـ `auth` + `permission`:

```
GET    /admin/hr/employees              → index
GET    /admin/hr/employees/create       → create
POST   /admin/hr/employees              → store
GET    /admin/hr/employees/{id}         → show
GET    /admin/hr/employees/{id}/edit    → edit
PUT    /admin/hr/employees/{id}         → update
DELETE /admin/hr/employees/{id}         → destroy
POST   /admin/hr/employees/import       → import
GET    /admin/hr/employees/export       → export

GET    /admin/hr/job-positions          → resource (full CRUD)

POST   /admin/hr/warnings/{id}/fold     → fold (individual)
POST   /admin/hr/warnings/bulk-fold     → bulkFold
```

### 2.3 مسارات الإدارات (عامة — مثل المراكز)

```
GET    /admin/departments               → resource (full CRUD)
```

---

## 3. الصلاحيات

المستخدم سيقوم بإضافة الصلاحيات يدوياً من نظام الصلاحيات الموجود.

لذلك نحتاج فقط إضافة هذه الموديلات إلى `availableModels` في `PermissionController`:

- `App\Models\Admin\Hr\Employee`
- `App\Models\Admin\Hr\JobPosition`
- `App\Models\Admin\Hr\Warning`
- `App\Models\Admin\Department` (عام)

بعدها أي مستخدم له صلاحية `view` على `Employee` مثلاً سيظهر له رابط HR في السايدبار.

---

## 4. صفحة البروفايل (عامة)

صفحة بروفايل في dashboard يستطيع أي مستخدم **نشط** (is_active = true) الوصول إليها:

**المحتوى:**
- عرض معلومات المستخدم (الاسم، البريد الإلكتروني، تاريخ التسجيل)
- نموذج تغيير اسم المستخدم
- نموذج تغيير كلمة المرور (كلمة المرور الحالية + الجديدة + تأكيد)

**الأمان:**
- لا يحتاج `permission middleware`
- فقط `auth` + التحقق أن `is_active = true`
- تغيير كلمة المرور يتطلب التحقق من كلمة المرور الحالية

**السايدبار:**
- أيقونة شخص في أعلى القائمة أو في dropdown المستخدم
- رابط "الملف الشخصي" الموجود حالياً في dropdown → يوجه إلى صفحة البروفايل

---

## 5. واجهة المستخدم

### 5.1 صفحة الموظفين (index)
- جدول بالبيانات الأساسية (كود، اسم، مركز، مشروع، حالة)
- أزرار بحث وتصفية
- زر استيراد وتصدير Excel
- زر إضافة موظف

### 5.2 نموذج الموظف (form)
- **Tab 1: المعلومات الأساسية** (الاسم، الجنس، التاريخ، ...)
- **Tab 2: معلومات الوظيفة** (المنصب، الإدارة، المركز، المشروع، أوقات الدوام)
- **Tab 3: المؤهلات والاتصال** (جداول inline قابلة للإضافة/حذف)
- **Tab 4: العقد والراتب** (مع جميع البدلات المقترحة)
- **Tab 5: الملفات** (رفع ملفات لكل نوع من الوثائق)
- **Tab 6: التنبيهات والملاحظات**

### 5.3 صفحة عرض الموظف (show)
- عرض كامل المعلومات مع إمكانية التنقل بين التبويبات

---

## 6. التسجيل المخصص (Register)

إنشاء صفحة Register جديدة (بدل Livewire Volt) تحتوي على:
- الاسم الثلاثي
- البريد الإلكتروني
- كلمة المرور + تأكيد
- اختيار المركز (من قائمة المراكز الفعالة)
- اختيار المشروع (يتغير حسب المركز المختار، من المشاريع الفعالة في ذلك المركز)

بعد التسجيل → تحويل إلى صفحة التطبيقات (`/admin`).

---

## 7. الاستيراد والتصدير (Excel)

### تصدير
- جميع بيانات الموظفين مع الحقول المرتبطة (اسم المركز، اسم المشروع، اسم الإدارة، ...)
- استخدام `maatwebsite/laravel-excel`

### استيراد
1. تحميل نموذج Excel مملوء بالبيانات مثال + headers
2. المستخدم يملأ البيانات في النموذج
3. رفع الملف واستيراده مع التحقق من العلاقات (FKs)

---

## 8. خطة التنفيذ (مراحلة)

### المرحلة 0: العام (قبل HR)
- [ ] إنشاء ميغريشن `departments` + موديل `Department.php` (core)
- [ ] إنشاء `DepartmentController` (CRUD كامل)
- [ ] إنشاء فيوز departments (index + form)
- [ ] إضافة departments إلى السايدبار تحت "الإدارة"
- [ ] إضافة `Department` إلى `availableModels` في الصلاحيات
- [ ] إنشاء `ProfileController` + صفحة البروفايل + تغيير كلمة المرور
- [ ] ربط رابط "الملف الشخصي" في dropdown المستخدم بصفحة البروفايل

### المرحلة 1: البنية الأساسية للـ HR
- [ ] إنشاء الـ 10 ميغريشنز حق HR
- [ ] إنشاء موديلات HR مع العلاقات
- [ ] إضافة موديلات HR إلى `availableModels` في الصلاحيات

### المرحلة 2: الموظفين
- [ ] إنشاء `EmployeeController` (CRUD كامل)
- [ ] إنشاء الفورم بالتبويبات مع الـ inline tables
- [ ] رفع الملفات وتنظيم المجلدات
- [ ] تحميل وعرض الملفات

### المرحلة 3: الميزات المتقدمة
- [ ] إنشاء صفحة عرض الموظف
- [ ] JobPositions CRUD
- [ ] إدارة التنبيهات (طي فردي + جماعي)
- [ ] الملاحظات

### المرحلة 4: الاستيراد والتصدير
- [ ] تثبيت `maatwebsite/laravel-excel`
- [ ] تصدير Excel
- [ ] استيراد Excel مع التحقق من البيانات

### المرحلة 5: التسجيل المخصص
- [ ] إنشاء RegisterController
- [ ] ربط المستخدم بسجل الموظف بعد التسجيل
- [ ] إضافة تطبيق HR إلى صفحة التطبيقات

---

## 9. اقتراحات إضافية (متفق عليها)

- **جدول التعليم**: إضافة `university` و `grade` (جامعة، تقدير)
- **الراتب**: إضافة `currency` (عملة الراتب)
- **الراتب**: إضافة `base_salary` (الراتب الأساسي)
- **الراتب**: إضافة `transport_allowance` (بدل نقل)
- **الراتب**: إضافة `food_allowance` (بدل طعام)
- **الراتب**: إضافة `housing_allowance` (بدل سكن)
- **الراتب**: إضافة `mobile_allowance` (بدل هاتف)
- **الراتب**: إضافة `risk_allowance` (بدل خطورة)
- **الراتب**: إضافة `overtime_rate` (قيمة ساعة إضافية)
- **الراتب**: إضافة `deduction` (خصومات)
- **جهة الاتصال**: إضافة `is_primary` (اتصال رئيسي)
- **العقد**: إضافة `contract_type` (دائم، مؤقت، موسمي، تجريبي)
- **تاريخ الميلاد**: حساب العمر تلقائياً
- **نظام الأرشفة**: إمكانية أرشفة الموظفين بدل حذفهم (soft deletes)
- **الميغريشنز**: تسمية منظمة بالأرقام (`0001xx` للـ HR)
