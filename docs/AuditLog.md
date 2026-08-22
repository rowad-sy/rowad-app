# دراسة نظام سجل التدقيق (Audit Log) المخصص

## 1. نظرة عامة

هذه وثيقة **دراسة وتصميم** (بدون تنفيذ) لنظام سجل تدقيق (Audit Log) **مخصص بالكامل** (غير مبني على مكتبة خارجية) ليتناسب مع بنية تطبيق "نظام روّاد" الحالية.

الهدف: تسجيل **كل التغييرات الحاصلة على البيانات** داخل النظام بطريقة قوية، فعّالة أدائياً، محمية من العبث، وقابلة للاستعلام والعرض.

**النتائج المتفق عليها مع صاحب المشروع:**
| القرار | الاختيار |
|--------|----------|
| نطاق التسجيل | **جميع الموديلز (~40)** |
| مستوى التفاصيل | **تفاصيل على مستوى الحقل** (old → new لكل حقل متغير) |
| الحماية من العبث | **نعم** — سلسلة تجزئة Hash Chain |
| واجهة العرض | **نمط Django Admin** — زر "التاريخ" بجانب كل سجل يعرض كل تغييراته + صفحة استعلام عامة |

---

## 2. تحليل الوضع الحالي

### 2.1 قائمة الموديلز (40 موديلاً فريداً)

| الوحدة | الموديلات | العدد |
|--------|-----------|-------|
| الأساس/النظام | `User`, `Admin\Center`, `Admin\Department`, `Admin\Group`, `Admin\Permission`, `Admin\Project` | 6 |
| إدارة المشاريع | `Admin\ProjectTask` | 1 |
| الموارد البشرية | `Hr\Employee`, `Hr\Contract`, `Hr\EmployeeAttendance`, `Hr\EmployeeContact`, `Hr\EmployeeDocument`, `Hr\EmployeeEducation`, `Hr\EmployeeNote`, `Hr\JobPosition`, `Hr\LeaveBalance`, `Hr\LeaveRequest`, `Hr\LeaveType`, `Hr\Salary`, `Hr\Warning`, `Hr\WorkSchedule` | 14 |
| الطلاب | `Student\Student`, `Student\StudentEnrollment`, `Student\Course`, `Student\Period`, `Student\Attendance`, `Student\Certificate`, `Student\CertificateDesign`, `Student\CertificateNumberSequence` | 8 |
| اللوجستي | `Logistics\ApprovalRule`, `Logistics\Asset`, `Logistics\DeletedItem`, `Logistics\LogisticsSetting`, `Logistics\PurchaseRequest`, `Logistics\PurchaseRequestApproval`, `Logistics\PurchaseRequestItem`, `Logistics\Warehouse`, `Logistics\WarehouseItem` | 9 |
| التقنية | `Tech\TechIssue`, `Tech\TechEquipment` | 2 |

