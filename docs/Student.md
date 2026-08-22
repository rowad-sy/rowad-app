# وحدة الطلاب - Student Module

## نظرة عامة

وحدة الطلاب مسؤولة عن إدارة بيانات الطلاب والتسجيلات في المقررات والشهادات والحضور. تتكامل مع نظام الصلاحيات ونظام المستخدمين والمراكز والمشاريع.

---

## هيكل الجداول (Database Schema)

### 1. students
بيانات الطلاب الأساسية.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| student_code | string(20) | Unique. كود الطالب |
| identity_type | string(50) | **Nullable.** نوع الهوية: `national_id` \| `passport` \| `resident_id` \| `other` |
| identity_number | string(50) | **Nullable.** رقم الهوية |
| user_id | FK | **Nullable.** -> users(id) ON DELETE SET NULL |
| first_name_ar | string(100) | الاسم الأول بالعربية |
| last_name_ar | string(100) | اللقب بالعربية |
| first_name_en | string(100) | **Nullable.** |
| last_name_en | string(100) | **Nullable.** |
| father_name | string(100) | **Nullable.** |
| mother_name | string(100) | **Nullable.** |
| birth_date | date | **Nullable.** |
| birth_place | string(100) | **Nullable.** |
| gender | enum | `male` \| `female` |
| nationality | string(100) | **Nullable.** |
| phone | string(50) | **Nullable.** |
| email | string(100) | **Nullable.** |
| address | text | **Nullable.** |
| center_id | FK | **Nullable.** -> centers(id) ON DELETE SET NULL |
| project_id | FK | **Nullable.** -> projects(id) ON DELETE SET NULL |
| status | enum | `active` \| `inactive` \| `graduated` \| `suspended`. Default `active` |
| enrollment_date | date | **Nullable.** تاريخ التسجيل |
| notes | text | **Nullable.** |
| timestamps | - | - |
| softDeletes | timestamp | deleted_at |

### 2. student_enrollments
تسجيلات الطلاب في المقررات الدراسية.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| student_id | FK | -> students(id) |
| course_id | FK | -> courses(id) |
| period_id | FK | -> periods(id) |
| enrollment_date | date | **Nullable.** |
| status | string(255) | `enrolled` \| `completed` \| `dropped`. Default `enrolled` |
| grade | decimal(8,2) | **Nullable.** |
| is_certificate_eligible | boolean | Default false |
| timestamps | - | - |

### 3. courses
المقررات الدراسية.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| project_id | FK | **Nullable.** -> projects(id) |
| name_ar | string(255) | اسم المقرر بالعربية |
| name_en | string(255) | **Nullable.** |
| description | text | **Nullable.** |
| duration | integer | **Nullable.** المدة (بالساعات/الأيام) |
| timestamps | - | - |

**Pivot: course_period**
| الحقل | النوع | ملاحظات |
|-------|------|---------|
| course_id | FK | -> courses(id) |
| period_id | FK | -> periods(id) |

### 4. periods
الفترات الدراسية.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| project_id | FK | **Nullable.** -> projects(id) |
| name_ar | string(255) | اسم الفترة |
| year | integer | السنة |
| start_date | date | **Nullable.** |
| end_date | date | **Nullable.** |
| is_active | boolean | Default true |
| timestamps | - | - |
| softDeletes | timestamp | deleted_at |

### 5. attendance
سجل حضور الطلاب.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| student_id | FK | -> students(id) |
| date | date | التاريخ |
| status | string(255) | `present` \| `absent` \| `excused` |
| note | text | **Nullable.** |
| created_by | FK | **Nullable.** -> users(id) |
| timestamps | - | - |

### 6. certificates
الشهادات.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| certificate_number | string(255) | Unique. التنسيق: `YYYY-XXXXX` |
| design_id | FK | **Nullable.** -> certificate_designs(id) |
| student_id | FK | -> students(id) |
| enrollment_id | FK | **Nullable.** -> student_enrollments(id) |
| barcode_hash | string(255) | **Nullable.** |
| issue_date | date | **Nullable.** |
| is_verified | boolean | Default false |
| verified_at | datetime | **Nullable.** |
| timestamps | - | - |

