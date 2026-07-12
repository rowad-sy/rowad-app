# نظام الصلاحيات - دليل شامل

## 1. المفاهيم الأساسية

نظام الصلاحيات مبني على **جمع الصلاحيات** (Permission Aggregation):
- صلاحيات مباشرة للمستخدم
- صلاحيات من المجموعات التي ينتمي إليها المستخدم
- تُجمع كلها وتُرجَع الصلاحية الفعالة (إن وُجدت أي صلاحية تسمح بالوصول، يُسمح به)

نوعا المستخدمين:
- **super-admin**: يمر على جميع فحوصات الصلاحية (`PermissionHelper::can` ترجع `true` فوراً)
- **admin** و **user** وأي أنواع أخرى: تطبّق عليهم قيود الصلاحية كاملة

## 2. جدول `permissions`

| العمود | النوع | الوصف |
|--------|------|-------|
| `id` | bigint | المفتاح الرئيسي |
| `user_id` | bigint null | المستخدم المعين له الصلاحية |
| `group_id` | bigint null | المجموعة المعينة لها الصلاحية |
| `model_names` | json | أسماء الموديلات (`["App\\Models\\Admin\\Center", ...]`) |
| `model_id` | bigint null | معرّف عنصر معين (صلاحية على مستوى سجل) |
| `center_id` | bigint null | نطاق المركز (null = جميع المراكز) |
| `project_id` | bigint null | نطاق المشروع (null = جميع المشاريع) |
| `can_view` | boolean | صلاحية العرض |
| `can_create` | boolean | صلاحية الإضافة |
| `can_edit` | boolean | صلاحية التعديل |
| `can_delete` | boolean | صلاحية الحذف |

**ملاحظة**: `user_id` و `group_id` متنافيان — صلاحية واحدة تُعيّن إما لمستخدم أو لمجموعة.

## 3. تسجيل الموديلات (Model Registration)

تُسجّل الموديلات في **`PermissionController@modelGroups()`** (`app/Http/Controllers/Admin/PermissionController.php:137-183`).

مقسّمة إلى فئات (categories):

| الفئة | الموديلات |
|-------|-----------|
| الإدارة | Center, Project, Department, User, Group, Permission |
| الموارد البشرية | Employee, JobPosition, Warning, LeaveType, LeaveRequest, EmployeeAttendance |
| الطلاب | Student, Course, Period, StudentEnrollment, Attendance, Certificate, CertificateDesign |
| التقنية | TechIssue, TechEquipment |
| اللوجستي | PurchaseRequest, ApprovalRule, Warehouse, Asset, LogisticsSetting |
| إدارة المشاريع | ProjectTask |
| الصفحات | صفحات مخصصة (مثل `page:admin.logistics.statistics`) |

**لتسجيل موديل جديد**: أضف مدخلاً في `modelGroups()` داخل `PermissionController`.

## 4. `PermissionHelper` — محرك الصلاحيات

الملف: `app/Helpers/PermissionHelper.php`

### `can(User $user, string $modelName, string $action, ?int $modelId, ?int $centerId, ?int $projectId): bool`

**المنطق**:
1. إذا كان `$user->type === 'super-admin'` → `true`
2. يُحوّل `$action` إلى اسم عمود (`can_view`, `can_create`, `can_edit`, `can_delete`)
3. يجلب جميع صلاحيات المستخدم (مباشرة + مجموعات) التي تحتوي على `$modelName` في `model_names`
4. لكل صلاحية:
   - إذا `$modelId !== null` والصلاحية لها `model_id` مختلف → `continue`
   - إذا `$centerId !== null` والصلاحية لها `center_id` مختلف → `continue`
   - إذا `$projectId !== null` والصلاحية لها `project_id` مختلف → `continue`
   - إذا العمود المطلوب `true` → `true`
5. لا شيء يطابق → `false`

### `getUserPermissions(User $user, string $modelName): Collection`

**آلية الجمع**:
1. صلاحيات مباشرة: `Permission::where('user_id', $user->id)->whereJsonContains('model_names', $modelName)`
2. صلاحيات المجموعات: يحصل على `groups.id` للمستخدم، ثم `Permission::whereIn('group_id', $groupIds)->whereJsonContains(...)`
3. يدمج المجموعتين بـ `concat()`

### `canViewPage(User $user, string $routeName): bool`

للصفحات المخصصة — تستخدم `page:{routeName}` كـ `modelName`.

## 5. Middleware `CheckPermission`

الملف: `app/Http/Middleware/CheckPermission.php`

### الاستخدام في Routes
```
Route::get('/centers', ...)->middleware('permission:App\Models\Admin\Center,view');
```

### المنطق
1. يحصل على `$user` من الطلب
2. يبحث عن سجل `Employee` المرتبط بالمستخدم: `Employee::where('user_id', $user->id)->first()`
3. يستخرج `center_id` و `project_id` من الموظف
4. يستدعي `PermissionHelper::can($user, $modelName, $action, null, $centerId, $projectId)`
5. إذا رفضت → 403

**تأثير النطاق**: المستخدم العادي سيرى فقط البيانات المرتبطة بمركزه/مشروعه لأن الميدل وير يمرّر نطاقه من سجل الموظف.

## 6. `@canPermission` — Blade Directive

الملف: `app/Providers/AppServiceProvider.php:21-30`

