# نظام الصلاحيات - الدليل الكامل

دليل شامل ومرجع عملي لكل ما تحتاجه لفهم نظام الصلاحيات في المشروع والعمل به وتوسيعه مستقبلاً.
يعتمد هذا الدليل على الكود الفعلي الحالي، ويشمل الدروس المستفادة من إصلاحات سابقة حتى لا تتكرر الأخطاء.

---

## 1. المفاهيم الأساسية

النظام مبني على **جمع الصلاحيات (Aggregation)** وليس "دور" واحد للمستخدم:

- المستخدم قد يملك صلاحيات **مباشرة** (معيّنة له شخصياً).
- وقد يملك صلاحيات **عبر مجموعات** ينتمي إليها.
- كل ذلك **يُجمع** وتُؤخذ "الصلاحية الفعّالة": إذا وجدت أي صلاحية تسمح بالوصول → يُسمح به.

### أنواع المستخدمين

| النوع | السلوك |
|-------|--------|
| `super-admin` | يتجاوز جميع الفحوصات: `PermissionHelper::can()` ترجع `true` فوراً، و`getEffectiveScope()` ترجع `sees_all = true`. **لا يحتاج أي سجلات صلاحيات.** |
| `admin` / `user` / أي نوع آخر | تُطبَّق عليها قيود الصلاحيات كاملة. |
| `student` | حساب الطالب نفسه (له مسار مختلف خارج نظام admin بشكل عام). |

> القاعدة: التعامل مع `type` يتم فقط في `super-admin`. لا تعتمد على `admin` لتمييز صلاحيات — كل شيء يعتمد على سجلات `permissions`.

---

## 2. تشريح سجل الصلاحية (جدول `permissions`)

كل سجل = **تعيين × موديل × نطاق × أعلام**.

| العمود | المعنى |
|--------|--------|
| `id` | المفتاح الرئيسي |
| `user_id` | المستخدم المعيّن له (أو `null` إذا كان للمجموعة) |
| `group_id` | المجموعة المعيّن لها (أو `null` إذا كان للمستخدم) |
| `model_names` | JSON بآخر موديل **واحد** (مثال: `["App\\Models\\Admin\\Student\\Student"]`) |
| `model_id` | صلاحية على **سجل واحد معيّن** (نادر الاستخدام — يُترك `null`) |
| `center_id` | **النطاق**: المركز (null = جميع المراكز) |
| `project_id` | **النطاق**: المشروع (null = جميع المشاريع) |
| `cohort_id` | **النطاق**: الفوج (null = جميع الأفواج) |
| `can_view` | صلاحية العرض |
| `can_create` | صلاحية الإضافة |
| `can_edit` | صلاحية التعديل |
| `can_delete` | صلاحية الحذف |

### قواعد مهمة

- `user_id` و `group_id` **متنافيان**: سجل واحد يُعيّن إما لمستخدم أو لمجموعة.
- `model_names` يجب أن يحتوي موديلاً **واحداً فقط** (الأمر `permissions:expand` يقسّم أي سجل متعدد الموديلات إلى سجلات مستقلة).
- **النطاق فارغ (null في الثلاثة)** يُفسَّر على أنه "جميع المنظمة" — راجع قسم `sees_all`.

### مثال واقعي (حالة يوسف — مدير مشروع الرواد)

```
user_id   = 12 (يوسف)
model_names = ["App\Models\Admin\Student\Attendance"]
center_id = null
project_id = 3 (معهد الرواد للعلوم التقنية)
cohort_id = null
can_view  = 1, can_create = 1
```

---

## 3. أنواع الصلاحية (من حيث الموضوع الذي تُعطى عليه)

### 3.1 صلاحيات الموديلات (Model Permissions)

تُكتب باسم الموديل الكامل، مثال:

- `App\Models\Admin\Student\Student` → صفحة الطلاب
- `App\Models\Admin\Hr\Employee` → الموظفون
- `App\Models\Admin\Student\Attendance` → سجل الحضور
- `App\Models\Admin\ProjectTask` → المهام

### 3.2 صلاحيات الصفحات (Page Permissions)

