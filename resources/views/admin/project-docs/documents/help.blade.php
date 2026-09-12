@extends('admin.layouts.master')

@section('title', 'معلومات ونصائح — وثائق المشروع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-question-circle ms-1"></i> معلومات ونصائح — وثائق المشروع</h4>
        <p>دليل شامل لكيفية عمل الوثائق (ملاحق المشروع): القوالب، التعبئة، سير العمل، والوثائق المشتركة بين مستخدمين.</p>
    </div>
    <a href="{{ route('admin.project-docs.documents.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right me-1"></i> العودة للوثائق
    </a>
</div>

<div class="row g-3">

    <div class="col-lg-8">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-folder2-open me-1"></i> ما هي وثائق المشروع؟</h5></div>
            <div class="p-3">
                <p class="mb-2">وثائق المشروع هي <strong>الملاحق الرسمية</strong> التي ترفق بملف كل مشروع (إدارة المشاريع):</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-card-text text-primary"></i> بطاقة المشروع</strong> — التعريف الثابت بالمشروع: الكود، المسار، المناطق، الميزانية، الغاية والأنشطة.</p>
                    <p class="mb-1"><strong><i class="bi bi-lightbulb text-primary"></i> فكرة المشروع</strong> — مقترح المشروع قبل اعتماده: الاحتياج، الحل، الفئة، الكلفة والمخاطر.</p>
                    <p class="mb-1"><strong><i class="bi bi-search text-primary"></i> الدراسة الأولية</strong> — دراسة مقارنة أو تفصيلية: السياق، تصميم التدخل، الجدوى، المخاطر والتوصية.</p>
                    <p class="mb-1"><strong><i class="bi bi-people text-primary"></i> استمارة تحديد معايير المستفيدين</strong> — قواعد اختيار وقبول وترتيب الأولوية للمستفيدين.</p>
                    <p class="mb-1"><strong><i class="bi bi-calendar3 text-primary"></i> التقرير الشهري</strong> — متابعة شهرية: أنجز، مؤشرات KPIs، مخاطر، امتثال، MEAL، وتقارير وملاحق.</p>
                    <p class="mb-0"><strong><i class="bi bi-clipboard-data text-primary"></i> تقرير نهاية المشروع</strong> — إغلاق رسمي: الإنجازات، النتائج، المالية، ثم إجراءات الإغلاق والملاحق.</p>
                </div>
                <p class="small text-muted mt-2 mb-0">هذه قوالب جاهزة مسبقاً. يمكنك أيضاً إنشاء قالب جديد من صفحة <strong>قوالب وثائق المشروع</strong> في القائمة الجانبية.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> القالب ≠ الوثيقة</h5></div>
            <div class="p-3">
                <p class="mb-2">نظام وثائق المشروع يفصل بين شيئين:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-bounding-box text-secondary"></i> القالب (Template)</strong> — <strong>شكل</strong> الوثيقة: تعريف أقسامها وترتيبها وأنواع حقولها. القوالب مشتركة بين كل المشاريع.</p>
                    <p class="mb-0"><strong><i class="bi bi-file-earmark-text text-secondary"></i> الوثيقة (Document)</strong> — <strong>نسخة معبأة</strong> من قالب لمشروع معيّن (أو مركز/فترة). الوثيقة هي التي تُحرر وتُعتمد وتُطبع.</p>
                </div>
                <p class="small text-muted mt-2 mb-0">أي تعديل على القالب يرفع إصداره <code>V{n+1}</code>، والوثائق القائمة تحتفظ بالإصدار الذي أُنشئت عليه (لقطة).</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-layout-text-window-reverse me-1"></i> أقسام الوثيقة وأنواع تعبئتها</h5></div>
            <div class="p-3">
                <p class="mb-2">كل قالب مكوّن من أقسام (Sections)، وكل قسم من أحد الأنواع التالية:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-justify text-primary"></i> فقرة (Paragraph)</strong> — نص حر طويل: خلفية، غاية، توصيات، قصص نجاح.</p>
                    <p class="mb-1"><strong><i class="bi bi-card-list text-primary"></i> حقول (Fields)</strong> — مجموعة أسئلة ذات إجابة قصيرة في بطاقة واحدة: اسم المشروع، الميزانية، تاريخ البدء.</p>
                    <p class="mb-1"><strong><i class="bi bi-table text-primary"></i> جدول (Table)</strong> — صفوف بعمود محدّد: مواقع التنفيذ، مؤشرات الأداء، الكلفة، المخاطر.</p>
                    <p class="mb-0"><strong><i class="bi bi-list-ul text-primary"></i> قائمة (List)</strong> — بنود متكررة بدون أعمد: الأنشطة، المخاطر الأولية، الوثائق المطلوبة.</p>
                </div>
                <p class="small text-muted mt-2 mb-0">في صفحة «تعبئة» الوثيقة يتكرر لكل قسم: حقل/جدول/قائمة حسب نوعه، مع زر <span class="badge bg-light text-dark border">قفل بعد الحفظ</span> لكل قسم.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i> دورة حياة الوثيقة (سير العمل)</h5></div>
            <div class="p-3">
                <ol class="small mb-3 ps-3">
                    <li class="mb-1"><strong>إنشاء:</strong> من زر «وثيقة جديدة» اختر القالب وحدد المشروع/المركز/الفترة (اختياري). تُنشأ الوثيقة كـ <span class="badge bg-secondary">مسودة</span> بأقسام فارغة.</li>
                    <li class="mb-1"><strong>تعبئة:</strong> من زر «تعبئة/تعديل» تُملأ الأقسام قسماً قسماً، ويظهر لكل قسم حالته (معبأ/ناقص). يمكن قفل أي قسم بعد حفظه.</li>
                    <li class="mb-1"><strong>إرسال للمراجعة:</strong> زر «إرسال للمراجعة» يقفل كل الأقسام تلقائياً — لا يمكن إرسال وثيقة فيها أقسام ناقصة.</li>
                    <li class="mb-1"><strong>مراجعة:</strong> الوثيقة تصبح <span class="badge bg-warning text-dark">قيد المراجعة</span>، ويستطيع من يملك صلاحية التحرير التعليق أو الاعتماد أو الرفض.</li>
                    <li class="mb-1"><strong>اعتماد / رفض:</strong> الاعتماد يثبّت الوثيقة نهائياً <span class="badge bg-success">معتمد</span>. الرفض يعيدها <span class="badge bg-danger">مرفوضة</span> مع تسجيل سببه.</li>
                    <li class="mb-1"><strong>إعادة فتح (عند الرفض فقط):</strong> يستطيع <strong>منشئ الوثيقة</strong> فتحها مجدداً كمسودة لتعديل أقسامها.</li>
                </ol>
                <div class="row text-center small g-1 mb-3">
                    <div class="col"><span class="badge bg-secondary w-100 py-2">مسودة</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-warning text-dark w-100 py-2">قيد المراجعة</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-success w-100 py-2">معتمد</span></div>
                </div>
                <div class="text-center small mb-0">
                    <a href="{{ route('admin.project-docs.documents.index') }}" class="text-decoration-none">من صفحة الوثائق يمكنك تصفية القائمة حسب الحالة</a>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-people-fill me-1"></i> الوثائق المشتركة بين مستخدمين (أهم قسم)</h5></div>
            <div class="p-3">
                <p class="mb-2">وثيقة المشروع الواحدة <strong>يعمل عليها أكثر من مستخدم</strong> بالتتابع: مدير المشروع يعبّئ، متخصص MEAL يعبّئ قسمه، ثم إدارة المشاريع تراجع وتقرّر. هذا هو جوهر نظام الوثائق — إليك كيف يعمل:</p>

                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="fw-bold mb-2"><i class="bi bi-person-badge text-primary"></i> 1) لكل قسم «مسؤول تعبئة» (يُعبأ بواسطة)</h6>
                    <p class="small mb-0">بعض أقسام القوالب تحمل وسماً أزرق <span class="badge bg-info text-dark">يُعبأ بواسطة: …</span> مثل «مدير المشروع» أو «مدير المتابعة والتقييم (MEAL)». هذا دليل على الجهة صاحبة الاختصاص — الوثيقة نفسها مفتوحة لأي مستخدم مخوّل، لكن الوسم يوضح من يفترض أن يعبّئ.</p>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="fw-bold mb-2"><i class="bi bi-lock text-primary"></i> 2) القفل يمنع الكتابة فوق عمل الآخرين</h6>
                    <p class="small mb-1">كل قسم له حالة قفل:
                        <ul class="small mb-0">
                            <li>أثناء المسودة يمكن قفل أي قسم بعلامة <strong>«قفل بعد الحفظ»</strong> بعد اكتماله (مثل قسم MEAL بعد أن يعبّئه مختصّه).</li>
                            <li>عند «إرسال للمراجعة» تُقفل <strong>كل الأقسام</strong> تلقائياً.</li>
                            <li>القسم المقفول لا يستطيع التعديل عليه سوى <strong>منشئ الوثيقة</strong> — لحمايته من تغيير على عمل زميل. يظهر عليه شارة <span class="badge bg-warning text-dark"><i class="bi bi-lock"></i> مقفول</span>.</li>
                        </ul>
                    </p>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="fw-bold mb-2"><i class="bi bi-person-lines-fill text-primary"></i> 3) من يمكنه ماذا؟</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered small align-middle mb-0">
                            <thead><tr><th>المستخدم</th><th>يستطيع</th></tr></thead>
                            <tbody>
                                <tr><td>أي مستخدم بصلاحية <code>عرض</code> للوثائق</td><td>عرض الوثيقة وسجلها وطباعتها</td></tr>
                                <tr><td>أي مستخدم بصلاحية <code>تعديل</code> (ضمن نطاقه)</td><td>تعبئة الأقسام <strong>غير المقفلة</strong> في المسودات، والتعليق/الاعتماد/الرفض أثناء المراجعة</td></tr>
                                <tr><td>منشئ الوثيقة</td><td>كل ما سبق + تعديل الأقسام المقفولة، إعادة فتح الوثيقة المرفوضة، وحذف المسودة</td></tr>
                                <tr><td>إدارة المشاريع / المراجع</td><td>المراجعة: تعليق، اعتماد نهائي، أو رفض مع تسجيل السبب</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 mt-2">صلاحيات الوثائق (عرض/إنشاء/تعديل/حذف) تُدار من صفحة الصلاحيات في النظام، ويمكن تقييدها على مركز/مشروع معيّن.</p>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="fw-bold mb-2"><i class="bi bi-clock-history text-primary"></i> 4) كل تعديل موثّق</h6>
                    <p class="small mb-1">لا يختفي عمل أحد:
                        <ul class="small mb-0">
                            <li>أسفل كل قسم يظهر <strong>اسم آخر من عدّله وتاريخه</strong>.</li>
                            <li>في صفحة العرض يوجد <strong>سجل الاعتمادات</strong> (من اعتمد/رفض/علّق ومتى) و<strong>الشريط الزمني</strong> بكل إجراء سُجل على الوثيقة (إنشاء، تحديث، إرسال، اعتماد، رفض...).</li>
                        </ul>
                    </p>
                </div>

                <div class="border rounded p-3 bg-light">
                    <h6 class="fw-bold mb-2"><i class="bi bi-diagram-2 text-primary"></i> 5) سيناريو واقعي</h6>
                    <ol class="small mb-0 ps-3">
                        <li class="mb-1">أنشأ <strong>مدير المشروع</strong> وثيقة «التقرير الشهري للمشروع» وعبّأ أقسام الملخص والأنشطة والمواقع (دون قفلها).</li>
                        <li class="mb-1">عبّأ <strong>مختص MEAL</strong> قسم MEAL (الوسم <span class="badge bg-info text-dark">يُعبأ بواسطة: مدير المتابعة والتقييم</span>) ثم فعّل <strong>«قفل بعد الحفظ»</strong> — حفاظاً على محتواه.</li>
                        <li class="mb-1">عدّل <strong>مدير المشروع</strong> ما تبقى، ثم أرسل الوثيقة للمراجعة (أقفلت الأقسام كلها).</li>
                        <li class="mb-1">أضاف <strong>مسؤول المشروع</strong> ملاحظة عبر «إضافة ملاحظة»، ثم <strong>إدارة المشاريع</strong> اعتمدت الوثيقة نهائياً، وسُجل كل ذلك في سجل الاعتمادات والشريط الزمني.</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-printer me-1"></i> الطباعة وحفظ PDF</h5></div>
            <div class="p-3">
                <ul class="small mb-0 ps-3">
                    <li class="mb-1">من صفحة عرض أي وثيقة اضغط <span class="badge bg-dark">طباعة A4</span> (يفتح نسخة الطباعة في تبويب جديد).</li>
                    <li class="mb-1">داخل صفحة الطباعة يوجد زر <span class="badge bg-primary">طباعة / حفظ PDF</span> — اختر «حفظ كـ PDF» من نافذة الطباعة.</li>
                    <li class="mb-1">النسخة المطبوعة تظهر بهوية مؤسسة الرواد: <strong>خلفية مائية كاملة لكل صفحة</strong>، عناوين برتقالية، وخط Tajawal — ولا تظهر أزرار الواجهة في الطباعة.</li>
                    <li class="mb-1">الوثيقة متعددة الصفحات: الخلفية تتكرر تلقائياً على كل صفحة، والهوامش تطبّق على المحتوى فقط.</li>
                    <li class="mb-0">الطباعة تُظهر <strong>أقسام الوثيقة وسجل الاعتمادات والتواقيع</strong> — المحتوى كما هو معبأ.</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> تعبئة وثيقة: خطوة بخطوة</h5></div>
            <div class="p-3 small">
                <ol class="mb-0 ps-3">
                    <li class="mb-1">الوثائق ← <strong>وثيقة جديدة</strong> ← اختر القالب والمشروع.</li>
                    <li class="mb-1">تظهر الوثيقة كمسودة بكل أقسامها <span class="badge bg-danger-subtle text-danger">ناقص</span>.</li>
                    <li class="mb-1">«تعبئة/تعديل» ← املأ كل قسم وحفظ.</li>
                    <li class="mb-1">عند اكتمال كل الأقسام «إرسال للمراجعة».</li>
                    <li class="mb-0">الاعتماد النهائي من صفحة العرض.</li>
                </ol>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-x-circle me-1"></i> متى يحدث ماذا؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><span class="badge bg-danger">رفض</span> الوثيقة → تعود <span class="badge bg-secondary">مسودة</span> مقفولة، ولا يفتحها <strong>إلا منشئها</strong>.</li>
                    <li class="mb-1"><span class="badge bg-success">اعتماد</span> → الوثيقة ثابتة نهائياً؛ لا يُعدَّل عليها، وتظهر في الطباعة كمعتمدة.</li>
                    <li class="mb-1">الحذف متاح <strong>للمسودات فقط</strong> (للمنشئ أو للمدير التنفيذي).</li>
                </ul>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-shield-check me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">لا يُرسل للمراجعة وثيقة فيها <strong>قسم ناقص</strong> — النظام يمنع ذلك ويذكر الأقسام الناقصة.</li>
                    <li class="mb-1">قفل القسم لا يمنع عرضه أو طباعته — يمنع التعديل فقط.</li>
                    <li class="mb-1">منشئ الوثيقة يتجاوز القفل — استخدمه بحذر فهو المسؤول النهائي.</li>
                    <li class="mb-1">سجل الاعتمادات والشريط الزمني <strong>غير قابل للحذف</strong> — سجل تدقيق كامل.</li>
                    <li class="mb-1">النسخ: التقرير الشهري أُنشئ بالصيغة <code>YYYY-MM</code> للفترة — مثال <code>2026-06</code>.</li>
                    <li class="mb-0">نموذج تقرير نهاية المشروع يعتمد أدلة مرفقة (الملاحق والأدلة) — جهّزها قبل الإغلاق.</li>
                </ul>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-question-lg me-1"></i> من يستخدم الوثائق؟</h5></div>
            <div class="p-3 small">
                <p class="mb-1">الوثائق تخدم كل فريق المشروع:</p>
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>مدير المشروع:</strong> ينشئ ويعبّئ ويُرسل.</li>
                    <li class="mb-1"><strong>فريق التخصصات (MEAL، الإعلام، اللوجستيات):</strong> يعبّئون أقسامهم ثم يقفلونها.</li>
                    <li class="mb-1"><strong>إدارة المشاريع:</strong> تراجع وتعلّق وتعتمد أو ترفض.</li>
                    <li class="mb-1"><strong>الإدارة التنفيذية:</strong> تعتمد التقارير النهائية.</li>
                </ul>
            </div>
        </div>

    </div>

</div>
@endsection