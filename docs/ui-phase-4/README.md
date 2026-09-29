# المرحلة 4 (الأخيرة) — إغلاق النواقص والتحقق من الحفاظ على الوظائف

الأساس: `main` بعد دمج PR #4 وPR #5 (تحقّقتُ أن التقاط 7436ac6 و99572ce موجودان في تاريخ `main` كما هما). التقرير الكامل: [`final-verification.md`](final-verification.md).

## ما نُفِّذ (بلا migrations وبلا تغيير في المخطط أو الاتصال)
**إصلاحات وظيفية محدودة (commit مستقل `6fc7e34`):**
- `logistics/assets/{id}`: المسار موجود ولا يوجد `AssetController::show` (500). أُضيف عرض قراءة فقط لحقول الأصل نفسها بصلاحية العرض القائمة، ورابط من اسم الأصل في القائمة. لا دورة عمل جديدة.
- إحصائيات التقنية: العمود `condition` كلمة محجوزة في MySQL/MariaDB (500 هناك ولا يظهر على sqlite) — وُضع بين backticks (الاستعلام نفسه دلاليًا).
- إحصائيات المهام: تسميات الأشهر كانت تستدعي `now()->month($m)` فتُغيّر التاريخ وتنزاح الأشهر عند الأيام 29–31؛ صارت `Carbon::create(2000, $m, 1)`.

**واجهات (commit `e25ab52`):** الملف الشخصي وتغيير كلمة المرور الإلزامي والمعرّفات ولوحة المستفيد وسجل التدقيق (فلاتر مُسمّاة بزر «تطبيق» ومسح) على المكونات المشتركة؛ قالب أخطاء موحّد (403/404/419/500/503) بخط Tajawal المحلي مع «العودة» و«الذهاب للتطبيقات» و**تسجيل الخروج** للمصادَق عليه فقط؛ إزالة روابط الخطوط الخارجية (bunny.net) من قوالب الدخول والإعدادات؛ بطاقات إحصاء الطلاب على `x-kpi`.

**جولة تصحيح PR #6 (صلاحيات):** نطاق السجل والإذن معًا في التقنية (تذاكر/معدات، بما فيها `respond`) وتفاصيل/قائمة/كتابة الأصول عبر `App\Support\RecordAccess`؛ التفاصيل والأدلة والقرارات غير المحسومة في `final-verification.md` القسم 7. لا migrations ولا تغيير مخطط/بيانات/صلاحيات مخزّنة. اختبارات: `tests/Feature/RecordScopeAuthorizationTest.php`، ومتصفح: `ONLY=6`.

## إعادة التشغيل على بيئة معزولة
1. **sqlite (افتراضي الاختبارات):** `cp .env.example .env && php artisan key:generate && npm ci && npm run build && vendor/bin/pest` — 3 اختبارات MySQL-only تُتخطّى تلقائيًا.
2. **MySQL/MariaDB تجريبي محلي:** أنشئ خادمًا محليًا مؤقتًا (مثال: `mariadb-install-db --no-defaults --datadir=/tmp/x/data` ثم `mariadbd --no-defaults --datadir=/tmp/x/data --socket=/tmp/x/m.sock --port=3307 --bind-address=127.0.0.1`) وقاعدة اسمها ينتهي بـ `_test`، ثم صدّر متغيرات البيئة **في الصدفة فقط** (لا تعدّل `.env`):
   `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 DB_DATABASE=rowad_phase4_test DB_USERNAME=... DB_PASSWORD=...` ثم `vendor/bin/pest`.
   `MysqlOnlyStatisticsTest` يرفض العمل إن لم يكن المضيف محليًا واسم القاعدة `*_test`. **تنبيه:** `RefreshDatabase` يعيد بناء القاعدة المتصلة؛ لا تشغّله مع أي اتصال غير معزول.
3. **متصفح:** `php artisan migrate:fresh --force && php artisan db:seed --class=UiE2ESeeder --force && php artisan db:seed --class=UiE2EPhase3Seeder --force` (على قاعدة معزولة فقط) ثم `php artisan serve --port=8002` و`BASE=http://localhost:8002 OUT=docs/ui-phase-4/after node tests/e2e/ui-phase-4.mjs` (`ONLY=1,2,3,4,5` لاختيار الأقسام).
   على sqlite تعطي `projects/statistics` و`students/statistics` 500 (دوال MySQL) — متوقع؛ استخدم الخطوة 2 لها.

## المتطلبات التشغيلية بعد الدمج (لا تُنفَّذ ضمن هذه المهمة)
- بناء الأصول: `npm ci && npm run build` (`public/build` غير مُتتبَّع). الخطوط في `public/fonts/tajawal` مُتتبَّعة.
- `php artisan view:clear` (وإن كانت المسارات مخزّنة مؤقتًا: `php artisan route:clear` أو إعادة `route:cache` لأن مسار حذف مواد المخزن ووسيط `project-activities` تغيّرا في المرحلة 3).
- **لا** migrations، **لا** seeders، **لا** تغيير في `.env` أو الاتصال. ملفات `UiE2E*Seeder` للاختبار فقط ولا يستدعيها التطبيق ولا `DatabaseSeeder`.
