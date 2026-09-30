# تغييرات وظيفية أُخرجت من PR #6 (للتسليم لمسؤول النظام — لا تُتابَع هنا)

المصدر: الفرع الأرشيفي `archive/ui-phase-4-functional-9cde4c1` (`9cde4c1ae202df83737b3b1470022c284764bdad`). لم تُدمج ولم تُنشر.

| البند | الملفات في الأرشيف | الأثر | ملاحظة |
|---|---|---|---|
| `App\Support\RecordAccess` (فحص الإذن+نطاق السجل، `scopeQuery`, `hasAny`) وتحويل `PermissionHelper::getUserPermissions` إلى public | `app/Support/RecordAccess.php`, `app/Helpers/PermissionHelper.php` | سياسة وصول جديدة | تغيير أمني/وظيفي |
| تقييد التذاكر والمعدات (show/edit/update/destroy/store، الوجهة، القوائم) | `Tech{Issue,Equipment}Controller.php`, قوالب `tech/*` | يمنع وصول مستخدم مقيّد بمركز إلى سجلات مركز آخر | كان سلوك `main`: التفاصيل والتعديل بلا نطاق سجل |
| `respond` بصلاحية `edit` | `TechIssueController.php` | يمنع الرد لمن لا يملك تعديلًا (كان مفتوحًا لأي مسجَّل دخول) | تغيير أمني مقصود؛ قرار الإسناد عبر المراكز غير محسوم |
| `AssetController::show` + رابط من القائمة + نطاق الأصول | `AssetController.php`, `assets/show.blade.php`, `assets/index.blade.php` | يضيف صفحة تفاصيل (كان المسار 500) ويقيّد القائمة | وظيفة جديدة |
| تصدير/استيراد الأصول (بوابات، نطاق، معاملة، رسائل) | `AssetExport.php`, `AssetImport.php`, `AssetImportRejected.php`, `LogisticsExportController.php` | كان التصدير يعطي 500 (علاقتان غير موجودتان) والاستيراد لا ينجح لأي مستخدم | عيب قائم في `main` |
| اقتباس `condition` في إحصاءات التقنية | `TechStatisticsController.php` | يمنع 500 على MySQL/MariaDB | إصلاح استعلام |
| اختبارات هذه البنود | `RecordScopeAuthorizationTest`, `ScopedListsAndAssetTransferTest`, جزء التقنية من `MysqlOnlyStatisticsTest`, أقسام 6–7 من `ui-phase-4.mjs` | — | تُحفظ مع الأرشيف |

عيوب قائمة في `main` لم تُعالَج هنا: استيراد الطلاب («ذكر» مقابل enum على MySQL الصارم)، اختبار تصدير الطلاب بمعرّفات ثابتة، `logistics/assets/{id}` (لا `show`)، `projects/statistics` و`students/statistics` تحتاجان MySQL، `tech/statistics` يعطي 500 على MySQL (`condition`).