> **ملاحظة:** توجد نسخ مكررة قديمة تحت `App\Models\` مباشرة (`Center`, `Group`, `Project`, `Permission`) بينما تُستخدم نسخ `App\Models\Admin\*`. يجب تأكيد أي نسخة فعلياً مستخدمة قبل ربط التسجيل لتجنب تسجيل مزدوج/ناقص.

### 2.2 نقاط تغيير البيانات (أين يحدث الكتابة)

| المسار | الوصف |
|--------|-------|
| **الكونترولرات** | ~20 كونترولر `Admin` تستدعي `create()/update()/delete()` مباشرة |
| **عمليات الحالة المخصصة** | `ProjectTaskController@updateStatus`, `PurchaseRequestApprovalController@approve/reject`, `LeaveApprovalController@approve/reject`, `UserController@toggleStatus` |
| **الاستيراد من Excel** | `StudentImport`, `StudentFullImport`, `EmployeeFullImport`, `LogisticsExportController@import*` — تستخدم `updateOrCreate()` وهي تطلق أحداث Eloquent تلقائياً |
| **العلاقات Many-to-Many (Pivot)** | `sync()`/`attach()`/`detach()` على `project_student`, `group_user`, `center_project`, `course_period`, `logistics_approval_rule_user` — **لا تُطلق أحداث موديل عادية** وتحتاج معالجة خاصة |
| **النماذج الذاتية (booted events)** | `Employee::booted()` ينشئ 7 جداول دوام تلقائياً عند الإنشاء — هذه كتابة ثانوية يجب مراعاتها |
| **المصادقة** | تسجيل الدخول/الخروج/المحاولات الفاشلة (أحداث Laravel Auth) |

### 2.3 الأنماط والقيود الحالية ذات الصلة

- **قاعدة البيانات:** MySQL/MariaDB (DB_CONNECTION=mysql) — تتيح فهارس قوية، JSON، والتقسيم.
- **قائمة الانتظار:** `QUEUE_CONNECTION=database` — مناسبة لمعالجة غير متزامنة للسجلات.
- **الموديلات** لا تستخدم Traits مشتركة حالياً (فقط `SoftDeletes`, `HasFactory`) — إضافة Trait سهل.
- **نظام الصلاحيات موجود وجاهز** (`Permission` + middleware `permission:` + `@canPermission`) — سيُدمج معه تسجيل الوصول لعرض السجلات.
- **نظام "أرشفة الحذف" موجود سابقاً** (`logistics_deleted_items`) — مثال على تتبع مخصص لكنه محدود بحالة واحدة؛ نظامنا المقترح يعممه.

---

## 3. متطلبات النظام (Functional Requirements)

1. تسجيل كل من: `created`, `updated`, `deleted`, `restored`, وأحداث مخصصة (`login`, `logout`, `failed`, `import`, `export`, `approve`, `reject`).
2. لكل تعديل: تخزين **قبل/بعد على مستوى الحقل** مع اسم الحقل وقيمته قبل وبعد.
3. تحديد الفاعل: المستخدم، نوعه، IP، User-Agent، ومعرّف طلب موحّد (لتجميع عمليات الطلب الواحد).
4. إخفاء الحقول الحساسة (كلمات المرور، الرموز) — لا تُخزَّن أبداً.
5. حماية من العبث عبر سلسلة تجزئة، مع القدرة على التحقق من سلامة أي سجل أو السلسلة كاملة.
6. عرض نمط Django Admin: زر "التاريخ" لكل سجل في صفحات العرض + صفحة استعلام عامة بفلاتر.
7. عدم التأثير على أداء العمليات الحالية (التسجيل عبر قائمة انتظار، استثناء الحالات عالية الحجم).
8. ربط القراءة بنظام الصلاحيات الموجود.

---

## 4. قرارات التصميم الأساسية

### 4.1 تخزين: جدولان مقابل جدول واحد

| المعيار | جدول واحد (`audit_logs` + JSON) | **جدولان (الموصى به)** `audit_logs` + `audit_changes` |
|---------|-------------------------------|------------------------------------------------------|
| البساطة | أبسط كتابةً | كتابة معقدة قليلاً (حدثان) |
| تصفية/استعلام مفصّل | JSON لا يُفهرس جيداً | أعمدة مفهرسة صريحة |
| حجم الصف | كبير (يتضخم مع الحقول) | صغير لكل صف |
| عرض التاريخ لكل سجل | سهل | سهل |
| استعلام "من غيّر الحقل X" | صعب/بطيء | سهل عبر فهرس `field` |
| الحجم الكلي | أكبر (تكرار old/new كاملاً) | أصغر (الحقول المتغيرة فقط) |

**القرار:** تصميم من **جدولين** — رأس الحدث (`audit_logs`) + تفاصيل الحقول (`audit_changes`). هذا يحقق "الكفاءة العالية" في الاستعلام والتخزين معاً.

### 4.2 مستوى التفاصيل

يُخزَّن لكل حدث **قائمة الحقول المتغيرة فقط** (وليس snapshot كامل)، مع قيمة قبل وبعد لكل حقل. يشمل الحقول:
- قيمة الحقل قبل (`old_value`) وبعد (`new_value`).
- نوع الحقل (لإعادة التنسيق في العرض: تاريخ، رقم، منطقي، نص).
- علامة `is_masked` للحقول المحمية.

### 4.3 الحماية من العبث

سلسلة تجزئة SHA-256 حيث يحتوي كل سجل على تجزئة تخصّه وتجزئة السجل السابق (مبدأ Blockchain):

```
hash = SHA256( prev_hash . request_id . user_id . model . model_id .
               event . old_values_json . new_values_json . created_at )
