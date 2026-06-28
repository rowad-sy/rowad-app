# الوحدة اللوجستية - Logistics Module

## نظرة عامة

الوحدة اللوجستية مسؤولة عن إدارة المشتريات والمستودعات والأصول داخل المنظمة. تتكامل مع نظام الصلاحيات الموجود ونظام المستخدمين.

---

## هيكل الجداول (Database Schema)

### 1. logistics_settings
إعدادات عامة للوحدة (key-value store).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| key | string(255) | Unique |
| value | text | Nullable |
| timestamps | - | - |

### 2. logistics_approval_rules
قواعد الموافقة على طلبات الشراء (تعتمد على النطاق السعري).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| name | string(255) | اسم القاعدة |
| min_amount | decimal(12,2) | Default 0. الحد الأدنى للمبلغ |
| max_amount | decimal(12,2) | **Nullable.** الحد الأعلى (يُترك فارغاً لعدم وجود حد أعلى) |
| required_approvals | integer | Default 1. عدد الموافقات المطلوبة لتغيير حالة الطلب إلى "معتمد" |
| notes | text | Nullable |
| timestamps | - | - |

**Pivot: logistics_approval_rule_user**
| الحقل | النوع | ملاحظات |
|-------|------|---------|
| rule_id | FK | -> logistics_approval_rules(id) CASCADE |
| user_id | FK | -> users(id) CASCADE |

### 3. logistics_purchase_requests
طلبات الشراء.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| request_number | string(255) | Unique. التنسيق: `PR-YYYY-NNNNN` |
| user_id | FK | -> users(id). منشئ الطلب |
| specifications | text | **Nullable.** قديم (تم استبداله بالبنود) |
| quantity | integer | **Nullable.** قديم |
| unit | string(255) | **Nullable.** قديم |
| expected_unit_price | decimal(12,2) | **Nullable.** قديم |
| expected_total_price | decimal(12,2) | مجموع إجمالي البنود |
| center_id | FK | Nullable. -> centers(id) |
| project_id | FK | Nullable. -> projects(id) |
| status | string(255) | `pending` \| `approved` \| `rejected` \| `executed` |
| notes | text | Nullable |
| signature_path | string(255) | Nullable. مسار ملف التوقيع أو data URL |
| softDeletes | timestamp | deleted_at |

### 4. logistics_purchase_request_items
بنود طلب الشراء (تمت إضافتها لاحقاً لدعم بنود متعددة).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| purchase_request_id | FK | -> logistics_purchase_requests(id) CASCADE |
| description | text | وصف البند |
| quantity | integer | الكمية |
| unit | string(255) | الوحدة (قطعة، كرتون، كغم، ...) |
| unit_price | decimal(12,2) | سعر الوحدة |
| total_price | decimal(12,2) | المجموع = quantity * unit_price |
| notes | text | Nullable. ملاحظات البند |
| timestamps | - | - |

### 5. logistics_purchase_request_approvals
سجل الموافقات على طلبات الشراء.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| purchase_request_id | FK | -> logistics_purchase_requests(id) CASCADE |
| user_id | FK | -> users(id). المُعتمِد |
| status | string(255) | `pending` \| `approved` \| `rejected` |
| notes | text | Nullable |
| decided_at | timestamp | Nullable. تاريخ البتّ |
| timestamps | - | - |

### 6. logistics_warehouses
المستودعات.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| name | string(255) | اسم المستودع |
| center_id | FK | -> centers(id) |
| notes | text | Nullable |
| softDeletes | timestamp | deleted_at |

### 7. logistics_warehouse_items
محتويات المستودعات (تُحذف نهائياً مع النقل إلى deleted_items).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| warehouse_id | FK | -> logistics_warehouses(id) CASCADE |
| name | string(255) | اسم الصنف |
| description | text | Nullable |
| quantity | integer | Default 0 |
| unit | string(255) | الوحدة |
| status | string(255) | Default `active` |
| timestamps | - | - |

### 8. logistics_deleted_items
سجل محذوفات المستودعات (لأغراض التدقيق - لا حذف نهائي).

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| warehouse_id | FK | Nullable. -> logistics_warehouses(id) SET NULL |
| item_name | string(255) | اسم الصنف |
| description | text | Nullable |
| quantity | integer | الكمية |
| unit | string(255) | الوحدة |
| delete_reason | string(255) | سبب الحذف (مطلوب) |
| timestamps | - | - |

### 9. logistics_assets
الأصول.

| الحقل | النوع | ملاحظات |
|-------|------|---------|
| id | bigint | PK |
| asset_code | string(255) | Unique. كود الأصل |
| name | string(255) | اسم الأصل |
| type | string(255) | النوع (أثاث, أجهزة, أخرى) |
| center_id | FK | -> centers(id) |
| project_id | FK | Nullable. -> projects(id) |
| room_number | string(255) | Nullable. رقم/اسم الغرفة |
| status | string(255) | جديد \| قيد الاستخدام \| صيانة \| مستبعد |
| notes | text | Nullable |
| recipient_id | FK | Nullable. -> users(id) SET NULL. المستلم |
| softDeletes | timestamp | deleted_at |