التعريف:
```php
Blade::directive('canPermission', function (string $expression) {
    $params = explode(',', $expression);
    $model = trim($params[0] ?? "''");
    $action = trim($params[1] ?? "'view'");
    return "<?php if(auth()->check() && \App\Helpers\PermissionHelper::can(auth()->user(), {$model}, {$action})): ?>";
});
```

**الاستخدام**:
```blade
@canPermission('App\Models\Admin\Center', 'view')
    {{-- محتوى يظهر فقط لمن لديه صلاحية عرض المراكز --}}
@endcanPermission
```

**ملاحظة مهمة**: التوجيه لا يمرّر `centerId` أو `projectId`، لذا فحص النطاق لا يُطبّق في الـ Blade — الغرض منه إظهار/إخفاء عناصر الـ UI (مثل أزرار الإجراءات وعناصر القائمة) وليس كحماية أمنية. الأمن يُطبّق عبر Middleware أو استدعاء `PermissionHelper::can()` مباشرة.

## 7. شاشات الـ UI

### النموذج (`form.blade.php`)
- **تعيين إلى**: اختيار بين "مستخدم" أو "مجموعة" (radio buttons)
- **المستخدم/المجموعة**: قائمة منسدلة تظهر حسب الاختيار
- **الموديلات**: مقسّمة حسب الفئة مع إمكانية طي/توسيع و "تحديد الكل" لكل فئة
- **النطاق**: المركز والمشروع (اختياريان — null = جميع)
- **الصلاحيات**: 4 checkbox: عرض، إضافة، تعديل، حذف

### قائمة الصلاحيات (`index.blade.php`)
- جدول يحتوي: المستخدم/المجموعة، الموديل، النطاق (badge), الصلاحيات (badges), الإجراءات
- مع search و pagination

### في master layout
الشريط الجانبي (sidebar) يستخدم `@canPermission` لإظهار/إخفاء روابط القائمة حسب صلاحية المستخدم.

## 8. أمثلة شاملة

### إنشاء صلاحية لموظف في مركز معين
1. اذهب إلى `/admin/permissions/create`
2. اختر "مستخدم" → حدد المستخدم
3. اختر الموديلات: `App\Models\Admin\Hr\Employee`
4. في "المركز": اختر "مركز الرياض"
5. في "المشروع": اتركه "جميع المشاريع"
6. حدد: عرض، إضافة، تعديل

النتيجة: المستخدم يستطيع إدارة موظفي مركز الرياض فقط.

### إنشاء صلاحية مجموعة للمشاريع
1. اختر "مجموعة" → حدد "مديري المشاريع"
2. اختر الموديلات: `App\Models\Admin\Project`, `App\Models\Admin\ProjectTask`, `App\Models\Admin\Student\Course`
3. اترك النطاق فارغاً (جميع المراكز والمشاريع)
4. حدد: عرض، إضافة، تعديل، حذف

النتيجة: جميع أعضاء المجموعة لديهم صلاحية كاملة على المشاريع والمهام والدورات.

### استخدام الصلاحية في Controller
```php
public function show(Student $student)
{
    // التحقق الأمني
    if (!\App\Helpers\PermissionHelper::can(auth()->user(), 'App\Models\Admin\Student\Student', 'view')) {
        abort(403);
    }
    // ...
}
```

### استخدام Middleware في routes
```php
Route::resource('employees', EmployeeController::class)
    ->middleware('permission:App\Models\Admin\Hr\Employee,view');
```

## 9. التوسيع (إضافة نطاق أو صلاحية جديدة)

### إضافة نطاق جديد (مثل `department_id`)
1. أضف عمود `department_id` إلى جدول `permissions` عبر ميجريشن جديد
2. أضف `department_id` إلى `$fillable` في `Permission` model
3. أضف العلاقة `department(): BelongsTo` في `Permission` model
4. أضف حقل `department_id` في `form.blade.php`
5. أضف التحقق من `department_id` في `PermissionHelper::can()`
6. أضف `department_id` إلى `$validated` في `store()`/`update()` في `PermissionController`
7. أضف `department_id` إلى `create()`/`edit()` في `PermissionController` (لتمرير قائمة الإدارات)

### إضافة نوع صلاحية جديد (مثل `can_export`)
1. أضف عمود `can_export` إلى جدول `permissions` عبر ميجريشن
2. أضف `can_export` إلى `$fillable` و `casts()` في `Permission` model
3. أضف checkbox في `form.blade.php`
4. أضف `can_export` إلى `$validated` في `store()`/`update()`
5. استخدمه في `PermissionHelper::can($user, $model, 'export')`

## 10. سيناريوهات وحالات اختبارية

| السيناريو | التوقع |
|-----------|--------|
| مستخدم عادي بدون أي صلاحيات يحاول فتح /admin | 403 |
| مدخل بيانات لديه `can_create` على Student في مركزه فقط | يرى زر "إضافة طالب" ولا يرى طلاب من مراكز أخرى |
| مشرف HR لديه `can_view` على Employee عبر مجموعة | يرى قائمة الموظفين (صلاحية مفعّلة من المجموعة) |
| super-admin على أي صفحة | يرى كل شيء دون قيود |
| صلاحية بمركز محدد + مشروع محدد | المستخدم يرى فقط بيانات هذا المركز/المشروع |