```

- أي تعديل/حذف/إدراج لسجل قديم يكسر جميع التجزئات اللاحقة → يمكن اكتشاف العبث فوراً.
- **مراسٍ خارجية (Anchors):** لتغطية حالة حذف السجلات من النهاية، تُحفظ تجزئة مختزلة دورية (يومية مثلاً) خارج جدول التدقيق (ملف بصلاحيات مقيدة على القرص أو سجل تطبيق محمي) للمطابقة الدورية.

### 4.4 الأداء: تسلسلي أم غير متزامن

| السيناريو | الأسلوب |
|-----------|---------|
| عمليات عادية (CRUD) | كتابة مباشرة متزامنة داخل نفس المعاملة — بسيطة ولا تفقد سجلات |
| عمليات عالية الحجم (استيراد Excel، حضور جماعي) | دفع عبر قائمة الانتظار (`database` queue) مع `afterCommit` |
| الحالات الحرجة (حذف، موافقات) | تسجيل متزامن إجباري (لا يضيع أبداً) |

---

## 5. مخطط قاعدة البيانات المقترح

### 5.1 جدول `audit_logs` (رأس الحدث)

| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint, PK | |
| request_id | char(32) | معرّف موحّد لكل طلب HTTP/عملية (يجمع كل تغييرات الطلب الواحد) |
| user_id | FK → users, nullable | الفاعل (null = نظامي/seed/unauthenticated) |
| user_type | string(20), nullable | نوع الفاعل (admin/employee/student/super-admin) |
| ip_address | string(45), nullable | IPv4/IPv6 |
| user_agent | string(255), nullable | |
| model | string(191) | FQCN للموديل (App\Models\Admin\Hr\Employee) |
| model_name | string(191) | اسم قصير قابل للقراءة (Employee) — لفلاتر سريعة |
| model_id | bigint | معرّف السجل |
| event | string(20) | created \| updated \| deleted \| restored \| login \| logout \| failed \| import \| export \| approve \| reject \| custom |
| description | text, nullable | وصف عربي إنساني (اختياري) |
| hash | char(64) | SHA-256 لهذا السجل |
| prev_hash | char(64), nullable | تجزئة السجل السابق (أول سجل = null) |
| old_values | json, nullable | snapshot كامل قبل (يُملأ فقط لـ created/updated عند الحاجة) |
| new_values | json, nullable | snapshot كامل بعد |
| created_at | timestamp | وقت الحدث |

### 5.2 جدول `audit_changes` (تفاصيل الحقول — الأعمدة قبل/بعد)

| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint, PK | |
| audit_log_id | FK → audit_logs, CASCADE | |
| field | string(191) | اسم العمود (status, salary, ...) |
| label | string(191), nullable | تسمية عربية قابلة للتكوين (الحالة، الراتب...) |
| old_value | text, nullable | القيمة قبل التعديل (serialized text) |
| new_value | text, nullable | القيمة بعد التعديل |
| value_type | string(20) | string \| integer \| decimal \| boolean \| date \| datetime \| json |
| is_masked | boolean, default false | هل القيمة مخفية (لا تعرض الأصلية) |

> عند `created`: لا توجد صفوف في `audit_changes` (يُخزن snapshot في `new_values`). عند `deleted`: snapshot في `old_values`. عند `updated`: صف واحد لكل حقل تغير في `audit_changes`.

### 5.3 الفهارس المطلوبة

```sql
audit_logs:
  INDEX (model, model_id)          -- تاريخ كائن معين (زر Django)
  INDEX (model_name)
  INDEX (user_id)
  INDEX (event)
  INDEX (created_at)
  INDEX (request_id)