تُكتب ببادئة `page:` متبوعة باسم الراوت، مثال:

- `page:admin.project-manager.dashboard` → لوحة مدير المشروع
- `page:admin.logistics.statistics` → إحصائيات اللوجستي
- `page:admin.physiotherapy.followups.index` → متابعة المرضى

التحقق منها يتم عبر `PermissionHelper::canViewPage($user, $routeName)` وهي في جوهرها `can($user, 'page:'.$routeName, 'view')`.

> لا توجد `can_create/edit/delete` مفيدة للصفحات — عادةً `can_view` فقط.

### 3.3 سجل الكتالوج (PermissionModelCatalog)

كل الموديلات القابلة للتحكم مسجّلة في `app/Support/PermissionModelCatalog.php` مقسمة إلى فئات (إدارة، موارد بشرية، طلاب، تقنية، لوجستي، إدارة مشاريع، علاج فيزيائي، تدقيق، صفحات).

هذا الملف هو الذي يبني شاشة إدارة الصلاحيات (المصفوفة).

> **إذا أضفت موديلاً جديداً ولم تسجّله هنا، لن يظهر في شاشة الصلاحيات ولا يمكن منحه لأي أحد.** تأكد من إضافته (مع ترتيب ثابت كي لا تتغير المفاتيح الرقمية).

---

## 4. الأعلام الأربعة (Actions)

| العلم | العمود | يتحكم في |
|-------|--------|----------|
| عرض | `can_view` | فتح الصفحة / رؤية القائمة |
| إضافة | `can_create` | زر/نماذج الإضافة |
| تعديل | `can_edit` | تعديل سجل موجود |
| حذف | `can_delete` | حذف / إزالة |

تُطبَّق في الـ Controller عبر middleware أو `PermissionHelper::can()` مباشرة، وفي الـ UI عبر `@canPermission`.

---

## 5. النطاقات (Scopes): مفتاح عدم تسريب البيانات

كل صلاحية قد تحصر صاحبها في:

- **مركز** معيّن (`center_id`)
- **مشروع** معيّن (`project_id`)
- **فوج** معيّن (`cohort_id`)
- أو **الكل** (ترك النطاقات الثلاثة فارغة = null)

### ما معنى `sees_all`؟

في `getEffectiveScope()`: إذا وُجد **سجل واحد على الأقل** أعلامه `can_view` والنطاقات الثلاثة فيه `null` → `sees_all = true` → المستخدم يرى كل شيء بلا قيود.

وإلا تُجمع كل `center_ids` و `project_ids` و `cohort_ids` من جميع السجلات.

### قاعدة ذهبية لم تُراعَ سابقاً (وهذا سبب الإصلاح)

> **نطاق الصلاحية يُطبَّق دائماً، ولا يجوز إيقافه أو تجاوزه بسبب فلتر يكتبه المستخدم في الرابط.**

النمط القديم الخاطئ كان:
```php
->unless($scope['sees_all'] || $request->filled('project_id'),
    fn($q) => $q->whereIn('project_id', $scope['project_ids']))
```
معناه: "إذا مرّر المستخدم `?project_id=999` → لا تُطبّق قيود الصلاحية إطلاقاً" = **تجاوز للصلاحية** (مدير مشروع يرى طلاب أي مشروع آخر بوضع id في الرابط).

النمط الصحيح (المطبَّق حالياً):
```php
->when($projectId, fn($q, $v) => $q->inProjects([(int) $v]))          // فلتر المستخدم
->when(!$scope['sees_all'] && !empty($scope['project_ids']),
    fn($q) => $q->inProjects($scope['project_ids']))                   // نطاق الصلاحية — دائماً
```
الفلتر اليدوي + نطاق الصلاحية يُطبَّقان معاً بـ **AND** → لا يظهر شيء خارج النطاق.

---

## 6. محرك الصلاحيات (PermissionHelper)

الملف: `app/Helpers/PermissionHelper.php`

### `can(User $user, string $model, string $action, ?int $modelId, ?int $centerId, ?int $projectId, ?int $cohortId): bool`