### 7. certificate_designs
تصاميم الشهادات.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| name | string(255) | اسم التصميم |
| course_id | FK | **Nullable.** -> courses(id) |
| template_image | string(255) | **Nullable.** |
| fields_config | json | **Nullable.** |
| year | integer | **Nullable.** |
| timestamps | - | - |

### 8. certificate_number_sequence
تسلسل أرقام الشهادات (للحفاظ على الترقيم المتزايد).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| year | integer | السنة |
| last_number | integer | Default 0. آخر رقم مستخدم |
| timestamps | - | - |

---

## الموديلات (Models)

### Student
- `user()` -> BelongsTo(User)
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)
- `enrollments()` -> HasMany(StudentEnrollment)
- `attendance()` -> HasMany(Attendance)
- `certificates()` -> HasMany(Certificate)
- `identity_type` يمكن أن يكون: `national_id`, `passport`, `resident_id`, `other`

### StudentEnrollment
- `student()` -> BelongsTo(Student)
- `course()` -> BelongsTo(Course)
- `period()` -> BelongsTo(Period)

### Course
- `project()` -> BelongsTo(Project)
- `periods()` -> BelongsToMany(Period)

### Period
- `project()` -> BelongsTo(Project)
- `courses()` -> BelongsToMany(Course)
- `enrollments()` -> HasMany(StudentEnrollment)
- SoftDeletes

### Attendance
- `student()` -> BelongsTo(Student)
- `createdBy()` -> BelongsTo(User)

### Certificate
- `design()` -> BelongsTo(CertificateDesign)
- `student()` -> BelongsTo(Student)
- `enrollment()` -> BelongsTo(StudentEnrollment)

### CertificateDesign
- `course()` -> BelongsTo(Course)
- `certificates()` -> HasMany(Certificate)

### CertificateNumberSequence
- `nextNumber(int $year): string` -> توليد رقم الشهادة التالي (Pessimistic Locking)

---

## المسارات (Routes)

البادئة: `/admin/students` - الاسم: `admin.students.*`

### العمليات الأساسية
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | / | `StudentController@index` |
| GET | /create | `StudentController@create` |
| POST | / | `StudentController@store` |
| GET | /{student} | `StudentController@show` (profile) |
| GET | /{student}/edit | `StudentController@edit` |
| PUT | /{student} | `StudentController@update` |
| DELETE | /{student} | `StudentController@destroy` |
| POST | /{student}/create-user | `StudentController@createUser` |

### الحضور
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /attendance | `AttendanceController@index` |
| POST | /attendance | `AttendanceController@store` |

### المقررات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /courses | `CourseController@index` |
| POST | /courses | `CourseController@store` |
| GET | /courses/{course}/edit | `CourseController@edit` |
| PUT | /courses/{course} | `CourseController@update` |
| DELETE | /courses/{course} | `CourseController@destroy` |

### الفترات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /periods | `PeriodController@index` |
| POST | /periods | `PeriodController@store` |
| GET | /periods/{period}/edit | `PeriodController@edit` |
| PUT | /periods/{period} | `PeriodController@update` |
| DELETE | /periods/{period} | `PeriodController@destroy` |

### الشهادات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /certificates | `CertificateController@index` |
| GET | /certificates/designs | `CertificateController@designs` |
| GET | /certificates/designs/create | `CertificateController@createDesign` |
| POST | /certificates/designs | `CertificateController@storeDesign` |
| GET | /certificates/designs/{id}/edit | `CertificateController@editDesign` |
| PUT | /certificates/designs/{id} | `CertificateController@updateDesign` |
| DELETE | /certificates/designs/{id} | `CertificateController@destroyDesign` |
| GET | /certificates/issue | `CertificateController@issue` |
| POST | /certificates/generate | `CertificateController@generateCertificates` |
| GET | /certificates/{id}/preview | `CertificateController@preview` |
| GET | /certificates/print-batch | `CertificateController@printBatch` |

### الإحصائيات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /statistics | `StudentStatisticsController@index` |

### استيراد/تصدير Excel
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| POST | /export | `StudentExportController@export` |
| POST | /export-full | `StudentExportController@exportFull` |
| POST | /import | `StudentExportController@import` |
| POST | /import-full | `StudentExportController@importFull` |

### عام
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /verify-certificate/{hash} | `CertificateController@verifyCertificate` (خارج `/admin`) |

---

## التصدير (Export)