audit_changes:
  INDEX (audit_log_id)
  INDEX (field)                    -- "من غيّر حقل status؟"
```

**تقسيم (Partitioning) اختياري:** تقسيم `audit_logs` و `audit_changes` حسب `created_at` (شهرياً/سنوياً) على MariaDB لتسريع الاستعلامات التاريخية وتسهيل الأرشفة.

---

## 6. آلية الالتقاط (Capture)

### 6.1 Trait `Auditable` + Eloquent Events

- بناء Trait واحد `App\Models\Concerns\Auditable` يُضاف إلى كل موديل، يسجّل مستمعات على أحداث الموديل:

```php
trait Auditable {
    public static function bootAuditable(): void
    {
        static::created(function ($model)  { AuditLogger::log($model, 'created'); });
        static::updated(function ($model)  { AuditLogger::log($model, 'updated'); });
        static::deleted(function ($model)  { AuditLogger::log($model, 'deleted'); });
        static::restored(function ($model) { AuditLogger::log($model, 'restored'); });
    }
}
```

- **السبب:** الالتقاط على مستوى الموديل (وليس الكونترولر) يغطي **كل** مسارات الكتابة تلقائياً: الكونترولرات، الاستيراد عبر `updateOrCreate`، وطلبات Tinker/CLI. لا يمكن تفويت مسار أو نسيان كونترولر.
- **تكوين اختياري في الموديل:**
  - `protected $auditExclude = ['remember_token'];` — حقول لا تُسجل أبداً.
  - `protected $auditMask = ['password', 'identity_number'];` — حقول تُسجل بعلامة `is_masked` فقط.
  - `$auditIgnoreEvents` — لإيقاف التسجيل في seeders/factories (`AuditLogger::silenced(fn () => ...)`).

### 6.2 حساب الـ Diff

- لحدث `updated`: المقارنة بين `$model->getOriginal()` و `$model->getAttributes()`.
- استخدام `getDirty()` فقط، مع معالجة الـ casts لتوحيد التنسيق (dates → `Y-m-d`, booleans → true/false, decimals → numeric) حتى يكون قبل/بعد متساويين.
- تجاهل الحقول غير المتغيرة نهائياً (لا ضوضاء، لا تكرار).

### 6.3 سياق الفاعل

```php
[
  'user_id'    => auth()->id(),                    // أو null
  'user_type'  => auth()->user()?->type,
  'ip_address' => request()->ip(),
  'user_agent' => substr((string) request()->userAgent(), 0, 255),
  'request_id' => Str::uuid()->toString(),         // يُثبّت لكل طلب عبر middleware
]
```

- يُثبّت `request_id` عبر middleware على مجموعة المسارات المحمية، ويستمر عبر العملية بأكملها لتجميع التغييرات المتعددة (سجل + بنوده + موافقاته) تحت معرّف واحد.
- للمهام التي لا تمر عبر HTTP (قائمة انتظار، أمر console) يُوَلَّد `request_id` في بداية المهمة.

### 6.4 أحداث غير Eloquent

| الحدث | المصدر | التمثيل |
|-------|--------|---------|
| تسجيل الدخول/الخروج/فشل المحاولة | `Illuminate\Auth\Events\Login/Logout/Failed` | audit_logs (model=User, model_id=user id, event=login/logout/failed) |
| استيراد Excel | في نهاية عملية الاستيراد | event=import, description="استيراد 154 موظفاً" |
| تصدير Excel | في نهاية التصدير | event=export |
| موافقة/رفض طلب شراء أو إجازة | `approve/reject` | event=approve/reject (تُلتقط أيضاً كـ updated على الحقل status) |

### 6.5 العلاقات Pivot

لا تُطلق أحداث Eloquent عادية، لذا تُعالج بآلية صريحة:
- خدمة `AuditLogger::logPivot($parentModel, $relation, $attachedIds, $detachedIds, $syncedIds)` تُستدعى من الكونترولرات عند `sync/attach/detach` (مثل `addToProjects` في StudentController).
- بديل متقدم (لاحقاً): `db:table` triggers أو مخزن pivot مخصص، لكن البديل الأول أبسط ويناسب الحجم الحالي.

---

## 7. سلسلة التجزئة (Hash Chain) — التفاصيل

### 7.1 بناء الـ hash

```php
$hash = hash('sha256', implode('|', [
    $prevHash,            // hash آخر سجل في الجدول (سلسلة عامة)
    $requestId, $userId,
    $model, $modelId,
    $event,
    json_encode($changesOrdered),
    $createdAt,
]));
```

- **سلسلة عامة واحدة:** أي تلاعب بأي سجل قديم يكسر كل ما بعده → أبسط وأقوى إثبات.
- **بديل مكمّل (اختياري):** عمود `entity_prev_hash` لكل كيان ليتسنى التحقق من تسلسل كائن واحد دون اجتياز السلسلة الكاملة. (زيادة تخزين صغيرة مقابل تحقق أسرع.)
- القيم المتضمنة في الـ hash **مجرّدة ومرتّبة** (canonical) لتجنب اختلاف التجزئة بسبب ترتيب المفاتيح في JSON.

### 7.2 التحقق

- أمر `php artisan audit:verify` يعيد حساب السلسلة كاملة ويبلّغ عن أول موضع كسر + إحصائية.
- عرض "التحقق من السلامة" في الواجهة (عدد السجلات، صحة السلسلة، آخر تجزئة).
- دالة `AuditLog::verifyChain()` تُستخدم في الاختبارات.

### 7.3 المراسي الخارجية (Anchors)

- مهمة مجدولة يومياً تحسب `SHA256(آخر تجزئة + عدد السجلات)` وتكتبها إلى ملف خارج قاعدة البيانات (مثلاً `storage/app/private/audit-anchors/YYYY-MM-DD.sig`) بصلاحيات قراءة فقط للمالك.
- تُقارن لاحقاً مع السجلات للكشف عن حذف نهاية السلسلة.

---

## 8. الأداء والكفاءة

| الممارسة | التطبيق |
|----------|---------|
| **قائمة انتظار** | الأحداث غير الحرجة تُدفع عبر `ShouldQueue` (database queue موجودة). الحرجة تُسجل متزامناً |
| **أخذ اللقطة عند الحاجة فقط** | `created`/`deleted` فقط يخزنان snapshot؛ `updated` يخزن الحقول المتغيرة فقط |
| **حذف الضوضاء** | لا تسجيل للقراءات، ولا للحقول غير المتغيرة، ولا للمستخدم النظامي في seeders |
| **الفهارس** | حسب §5.3 |
| **التقسيم** | Partitioning شهري حسب `created_at` مع نمو الجدول |
| **المعاملة (Transaction)** | التسجيل المتزامن داخل معاملة العملية نفسها (`afterCommit` للمؤجل) لضمان الاتساق |

### تقدير حجم تقريبي

- صف `audit_logs` ≈ 250–400 بايت + صف `audit_changes` لكل حقل ≈ 100–200 بايت.
- مثال: 200 عملية تعديل يومياً بمتوسط 3 حقول → ~800 صف/يوم → ~24 ألف صف/شهر (~10–15 MB) — مقبول تماماً، والنمو يُدار بالتقسيم والأرشفة.

---

## 9. التكامل مع نظام الصلاحيات

1. موديل جديد `App\Models\AuditLog` يُسجَّل في `PermissionController@modelGroups()` ضمن قسم **"النظام والتدقيق"**.
2. الأفعال: `view` فقط (لا create/edit/delete — الجدول للقراءة فقط ولا يُكتب إلا من الخدمة الداخلية).
3. أذونات الصفحات: `page:admin.audit-logs.index`.
4. **قرار وصول زر التاريخ:** يُعرض زر "التاريخ" بجانب أي سجل فقط لمن لديه صلاحية `AuditLog.view` — حتى لا يطّلع أي مستخدم على نشاط الآخرين.
5. حماية الطريق: middleware `permission:App\Models\AuditLog,view` على صفحة الاستعلام والمعلومات.

---

## 10. الواجهة (نمط Django Admin)

### 10.1 صفحة الاستعلام العامة `/admin/audit-logs`

- جدول يعرض: التاريخ، الفاعل، الموديل، الحدث (شارة ملونة)، وصف مختصر.
- **فلاتر:** الموديل (قائمة منسدلة من 40 موديلاً)، الحدث، المستخدم، نطاق تاريخ، بحث نصي، ترقيم صفحات.
- **زر "عرض"** لكل سجل → صفحة/مودال تفاصيل تعرض حقول `audit_changes` في جدول (الحقل، التسمية، القيمة قبل، القيمة بعد).

### 10.2 زر "التاريخ" لكل سجل (Django-style)

- يُضاف **مكوّن Blade/Livewire قابل لإعادة الاستخدام** `record-history` يستقبل `(model, model_id)`:
  - زر صغير أيقونة "الساعة/التاريخ" بجانب كل سجل في صفحات `index` / `show`.
  - عند الضغط: مودال يعرض **خطاً زمنياً** بكل أحداث السجل مرتبة:
    - "2026-08-16 14:30 — محمد العلي (admin) عدّل الحالة من pending إلى approved"
    - لكل تعديل: جدول حقول قبل/بعد، مع تمييز `is_masked`.
  - بيانات جاهزة عبر استعلام واحد: `audit_logs where model=? and model_id=?` مع `load('changes')`.
- الاستدعاء من أي صفحة: `<x-audit-record-history :model="Employee::class" :model-id="$employee->id" />`.
- **التوسيع المستقبلي:** إمكانية إضافة نفس المكوّن في صفحات العرض (Profile) لعرض تاريخ كامل لأي كيان.

---

## 11. سياسة الاحتفاظ والأرشفة

1. إعدادات قابلة للتكوين: `audit.retention_months` (افتراضي 24 شهراً).
2. أرشفة: نقل الصفوف الأقدم إلى جداول باردة (`audit_logs_archive`, `audit_changes_archive`) أو تصدير ملفات JSON مضغوطة.
3. مهمة مجدولة للتنظيف/الأرشفة مع تنبيه عند تجاوز حجم معين.
4. **تنبيه هام:** أي حذف/أرشفة يجب أن يحافظ على سلسلة التجزئة (إعادة ربط `prev_hash` أو إغلاق السلسلة بمرساة قبل القص).

---

## 12. الاستعلامات والرؤى المتوقعة

| السؤال | التنفيذ |
|--------|---------|
| من غيّر سجل X ومتى؟ | `WHERE model=? AND model_id=?` |
| كل ما فعله المستخدم Y؟ | `WHERE user_id=?` + `join audit_changes` |
| من غيّر حقل "status" لطلبات الشراء؟ | `audit_changes WHERE field='status'` |
| متى حُذف سجل Z؟ | `WHERE model=? AND model_id=? AND event='deleted'` |
| عمليات اليوم (إحصاء) | `GROUP BY event, DATE(created_at)` |
| نشاط كائن ما ضمن فترة | `model_id` + `created_at BETWEEN` |

---

## 13. خطة الاختبارات

| النوع | الحالات |
|-------|---------|
| وحدة | حساب الـ diff (dates/booleans/decimals)، الإخفاء `is_masked`، الاستثناء `$auditExclude` |
| تكامل | إنشاء/تعديل/حذف/استعادة أي موديل ينتج سجل صحيح مع الحقول المتغيرة فقط |
| سلسلة التجزئة | التحقق من سلامة السلسلة، اكتشاف أي تعديل/حذف/إدراج وسط السلسلة |
| الأحداث غير Eloquent | login/logout/import/approve/reject تسجل بشكل صحيح |
| العلاقات | `sync` على `project_student` يسجل إضافة/إزالة |
| الأداء | عملية استيراد 1000 سجل لا تتجاوز مهلة معقولة عبر queue |
| الصلاحيات | غير المصرّح له لا يرى زر التاريخ ولا صفحة السجلات |

---

## 14. خطة التنفيذ (مراحل)

| المرحلة | المحتوى | المخرجات |
|---------|---------|----------|
| 1 | Migration للجدولين + الفهارس | `audit_logs`, `audit_changes` |
| 2 | `AuditLog` model + `AuditLogger` service + `Auditable` trait | جوهر التسجيل |
| 3 | تطبيق trait على الـ 40 موديلاً + ضبط exclusions/masks | تغطية كاملة |
| 4 | مستمعو أحداث Auth + تسجيل الاستيراد/التصدير/الموافقات + دالة `logPivot` | تغطية الأحداث غير المباشرة |
| 5 | تسجيل `AuditLog` في الصلاحيات + المسارات | حماية الوصول |
| 6 | صفحة الاستعلام العامة + مكوّن زر "التاريخ" | الواجهة بنمط Django |
| 7 | مهمة المراسي + أمر `audit:verify` | الحماية من العبث |
| 8 | الاختبارات + ضبط queue/partitioning | الاستقرار والأداء |

---

## 15. المخاطر والاعتبارات

| المخاطرة | المعالجة |
|----------|----------|
| تسجيل مزدوج/ناقص بسبب الموديلات الجذرية المكررة | توحيد الـ FQCN المستخدم فعلياً قبل الربط |
| `updated` قد يطلق حدثاً دون تغيير حقيقي (مثل updateStatus بنفس القيم) | المقارنة عبر `getDirty()` قبل التسجيل |
| Soft deletes: `deleted` تسجل الحذف الناعم فقط؛ الحذف النهائي عبر `forceDelete` | تسجيل الحدثين بأسماء مميزة |
| العلاقات Pivot لا تُلتقط تلقائياً | دالة `logPivot` صريحة في نقاط `sync/attach/detach` |
| استيراد ضخم ينتج آلاف السجلات | قائمة انتظار + تجميع، وحجم تقديري مذكور في §8 |
| حذف نهاية السلسلة (tail trimming) | مراسٍ خارجية يومية |
| قيم حساسة (passwords, identity numbers) | `$auditMask` + `$auditExclude` + اختبارات |
| الأداء في الـ JSON القديم | التصميم ثنائي الجداول + فهارس + تقسيم |
| تعارض أسماء الموديلات نفسها في وحدات مختلفة | الاعتماد على FQCN في `model` + `model_name` للعرض |

---

## 16. الخلاصة والتوصيات

1. **النظام المقترح:** تصميم مخصص من **جدولين** + Trait `Auditable` على مستوى الموديل، يغطي كل مسارات الكتابة (كونترولرات + استيراد + أحداث Auth) بتفاصيل حقل-بحقل.
2. **الكفاءة:** تسجيل متزامن للحالات الحرجة + قائمة انتظار للعمليات الضخمة + فهارس مقصودة + تقسيم اختياري.
3. **الأمان:** سلسلة تجزئة SHA-256 + مراسٍ خارجية + إخفاء الحقول الحساسة + ربط العرض بنظام الصلاحيات.
4. **الواجهة:** زر "التاريخ" بنمط Django Admin لكل سجل + صفحة استعلام عامة بفلاتر.
5. **لا يتطلب أي مكتبة خارجية** — كله مبني على أدوات Eloquent/MySQL الموجودة، مما يسهل التعديل والتخصيص مستقبلاً.

**الخطوة التالية المقترحة:** تنفيذ المراحل 1–3 (التخزين + جوهر التسجيل + التغطية الكاملة للموديلز) ثم البناء عليها بالواجهة والأمان.

---

*وثيقة دراسة — تُنفَّذ لاحقاً حسب الاتفاق. راجعتها بتاريخ 2026-08-16.*