1. إذا `type === 'super-admin'` → `true`.
2. يُحوّل `action` إلى عمود (`can_view` / `can_create` / ...).
3. يجمع صلاحيات المستخدم (مباشرة + مجموعات) التي تحتوي `$model` في `model_names`.
4. لكل سجل: يتخطاه إذا خالف معرّف السجل أو المركز أو المشروع أو الفوج الممرَّرة (فقط عندما تكون المعرّفات الممرَّرة غير null).
5. يعيد `true` عند أول سجل يحقق الشرط.

### `getEffectiveScope(User $user, string $model): array`

```php
['center_ids' => [...], 'project_ids' => [...], 'cohort_ids' => [...], 'sees_all' => bool]
```

تستخدمها الـ Controllers لتصفية استعلامات `index()`.

### `canViewPage(User $user, string $routeName): bool`

اختصار لفحص صفحة مخصصة: `can($user, 'page:'.$routeName, 'view')`.

### `getUserPermissions(User $user, string $model): Collection`

يجمع: سجلات `user_id = user` + سجلات `group_id` لكل مجموعات المستخدم.

---

## 7. Middleware: تحقق المسارات

الملف: `app/Http/Middleware/CheckPermission.php` — مسجّل بالاسم `permission`.

```php
Route::get('students/attendance', [...])->middleware('permission:App\Models\Admin\Student\Attendance,view');
```

السلوك:
1. يقرأ المستخدم الحالي.
2. يبحث عن سجل `Employee` الخاص به ويستخرج `center_id / project_id / cohort_id`.
3. يستدعي `can($user, $model, $action, null, empCenter, empProject, empCohort)`.
4. إذا رفض → `403`.

> انتبه: إذا كان للمستخدم سجل موظف بنطاق **مختلف** عن نطاق الصلاحية، قد يُرفض الوصول رغم وجود الصلاحية. هذا سلوك مقصود لصالح الأمان، لكنه قد يسبب حيرة — تأكد دائماً من تطابق النطاقين عند منح الصلاحيات.

---

## 8. توجيه Blade: @canPermission

معرّف في `app/Providers/AppServiceProvider.php`:

```blade
@canPermission('App\Models\Admin\Center', 'view')
    {{-- يظهر فقط لمن لديه صلاحية عرض المراكز --}}
@endcanPermission
```

**تنبيه أمني:** هذا التوجيه لا يمرّر نطاق المركز/المشروع — غرضه إظهار/إخفاء عناصر الواجهة فقط، **وليست حماية أمنية**. الحماية الحقيقية تكون في الـ Middleware / الـ Controller.

---

## 9. شاشة إدارة الصلاحيات (UI)

- الروابط: `/admin/permissions` (قائمة)، `/admin/permissions/create` (إضافة).
- **النموذج (مصفوفة):**
  - "التعيين إلى": مستخدم أو مجموعة.
  - حدد المستخدم/المجموعة.
  - اختر من الموديلات (مجموعة بفئاتها مع "تحديد الكل").
  - حدد النطاق: المركز / المشروع / الفوج (اتركه فارغاً = الكل).
  - حدد الأعلام الأربعة.
- **قائمة العرض:** تُجمّع السجلات لكل (كيان × نطاق) مع badges للنطاق والأعلام.
- الحفظ يحذف أي سجل لم يُحدَّد في الشبكة ضمن نفس النطاق فقط (لا يمسّ نطاقات أخرى).

### خطوات منح صلاحية "مدير مشروع" (مثال عملي)

1. `/admin/permissions/create`.
2. التعيين إلى **مستخدم** → اختر يوسف.
3. الموديلات: الطلاب، الحضور، الدورات، الفترات، المهام، ... إلخ.
4. النطاق: **المشروع = معهد الرواد للعلوم التقنية** (المركز: الكل، الفوج: الكل).
5. الأعلام: عرض + إضافة + تعديل.

> **نصيحة:** عيّن النطاق من **المشروع** وليس المركز فقط، لأن ربط الطلاب بالمشاريع هو المعتمد فعلياً (جدول `project_student`).

