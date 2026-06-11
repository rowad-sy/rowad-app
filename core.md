# نظام مؤسسة الرواد - النظام الأساسي (Core)

## هيكل المشروع

```
rowad-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                    # كونترولرات لوحة الإدارة
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── CenterController.php  # إدارة المراكز
│   │   │   │   ├── ProjectController.php # إدارة المشاريع
│   │   │   │   ├── GroupController.php   # إدارة المجموعات
│   │   │   │   ├── PermissionController.php # إدارة الصلاحيات
│   │   │   │   └── UserController.php    # إدارة المستخدمين
│   │   │   └── Auth/                     # كونترولرات المصادقة
│   │   └── Middleware/
│   │       └── CheckPermission.php       # ميدل وير الصلاحيات
│   ├── Models/
│   │   ├── Admin/                        # موديلز الإدارة
│   │   │   ├── Center.php
│   │   │   ├── Project.php
│   │   │   ├── Group.php
│   │   │   └── Permission.php
│   │   └── User.php                      # موديل المستخدم الأساسي
│   └── Helpers/
│       └── PermissionHelper.php          # مساعد الصلاحيات
├── database/
│   └── migrations/                       # ملفات الترحيل
│       ├── 0001_01_01_000000_create_users_table.php
│       ├── 2026_06_09_000000_add_is_active_to_users_table.php
│       ├── 2026_06_09_000001_create_centers_table.php
│       ├── 2026_06_09_000002_create_projects_table.php
│       ├── 2026_06_09_000003_create_center_project_table.php
│       ├── 2026_06_09_000004_create_groups_table.php
│       ├── 2026_06_09_000005_create_group_user_table.php
│       └── 2026_06_09_000006_create_permissions_table.php
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── layouts/
│       │   │   └── master.blade.php      # القالب الرئيسي للإدارة
│       │   ├── dashboard/
│       │   │   └── index.blade.php       # لوحة التحكم
│       │   ├── centers/
│       │   │   ├── index.blade.php       # قائمة المراكز
│       │   │   └── form.blade.php        # إضافة/تعديل مركز
│       │   ├── projects/
│       │   │   ├── index.blade.php       # قائمة المشاريع
│       │   │   └── form.blade.php        # إضافة/تعديل مشروع
│       │   ├── groups/
│       │   │   ├── index.blade.php       # قائمة المجموعات
│       │   │   └── form.blade.php        # إضافة/تعديل مجموعة
│       │   ├── permissions/
│       │   │   ├── index.blade.php       # قائمة الصلاحيات
│       │   │   └── form.blade.php        # إضافة/تعديل صلاحية
│       │   └── users/
│       │       └── index.blade.php       # قائمة المستخدمين
├── routes/
│   └── web.php                           # المسارات الرئيسية
└── core.md                               # هذا الملف
```

## قاعدة البيانات

### 1. `centers` - المراكز
| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint | معرّف فريد |
| name | string | اسم المركز |
| address | string | العنوان |
| phone | string | الهاتف |

### 2. `projects` - المشاريع
| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint | معرّف فريد |
| name | string | اسم المشروع |
| description | text | الوصف |

### 3. `center_project` (pivot)
| الحقل | النوع | الوصف |
|-------|------|-------|
| center_id | FK | معرّف المركز |
| project_id | FK | معرّف المشروع |

### 4. `groups` - المجموعات
| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint | معرّف فريد |
| name | string | اسم المجموعة |
| description | text | الوصف |

### 5. `group_user` (pivot)
| الحقل | النوع | الوصف |
|-------|------|-------|
| group_id | FK | معرّف المجموعة |
| user_id | FK | معرّف المستخدم |

### 6. `permissions` - الصلاحيات
| الحقل | النوع | الوصف |
|-------|------|-------|
| id | bigint | معرّف فريد |
| user_id | FK|null | مستخدم محدد |
| group_id | FK|null | مجموعة محددة |
| model_name | string | اسم الموديل |
| model_id | bigint|null | عنصر محدد |
| center_id | FK|null | نطاق المركز (null=الكل) |
| project_id | FK|null | نطاق المشروع (null=الكل) |
| can_view | bool | عرض |
| can_create | bool | إضافة |
| can_edit | bool | تعديل |
| can_delete | bool | حذف |

## المسارات

| المسار | الاسم | الوظيفة |
|-------|------|--------|
| `/admin` | admin.dashboard | لوحة التحكم |
| `/admin/centers` | admin.centers.* | إدارة المراكز (CRUD) |
| `/admin/projects` | admin.projects.* | إدارة المشاريع (CRUD) |
| `/admin/groups` | admin.groups.* | إدارة المجموعات (CRUD) |
| `/admin/permissions` | admin.permissions.* | إدارة الصلاحيات (CRUD) |
| `/admin/users` | admin.users.index | قائمة المستخدمين |
| `/admin/users/{id}/toggle-status` | admin.users.toggle-status | تفعيل/تعطيل مستخدم |

## الموديلز (Models)

### `App\Models\Admin\Center`
- `$table = 'centers'`
- `projects()` → BelongsToMany

### `App\Models\Admin\Project`
- `$table = 'projects'`
- `centers()` → BelongsToMany

### `App\Models\Admin\Group`
- `$table = 'groups'`
- `users()` → BelongsToMany
- `permissions()` → HasMany

### `App\Models\Admin\Permission`
- `$table = 'permissions'`
- `user()`, `group()`, `center()`, `project()` → BelongsTo

## حسابات التجربة

| الاسم | البريد | كلمة السر | المجموعة |
|------|-------|----------|---------|
| مدير النظام | admin@rowad.app | admin123 | مدير النظام |
| أحمد محمد | ahmed@rowad.app | password | مسؤول المراكز |
| سارة خالد | sara@rowad.app | password | مسؤول المشاريع |
| محمود علي | mahmoud@rowad.app | password | مشرف |
| نور حسن | noor@rowad.app | password | مسؤول الموارد البشرية |

## التقنيات المستخدمة
- **Laravel 12** - إطار العمل
- **Blade** - نظام القوالب (بدون Livewire)
- **Bootstrap 5.3** - واجهة المستخدم
- **Bootstrap Icons** - الأيقونات
- **SQLite** - قاعدة البيانات
- **Vite** - بناء الأصول

## كيفية التوسيع

### إضافة موديل جديد (مثل `Branch`):
1. أنشئ موديل في `app/Models/Admin/Branch.php`
2. أنشئ Controller في `app/Http/Controllers/Admin/BranchController.php`
3. أنشئ Views في `resources/views/admin/branches/`
4. أضف المسار في `routes/web.php` داخل مجموعة `admin`
5. أضف صلاحيات في السيدر

### إضافة نطاق صلاحية جديد:
1. أضف عموداً جديداً في جدول `permissions`
2. أضف العلاقة في `Permission.php`
3. أضف التحقق في `PermissionHelper.php`

## نظام الصلاحيات - كيفية عمله

1. **الميدل وير**: كل كونترولر يستخدم middleware للتحقق من الصلاحية قبل تنفيذ أي أكشن
2. **Blade Directive**: يستخدم `@canPermission('ModelClass', 'action')` في القوالب لإخفاء/إظهار العناصر
3. **عدم وجود صلاحية**: المستخدم الجديد بدون صلاحيات لا يرى أي روابط في القائمة الجانبية، وإذا حاول الوصول لأي صفحة يستقبل 403