### تصدير بسيط (StudentExport)
- **الملف:** `app/Exports/Students/StudentExport.php`
- **الأعمدة:** كود الطالب، نوع الهوية، رقم الهوية، الاسم الأول AR، الاسم الأخير AR، الاسم الأول EN، الاسم الأخير EN، الجنس، تاريخ الميلاد، الجنسية، الهاتف، البريد الإلكتروني، المركز، المشروع، الحالة، تاريخ التسجيل
- **الاستخدام:** `StudentExport::fromIds([1,2,3])` أو `StudentExport::all()`

### تصدير كامل (StudentFullExport)
- **الملف:** `app/Exports/Students/StudentFullExport.php`
- **الشيتات:**
  - `students` - جميع بيانات الطلاب (22 عمود)
  - `enrollments` - التسجيلات (student_code, course_name_ar, period_name_ar, enrollment_date, status, grade, is_certificate_eligible)
  - `certificates` - الشهادات (student_code, certificate_number, design_name, issue_date, is_verified)

---

## الاستيراد (Import)

### استيراد بسيط (StudentImport)
- **الملف:** `app/Imports/Students/StudentImport.php`
- **السلوك:** يبحث عن الطالب بـ `student_code`. إذا كان موجوداً **يحدّث الحقول الفارغة فقط** ولا ينشئ نسخة مكررة. إذا لم يكن موجوداً ينشئ طالباً جديداً.
- **الأعمدة المتوقعة:** كود الطالب، نوع الهوية، رقم الهوية، الاسم الأول AR، الاسم الأخير AR، الاسم الأول EN، الاسم الأخير EN، الجنس، تاريخ الميلاد، الجنسية، الهاتف، البريد الإلكتروني، المركز، المشروع، الحالة، تاريخ التسجيل
- **معالجة خاصة:** ترجمة الجنس (ذكر/أنثى)، ترجمة الحالة (نشط/غير نشط/متخرج/موقوف)، ترجمة نوع الهوية، البحث عن المركز والمشروع بالاسم

### استيراد كامل (StudentFullImport)
- **الملف:** `app/Imports/Students/StudentFullImport.php`
- **السلوك:** يستخدم `studentMap` لربط `student_code` بـ `student_id` عبر الشيتات
- **الشيتات:**
  - `students` - **updateOrCreate** على `student_code` (يمكن تحديث الطلاب الموجودين)
  - `enrollments` - ينشئ تسجيلات جديدة فقط إذا لم تكن موجودة لنفس الطالب + المقرر + الفترة (يمنع التكرار)
  - `certificates` - ينشئ شهادات جديدة

---

## الصلاحيات (Permissions)

الموديل المسجل في `PermissionController@modelGroups()`:
- `App\Models\Admin\Student\Student` => 'الطلاب'
- `App\Models\Admin\Student\Course` => 'الدورات'
- `App\Models\Admin\Student\Period` => 'الفترات'
- `App\Models\Admin\Student\StudentEnrollment` => 'التسجيلات'
- `App\Models\Admin\Student\Attendance` => 'الحضور'
- `App\Models\Admin\Student\Certificate` => 'الشهادات'
- `App\Models\Admin\Student\CertificateDesign` => 'تصاميم الشهادات'

الأفعال المتاحة لكل موديل: `view`, `create`, `edit`, `delete`.

مستخدم `super-admin` لديه جميع الصلاحيات تلقائياً.

---

## عرض الطالب (Profile Page)

يعرض صفحة `admin.students.show`:
- **بطاقة البيانات:** كود الطالب، نوع الهوية، رقم الهوية، الاسم، الجنس، تاريخ الميلاد، الجنسية، الأب، الأم، الهاتف، البريد، المركز، المشروع، العنوان، الملاحظات، الحالة
- **حساب المستخدم:** عرض المستخدم المرتبط أو زر "إنشاء حساب مستخدم"
- **التسجيلات:** جدول التسجيلات في المقررات مع المقرر، الفترة، التاريخ، الحالة، الدرجة، أهلية الشهادة
- **الشهادات:** جدول الشهادات مع رقم الشهادة، التصميم، المقرر، تاريخ الإصدار، حالة التوثيق
- **ملخص الحضور:** إحصائيات (حاضر/غائب/متعذر)
- **آخر 30 تسجيل حضور:** جدول الحضور الحديث