---

## 10. المنطق الصحيح لتطبيق النطاق في الـ Controllers

### 10.1 خاصية `scopeInProjects` على نموذج Student

المشكلة التي اكتشفناها: الطالب يُربط بالمشروع بطريقتين:
1. عمود `students.project_id` (المشروع الأساسي).
2. جدول الوسيط `project_student` (العلاقة كثير-لكثير، **المعتمدة فعلياً**).

صفحة الطلاب كانت تستخدم الطريقتين، بينما صفحة الحضور استخدمت العمود فقط ⇒ الطلاب المرتبطون عبر الوسيط فقط لن يظهروا في الحضور.

الحل الموحّد (والمطلوب استخدامه في كل مكان):
```php
// في نموذج Student
public function scopeInProjects(Builder $query, array $projectIds): void
{
    $query->where(function (Builder $q) use ($projectIds) {
        $q->whereIn('project_id', $projectIds)
            ->orWhereHas('projects', fn(Builder $p) => $p->whereIn('projects.id', $projectIds));
    });
}
```

**لا تكتب أبداً فلتراً مباشراً على `students.project_id` في صفحات الطلاب** — استخدم `inProjects()`.

### 10.2 النمط المرجعي الكامل لصفحة قائمة

```php
$scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\...\Model');
$centerId = $request->filled('center_id') ? $request->input('center_id') : (count($scope['center_ids']) === 1 ? $scope['center_ids'][0] : '');
$projectId = $request->filled('project_id') ? $request->input('project_id') : (count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : '');

$rows = Model::query()
    ->when($centerId, fn($q, $v) => $q->where('center_id', $v))
    ->when($projectId, fn($q, $v) => $q->where('project_id', $v))
    // نطاق الصلاحية — يُطبَّق دائماً ولا يُتجاوز
    ->when(!$scope['sees_all'] && !empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
    ->when(!$scope['sees_all'] && !empty($scope['project_ids']), fn($q) => $q->whereIn('project_id', $scope['project_ids']))
    ->when(!$scope['sees_all'] && !empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
    ->paginate();
```

وللطلاب تحديداً (مع الوسيط):
```php
    ->when($projectId, fn($q, $v) => $q->inProjects([(int) $v]))
    ->when(!$scope['sees_all'] && !empty($scope['project_ids']), fn($q) => $q->inProjects($scope['project_ids']))
```

### 10.3 تصفية القوائم المنسدلة حسب النطاق

لا تُعرض كل المراكز/المشاريع لمدير محصور النطاق:

```php
$centers = Center::when(!$scope['sees_all'] && !empty($scope['center_ids']), fn($q) => $q->whereIn('id', $scope['center_ids']))->orderBy('name')->get();
$projects = Project::when(!$scope['sees_all'] && !empty($scope['project_ids']), fn($q) => $q->whereIn('id', $scope['project_ids']))->orderBy('name')->get();
```

### 10.4 حماية أفعال الكتابة (store/update) بالنطاق أيضاً

لا يكفي حماية العرض فقط — يجب ألا يتمكن المستخدم من إرسال طلب مزوّر يسجّل حضور/يعدّل لسجلات خارج نطاقه:

```php
$scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\...\Model');
if (!$scope['sees_all']) {
    $allowedIds = Student::query()
        ->when(!empty($scope['center_ids']), fn($q) => $q->whereIn('center_id', $scope['center_ids']))
        ->when(!empty($scope['project_ids']), fn($q) => $q->inProjects($scope['project_ids']))
        ->when(!empty($scope['cohort_ids']), fn($q) => $q->whereIn('cohort_id', $scope['cohort_ids']))
        ->pluck('id')->all();
    $submittedIds = collect($request->input('attendance'))->pluck('student_id')->map(fn($id) => (int) $id)->unique()->all();
    if (array_diff($submittedIds, $allowedIds)) {
        abort(403, 'ليس لديك صلاحية تسجيل الحضور لهؤلاء الطلاب');
    }
}
```

---

## 11. الأخطاء الشائعة والنمط المحظور (الدرس من الإصلاح)