---

## الموديلات (Models)

### PurchaseRequest
- **Accessor:** `total_price` = مجموع `items()->sum('total_price')`
- **Accessor:** `formatted_total` = `number_format($this->total_price, 2)`

### ApprovalRule
- `max_amount` **nullable** - إذا كان null، القاعدة تنطبق على أي مبلغ فوق `min_amount`

### PurchaseRequestItem
- `total_price` = `quantity * unit_price` (يُحسب تلقائياً في الواجهة ويُحفظ في DB)

### DeletedItem
- لا SoftDeletes - هذا جدول أرشفة مخصص

---

## المسارات (Routes)

البادئة: `/admin/logistics` - الاسم: `admin.logistics.*`

### الإحصائيات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /statistics | `LogisticsStatisticsController@index` |

### الإعدادات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /settings | `SettingsController@index` |
| POST | /settings | `SettingsController@update` |

### قواعد الموافقات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /approval-rules | `index` |
| GET | /approval-rules/create | `create` |
| POST | /approval-rules | `store` |
| GET | /approval-rules/{id}/edit | `edit` |
| PUT | /approval-rules/{id} | `update` |
| DELETE | /approval-rules/{id} | `destroy` |

### طلبات الشراء
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /purchase-requests | `index` |
| GET | /purchase-requests/create | `create` |
| POST | /purchase-requests | `store` |
| GET | /purchase-requests/{id} | `show` |
| DELETE | /purchase-requests/{id} | `destroy` |
| POST | /purchase-requests/{id}/approve | `PurchaseRequestApprovalController@approve` |
| POST | /purchase-requests/{id}/reject | `PurchaseRequestApprovalController@reject` |

### المستودعات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /warehouses | `index` |
| GET | /warehouses/create | `create` |
| POST | /warehouses | `store` |
| GET | /warehouses/{id}/edit | `edit` |
| PUT | /warehouses/{id} | `update` |
| DELETE | /warehouses/{id} | `destroy` |

### أصناف المستودعات
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /warehouses/{id}/items | `index` |
| GET | /warehouses/{id}/items/create | `create` |
| POST | /warehouses/{id}/items | `store` |
| GET | /warehouses/{id}/items/{itemId}/edit | `edit` |
| PUT | /warehouses/{id}/items/{itemId} | `update` |
| POST | /warehouses/{id}/items/{itemId}/delete | `destroy` (ينقل إلى deleted_items) |
| GET | /deleted-items | `deleted` (الأصناف المحذوفة) |

### الأصول
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /assets | `index` |
| GET | /assets/create | `create` |
| POST | /assets | `store` |
| GET | /assets/{id} | `show` |
| GET | /assets/{id}/edit | `edit` |
| PUT | /assets/{id} | `update` |
| DELETE | /assets/{id} | `destroy` |

### استيراد/تصدير Excel
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | /export/purchase-requests | تصدير طلبات الشراء |
| POST | /import/purchase-requests | استيراد طلبات الشراء |
| GET | /export/warehouses | تصدير المستودعات |
| POST | /import/warehouses | استيراد المستودعات |
| GET | /export/assets | تصدير الأصول |
| POST | /import/assets | استيراد الأصول |

---

## سير عمل الموافقات (Approval Workflow)

1. يتم إنشاء طلب شراء مع بنود متعددة
2. يتم حساب `expected_total_price` = مجموع `(quantity * unit_price)` لكل البنود
3. النظام يبحث عن قاعدة موافقة (`ApprovalRule`) تطابق المبلغ:
   - `min_amount <= total <= max_amount` (أو `max_amount IS NULL` للمبالغ غير المحدودة)
4. إذا وُجدت قاعدة، يتم إنشاء سجل موافقة (`PurchaseRequestApproval`) لكل معتمَد في القاعدة، الحالة `pending`
5. يمكن لكل معتمِد الموافقة أو الرفض
6. **عند الموافقة:** بعد كل موافقة، النظام يحسب عدد الموافقات `approved`. إذا وصل العدد إلى `required_approvals` في القاعدة، تتغير حالة الطلب إلى `approved`
7. **عند الرفض:** تتغير حالة الطلب فوراً إلى `rejected`

---

## الصلاحيات (Permissions)

جميع موديلات اللوجست تستخدم نظام الصلاحيات الموجود (`Permission` model + `permission` middleware).

الموديلات المسجلة في `PermissionController@modelGroups()`:
- `App\Models\Admin\Logistics\LogisticsSetting`
- `App\Models\Admin\Logistics\ApprovalRule`
- `App\Models\Admin\Logistics\PurchaseRequest`
- `App\Models\Admin\Logistics\Warehouse`
- `App\Models\Admin\Logistics\WarehouseItem`
- `App\Models\Admin\Logistics\Asset`
- `page:admin.logistics.statistics` (صلاحية صفحة الإحصائيات)

الأفعال المتاحة لكل موديل: `view`, `create`, `edit`, `delete`.