---

## الفورم (Create/Edit Form)

- **حقول الهوية:** نوع الهوية (اختيار من: بطاقة هوية، جواز سفر، إقامة، أخرى) + رقم الهوية
- **البيانات الشخصية:** الاسم AR (مطلوب)، الاسم EN، الأب، الأم، الجنس، تاريخ الميلاد، مكان الميلاد، الجنسية
- **معلومات الاتصال:** الهاتف، البريد الإلكتروني، العنوان
- **المركز والمشروع:** اختيار من القوائم المنسدلة
- **الحالة وتاريخ التسجيل**
- **التسجيلات في المقررات:** جدول ديناميكي (إضافة/حذف صفوف) مع اختيار المقرر والفترة وتحديث الحالة والدرجة

---

## التعديلات الرئيسية التي تمت

### 2026-06-29: إضافة حقول الهوية

- **المشكلة:** لا يمكن تمييز الطلاب بشكل دقيق (تكرار الأسماء).
- **الحل:** إضافة `identity_type` و `identity_number` لجدول `students`.
- **التغييرات:**
  - `database/migrations/2026_06_29_000001_add_identity_fields_to_students_table.php` (جديد)
  - `app/Models/Admin/Student/Student.php`: إضافة الحقول إلى `$fillable`
  - `app/Http/Controllers/Admin/Student/StudentController.php`: إضافة `identity_type`, `identity_number` إلى validation
  - `resources/views/admin/students/form.blade.php`: إضافة حقول الهوية
  - `resources/views/admin/students/profile.blade.php`: عرض حقول الهوية
  - `app/Exports/Students/StudentExport.php`: إضافة الأعمدة
  - `app/Exports/Students/Sheets/StudentsSheet.php`: إضافة الأعمدة
  - `app/Imports/Students/StudentImport.php`: إضافة معالجة نوع الهوية
  - `app/Imports/Students/Sheets/StudentsSheetImport.php`: إضافة الحقول

### 2026-06-29: تعديل منطق الاستيراد (منع التكرار)

- **المشكلة:** الاستيراد البسيط كان ينشئ نسخاً مكررة من الطلاب عند تكرار `student_code`.
- **الحل:** إعادة كتابة `StudentImport` لاستخدام `where('student_code', $code)->first()` ثم تحديث الحقول الفارغة فقط، وإنشاء جديد فقط إذا لم يكن موجوداً.
- **التغييرات:**
  - `app/Imports/Students/StudentImport.php`: لم يعد يستخدم `BaseImport`، ينفذ `ToCollection` و `WithHeadingRow` مباشرة

### 2026-06-29: منع تكرار التسجيلات في الاستيراد الكامل

- **المشكلة:** الاستيراد الكامل كان ينشئ تسجيلات مكررة عند إعادة استيراد نفس الملف.
- **الحل:** إضافة `exists` check قبل إنشاء التسجيل (نفس `student_id` + `course_id` + `period_id`).
- **التغييرات:**
  - `app/Imports/Students/Sheets/EnrollmentsSheetImport.php`: إضافة `where()->exists()` check

### 2026-06-29: إدارة التسجيلات في فورم الطالب

- **المشكلة:** لا يمكن إضافة تسجيلات للطالب مباشرة من فورم الإنشاء/التعديل.
- **الحل:** إضافة جدول ديناميكي في الفورم مع إمكانية إضافة/حذف صفوف (مشابه لبنود طلب الشراء).
- **التغييرات:**
  - `app/Http/Controllers/Admin/Student/StudentController.php`: إضافة validation لقائمة `enrollments` ومعالجتها في `store()` و `update()`
  - `resources/views/admin/students/form.blade.php`: إضافة قسم التسجيلات مع JS ديناميكي
  - في `update()`: يتم التحقق من عدم وجود تكرار قبل إنشاء التسجيل

### 2026-06-29: استيراد/تصدير Excel للطلاب