| ❌ ممنوع | ✅ الصحيح |
|----------|-----------|
| `->unless($scope['sees_all'] \|\| $request->filled('project_id'), ...)` | `->when(!$scope['sees_all'] && !empty($scope['project_ids']), ...)` |
| `->where('project_id', $scope['project_ids'][0])` على جدول الطلاب | `->inProjects($scope['project_ids'])` |
| عدم فحص النطاق في `store()` | فحص `array_diff` للمعرّفات المرسلة (403) |
| ترك نطاق الصلاحية في التعليقات دون تطبيق فعلي على القوائم المنسدلة | تصفية `$centers`/`$projects`/`$cohorts` بالنطاق |

**الفحص المرجعي عند مراجعة أي صفحة:**
1. هل تُطبَّق النطاقات الثلاثة دائماً (بغض النظر عن الـ Request)؟
2. هل يُحترم رابط `project_student` (الوسيط) أم العمود فقط؟
3. هل تُحمى أفعال الكتابة بالنطاق؟
4. هل القوائم المنسدلة تعرض فقط ما يملكه المستخدم؟

---

## 12. الخطة خطوة بخطوة: إضافة وحدة/موديل جديد بصلاحيات

1. أنشئ الموديل والجدول والميغريشن.
2. أضف الموديل إلى `PermissionModelCatalog::groups()` (فئة مناسبة، ترتيب ثابت).
3. أضف الميغريشن لأعمدة النطاق الموجودة في جدول الموديل إن لزم (`center_id`, `project_id`, `cohort_id`).
4. إذا كانت السجلات تُربط بمشاريع عبر علاقة ManyToMany، أضف scope مشابهة لـ `scopeInProjects` واستخدمها.
5. في الـ Controller:
   - middleware لكل action: `permission:<Model>,view/create/edit/delete`.
   - `index()`: استخدم `getEffectiveScope` + النمط المرجعي في 10.2/10.3.
   - `store()/update()`: تحقق من النطاق (10.4).
6. في الواجهة: استخدم `@canPermission` لإظهار/إخفاء.
7. أضف اختبارات (انظر القسم 13).
8. حدّث هذا الدليل والملاحق إن لزم.

---

## 13. الاختبارات الآلية (حماية من الانتكاس)

الملف: `tests/Feature/StudentPermissionScopeTest.php` — يغطي:
- ظهور طلاب `project_student` فقط في سجل الحضور (العلّة الأصلية).
- منع تجاوز النطاق عبر `?project_id=` في الحضور والطلاب.
- رفض حفظ حضور لطلاب خارج النطاق (403).
- حماية نطاق المركز.
- سلامة `super-admin` (لا يتأثر).
- صحة `scopeInProjects`.

ملفات أخرى ذات صلة: `tests/Feature/PermissionTest.php` (حفظ المصفوفة والدمج والتوسيع)، `tests/Feature/SidebarPermissionTest.php` (القائمة الجانبية).

**التشغيل:**
```
vendor\bin\pest tests\Feature\StudentPermissionScopeTest.php
vendor\bin\pest tests\Feature\PermissionTest.php
```

**القاعدة:** أي تعديل على منطق الصلاحيات يجب أن يمر بكل هذه الاختبارات.

---

## 14. ملخص القرارات السريعة

- **من يرى ماذا؟** → استخدم `getEffectiveScope` مع النمط المرجعي.
- **كيف أربط طالباً بمشروع؟** → عبر `student->projects()->attach($projectId)` (الوسيط) و/أو عمود `project_id` في جدول الطلاب، وعند الاستعلام استخدم `inProjects`.
- **أين أضيف موديلاً جديداً للصلاحيات؟** → `PermissionModelCatalog::groups()`.
- **كيف أحمي صفحة كاملة؟** → middleware `permission:<Model>,view` على الراوت.
- **كيف أحمي البيانات من فلترة الرابط؟** → لا تستخدم `unless(... $request->filled(...))` أبداً.
- **السجلات القديمة متعددة الموديلات؟** → شغّل `php artisan permissions:expand`.