مستخدم `super-admin` لديه جميع الصلاحيات تلقائياً (لا يحتاج تعيين صلاحيات).

---

## التعديلات الرئيسية التي تمت

### 2026-06-28: دعم بنود متعددة في طلب الشراء

- **المشكلة:** كان طلب الشراء يدعم بنداً واحداً فقط (حقول `specifications`, `quantity`, `unit`, `expected_unit_price`).
- **الحل:** تم إنشاء جدول `logistics_purchase_request_items` وموديل `PurchaseRequestItem` لنقل البيانات إلى بنود متعددة.
- **التغييرات:**
  - `database/migrations/logistics/2026_06_28_000006_create_logistics_purchase_request_items_table.php`: جدول جديد + تعديل الحقول القديمة لتكون nullable
  - `app/Models/Admin/Logistics/PurchaseRequestItem.php` (جديد)
  - `app/Models/Admin/Logistics/PurchaseRequest.php`: إضافة `items()` hasMany + `getTotalPriceAttribute()` accessor
  - `app/Http/Controllers/Admin/Logistics/PurchaseRequestController.php`: الـ `store()` يقبل `items` array وينشئ البنود
  - `resources/views/admin/logistics/purchase-requests/form.blade.php`: فورم ديناميكي مع JS (إضافة/حذف بنود، حساب تلقائي)
  - `resources/views/admin/logistics/purchase-requests/show.blade.php`: عرض البنود في جدول
  - `resources/views/admin/logistics/purchase-requests/index.blade.php`: عرض أول بند وعدد البنود

### 2026-06-28: إكمال سير الموافقات التلقائي

- **المشكلة:** عند الموافقة، كان يتم تحديث سجل الموافقة فقط دون تغيير حالة الطلب.
- **الحل:** تمت إضافة منطق في `PurchaseRequestApprovalController@approve` يحسب عدد الموافقات ويقارنها بـ `required_approvals` من قاعدة الموافقة المطابقة. إذا اكتمل العدد، تتغير حالة الطلب إلى `approved`.
- **التغييرات:**
  - `app/Http/Controllers/Admin/Logistics/PurchaseRequestApprovalController.php`: إضافة منطق حساب الموافقات

### 2026-06-28: جعل max_amount اختيارياً في قواعد الموافقات

- **المشكلة:** كان `max_amount` إلزامياً مما يمنع إنشاء قواعد مثل "أي طلب فوق 20$".
- **الحل:** تم تغيير validation rule من `required` إلى `nullable`.
- **التغييرات:**
  - `app/Http/Controllers/Admin/Logistics/ApprovalRuleController.php`: `max_amount` -> `nullable|numeric|min:0|gte:min_amount`
  - `resources/views/admin/logistics/approval-rules/form.blade.php`: تحديث الحقل وإضافة رسالة "اتركه فارغاً"

### 2026-06-28: استيراد/تصدير Excel

- تمت إضافة:
  - `app/Exports/Logistics/` (3 كلاسات)
  - `app/Imports/Logistics/` (3 كلاسات)
  - `app/Http/Controllers/Admin/Logistics/LogisticsExportController.php`
  - 6 مسارات (3 تصدير + 3 استيراد)
  - أزرار تصدير/استيراد في صفحات index

### 2026-06-28: إصلاح خطأ القالب المخفي في فورم البنود

- **المشكلة:** خطأ `An invalid form control with name='items[__INDEX__][description]' is not focusable` بسبب وجود قالب مخفي (`style="display:none"`) مع `required` في DOM.
- **الحل:** استخدام وسم `<template>` HTML بدلاً من `display:none`. عناصر `<template>` ليست جزءاً من DOM ولا يتم التحقق منها.

---

## مشاكل معروفة

1. **View path mismatch في WarehouseItemController:** المتحكم يستخدم `return view('admin.logistics.warehouse-items.*')` ولكن المجلد الفعلي للـ views هو `warehouses/` وليس `warehouse-items/`. الملفات موجودة كـ:
   - `resources/views/admin/logistics/warehouses/items.blade.php`
   - `resources/views/admin/logistics/warehouses/item_form.blade.php`
   لكن المتحكم يشير إلى `admin.logistics.warehouse-items.index` و `admin.logistics.warehouse-items.form`. يحتاج إصلاح إما في أسماء الملفات أو في المتحكم.

---

## إرشادات للمطورين

- بعد تعديل أي Blade view، قم بتشغيل `php artisan view:clear`
- التوقيع الإلكتروني يدعم طريقتين: رفع صورة أو رسم بالماوس (يُحفظ كـ data URL في قاعدة البيانات)
- عند حذف صنف من المستودع، يجب تقديم سبب (delete_reason)، ويتم نسخ البيانات إلى `logistics_deleted_items` قبل الحذف
- جميع الموديلات تدعم SoftDeletes (ما عدا `PurchaseRequestItem`, `PurchaseRequestApproval`, `DeletedItem`, `LogisticsSetting`)
- التنسيق الآلي (auto-formatting) لملفات PHP يتم عبر Laravel Pint أو أي formatter متفق عليه