- تمت إضافة:
  - `app/Exports/Students/StudentExport.php` (تصدير بسيط)
  - `app/Exports/Students/StudentFullExport.php` (تصدير كامل - 3 شيتات)
  - `app/Exports/Students/Sheets/` (3 كلاسات شيت)
  - `app/Imports/Students/StudentImport.php` (استيراد بسيط مع update بدلاً من create)
  - `app/Imports/Students/StudentFullImport.php` (استيراد كامل مع studentMap)
  - `app/Imports/Students/Sheets/` (3 كلاسات شيت)
  - `app/Http/Controllers/Admin/Student/StudentExportController.php` (4 دوال)
  - 4 مسارات في `routes/web.php`
  - أزرار تصدير/استيراد في `resources/views/admin/students/index.blade.php`

---

### 2026-07-06: Soft Delete على جدول الوسيط project_student

- **المشكلة:** عند حذف طالب (`$student->delete()`) كان يختفي من جميع المشاريع (soft delete على جدول `students`).
- **الحل:** إضافة `deleted_at` لجدول `project_student` (الوسيط)، وتغيير منطق الحذف ليكون على مستوى الوسيط بدلاً من الطالب.
- **التغييرات:**
  - `database/migrations/2026_07_06_000001_add_soft_deletes_to_project_student_table.php` (جديد) — إضافة `softDeletes()` لجدول `project_student`.
  - `app/Models/Admin/Student/Student.php`:
    - تعديل `projects()`: إضافة `withPivot(['deleted_at'])` و `whereNull('project_student.deleted_at')` لتصفية المحذوفين.
    - إضافة `allProjects()`: علاقة BelongsToMany بدون فلتر deleted_at (للاستعلامات الإدارية).
  - `app/Http/Controllers/Admin/Student/StudentController.php`:
    - `destroy()`: الآن يستقبل `project_id` من الـ request ويستخدم `updateExistingPivot` لتعيين `deleted_at` على الوسيط. إذا لم يُمرر `project_id` يحاول أخذه من سجل الموظف (`Employee`). إذا لم يوجد مشروع سياق، يرجع للسلوك القديم (soft delete للطالب). كما يمسح `project_id` المباشر من الطالب إذا كان مطابقاً.
    - `addToProjects()`: عند إضافة طالب لمشروع، يتحقق أولاً إذا كان هناك وسيط موجود (حتى المحذوف). إذا كان موجوداً و `deleted_at !== null`، يعيد تفعيله (يضع `deleted_at = null`). إذا لم يكن موجوداً، ينشئ وسيطاً جديداً. هذا يمنع أخطاء duplicate key عند إعادة إضافة طالب سبق حذفه من المشروع.
  - `resources/views/admin/students/index.blade.php`: إضافة hidden input `project_id` في فورم الحذف (قيمة الفلتر الحالي في الصفحة). تغيير رسالة التأكيد إلى "إزالة من المشروع".
- **ملاحظات:**
  - العلاقة `projects()` الآن ترجع فقط المشاريع النشطة (غير المحذوفة). لرؤية الكل (بما في ذلك المحذوفة) استخدم `allProjects()`.
  - التوافق مع الإصدارات السابقة: عملية `sync` في `store()` و `update()` لا تتأثر لأن الفلتر `deleted_at` يستخدم فقط في الاستعلامات؛ أما `sync` فيتعامل مباشرة مع جدول الوسيط.

## إرشادات للمطورين

- **كود الطالب فريد:** يستخدم `student_code` كمفتاح للتمييز بين الطلاب في الاستيراد والتصدير.
- **هوية الطالب:** حقول `identity_type` و `identity_number` تساعد في تمييز الطلاب ذوي الأسماء المتشابهة.
- **التسجيلات المتعددة:** الطالب يمكن أن يسجل في أكثر من مقرر وأكثر من فترة (علاقة One-to-Many عبر `student_enrollments`).
- **الاستيراد غير مدمر:** الاستيراد البسيط لا يحذف أو يستبدل البيانات الموجودة، بل يضيف المفقود فقط.
- **بعد تعديل أي Blade view:** قم بتشغيل `php artisan view:clear`
- **ترتيب المسارات مهم:** مسارات `/students/attendance`, `/students/courses`, `/students/periods`, `/students/certificates`, `/students/statistics` يجب أن تكون قبل `Route::resource('students', ...)` لأن resource ستلتقط `{student}`.
- **إنشاء حساب مستخدم:** يتم من زر "إنشاء حساب مستخدم" في صفحة الطالب (`createUser`). البريد الافتراضي: `student_code@student.rowad.app`، كلمة المرور: `student123`.
