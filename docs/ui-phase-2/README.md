# المرحلة 2 — الإدارة والموارد البشرية والطلاب

لقطات `before/` (main بعد دمج المرحلة 1) و`after/` (هذا الفرع)، بالخط الفعلي Tajawal، بأسماء `<الصفحة>-<العرض>-<المظهر>.jpg`
(desktop=1440، mobile=390). لقطات `after/` الإضافية: `student-form-error-*` (فشل التحقق مع صف تسجيل) و`permissions-form-desktop-dark.jpg`.

## إعادة التشغيل
1. بيئة تجريبية معزولة: `cp .env.example .env && php artisan key:generate && touch database/database.sqlite && php artisan migrate --force`
2. بيانات وحسابات: `php artisan db:seed --class=UiE2ESeeder --force` (موثقة في `database/seeders/UiE2ESeeder.php`؛ كلمة المرور `password`).
3. `npm run build && php artisan serve --port=8002` ثم `BASE=http://localhost:8002 OUT=docs/ui-phase-2/after node tests/e2e/ui-phase-2.mjs`
4. اختبارات PHP: `vendor/bin/pest tests/Feature/UiPhase2Test.php`

## قيود معروفة
- صفحتا `admin/students/statistics` (وأي استعلام `DATE_FORMAT`) تتطلبان MySQL؛ على sqlite المحلية تعطي 500 (قبل التعديل وبعده)، فلم أستطع فحصهما بصريًا.
- «المناصب الوظيفية والاحتياجات»: لا توجد وحدة/مسار للاحتياجات في المشروع، فطُبّق التوحيد على المناصب فقط.
- لم تُعدَّل: مصمم الشهادات، صفحات الطباعة/المعاينة/التحقق، وقوالب التايم شيت للطباعة.
