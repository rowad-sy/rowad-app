# المرحلة 1 — الأساس البصري والقالب المشترك

لقطات المقارنة: `before/` (main) و`after/` (هذا الفرع). الأسماء: `<الصفحة>-<العرض>-<المظهر>.jpg`
(desktop=1440، tablet=768، mobile=390).

## الخط
- صور `after/` الحالية أُخذت بخط **Tajawal الفعلي**، وتحقق منه الاختبار (`document.fonts` بحالة loaded وطلبات ملفات الخط 200).
  بيئة الفحص تحجب `fonts.bunny.net`، لذلك استُضيف الخط محليًا في `public/fonts/tajawal/` (Fontsource 5.3.0، ترخيص SIL OFL 1.1 — `OFL-LICENSE.txt`)
  وعُرّف في `resources/css/app.css` (`@font-face`) وأُزيل رابط bunny من القالب الرئيسي والبوابة.
- صور `before/` أُخذت قبل هذا التحديث بخط بديل (الكود القديم يحمّل الخط من bunny المحجوب)؛ فالمقارنة الدقيقة بين قبل وبعد تخص التخطيط لا الخط.

## التحديث الأخير
- `after/sidebar-mobile-open.jpg`, `after/sidebar-rail-desktop.jpg`: أعيدت بالخط الفعلي.
- `after/projects-manager-sign-only-*.jpg`, `after/projects-manager-no-actions-*.jpg`: لوحة مدير المشاريع بطلبات توقيع فقط، وبدون إجراءات.
- `after/student-form-*.jpg` و`after/student-form-error-*.jpg`: نموذج الطالب (390/768/1440، فاتح وداكن) قبل وبعد فشل التحقق.

## التشغيل
- اختبارات PHP: `vendor/bin/pest tests/Feature/UiPhase1Test.php`
- اختبار المتصفح (تركيز القائمة، شريط الأيقونات، لوحة مدير المشاريع، نموذج الطالب): `tests/e2e/ui-phase-1.mjs` (الرأس يشرح الإعداد).
