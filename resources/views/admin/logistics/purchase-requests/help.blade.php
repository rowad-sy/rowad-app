@extends('admin.logistics.layouts.master')

@section('title', 'معلومات ونصائح — طلبات الشراء')

@section('logistics-content')
<x-page-header :title="'معلومات ونصائح — طلبات الشراء'" :description="'دليل شامل لدورة حياة طلب الشراء: الإنشاء بالتفاصيل، ثلاث موافقات موقعة، ثم تنفيذ اللوجستي.'"
               :breadcrumb="[['label' => 'اللوجستي'], ['label' => 'معلومات ونصائح — طلبات الشراء']]">
    <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right me-1"></i> العودة لطلبات الشراء
    </a>
</x-page-header>

<div class="row g-3">

    <div class="col-lg-8">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> ما هو طلب الشراء؟</h5></div>
            <div class="p-3">
                <p class="mb-2">طلب رسمي لشراء مواد أو خدمات — يعبّئه <strong>مدير المشروع</strong> كاملاً (بنود بأسعار وعملات USD/SYP) ثم يمر بثلاث موافقات متسلسلة كل واحدة <strong>بتوقيع إلكتروني (صورة)</strong>، وينتهي عند اللوجستي للتنفيذ والطباعة:</p>
                <p class="mb-2 text-primary"><i class="bi bi-tools me-1" aria-hidden="true"></i><strong>طلبات الصيانة</strong> تمر بنفس الدورة تماماً وتتميّز بنوعها — تتنقّل بينها من تبويبات الصفحة (طلبات الشراء / طلبات الصيانة)، والترقيم المقترح لها يبدأ بـ <code>PM-</code>.</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-pencil-square text-primary"></i> مدير المشروع (المنشئ)</strong> — رقم الطلب يدوياً، تاريخ الطلب (تلقائي وقابل للتعديل)، تاريخ التنفيذ المطلوب، المكتب، المشروع مع كوده، البنود، ويختار المُحيل الأول من قائمة بحث.</p>
                    <p class="mb-1"><strong><i class="bi bi-person-check text-primary"></i> الموافِق الأول — المدير المباشر</strong> (عادةً مدير المشاريع): يراجع ويوقّع بصورة ويختار التالي.</p>
                    <p class="mb-1"><strong><i class="bi bi-cash-stack text-primary"></i> الموافِق الثاني — الموارد المالية</strong> (عادةً المدير المالي): يوقّع ويختار التالي.</p>
                    <p class="mb-1"><strong><i class="bi bi-briefcase text-primary"></i> الموافِق الثالث — المدير التنفيذي</strong>: التوقيع النهائي ويُحيل لمدير قسم اللوجستي.</p>
                    <p class="mb-0"><strong><i class="bi bi-truck text-primary"></i> مدير قسم اللوجستي</strong> — لا يوقّع: يطبع الطلب PDF/Excel ويرسله للتنفيذ، ويعلّم كل بند «تم تنفيذه» أم لا.</p>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> خطوة بخطوة</h5></div>
            <div class="p-3">
                <ol class="small mb-0 ps-3">
                    <li class="mb-1"><strong>الإنشاء:</strong> «إضافة طلب شراء» ← <strong>رقم الطلب يدوياً</strong> (مثال PR-2026-0001)، تاريخ الطلب، تاريخ التنفيذ المطلوب، المكتب (المركز)، <strong>المشروع فيُجلب كوده تلقائياً</strong>، الإدارة/القسم، ثم البنود: المنتج والكمية والوحدة <strong>والعملة (دولار أو ليرة)</strong> وسعر الوحدة — الإجماليات تُحسب فوراً ولكل عملة على حدة.</li>
                    <li class="mb-1"><strong>الإحالة:</strong> يختار المنشئ الموافِق الأول من <strong>قائمة منسدلة قابلة للبحث</strong> — يُرشَّح مدير المشاريع افتراضياً، والمرونة كاملة لاختيار أي مستخدم آخر.</li>
                    <li class="mb-1"><strong>كل موافقة:</strong> المستلم الحالي <strong>وحده</strong> يفتح الطلب: يرفع <strong>صورة توقيعه</strong> + يكتب ملاحظة + يختار التالي من قائمة البحث — أو يرفض مع ذكر السبب.</li>
                    <li class="mb-1"><strong>الاعتماد النهائي:</strong> توقيع المدير التنفيذي يقفل الطلب نهائياً ويختار مدير اللوجستي.</li>
                    <li class="mb-1"><strong>التنفيذ:</strong> في صفحة الطلب يعلّم اللوجستي كل بند «منفَّذ» ويحفظ — يتحول الطلب <x-status-badge tone="success">منفَّذ</x-status-badge> تلقائياً عند إتمام كل البنود.</li>
                    <li class="mb-0"><strong>التصدير:</strong> زرّا «طباعة / PDF» و«تصدير Excel» — نموذج موحّد بهوية المؤسسة: رأس الطلب (رقم، مشروع بكوده، مكتب بكوده، تواريخ)، جدول 15 بنداً بالعملاتين وإجماليين، خانات التواقيع الأربعة، وبنود التنفيذ المعلَّمة.</li>
                </ol>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i> حالات الطلب وسير العمل</h5></div>
            <div class="p-3">
                <div class="table-responsive small">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>الحالة</th><th>ماذا تعني؟</th><th>مَن يتصرف؟</th></tr></thead>
                        <tbody>
                            <tr><td><x-status-badge tone="warning">بانتظار موافقة المدير المباشر</x-status-badge></td><td>أُنشئ الطلب كاملاً وأحيل للموافقة الأولى</td><td>المُحال إليه الأول حصراً</td></tr>
                            <tr><td><x-status-badge tone="info">بانتظار موافقة المالية</x-status-badge></td><td>وُقّع من الموافِق الأول</td><td>المُحال إليه الثاني حصراً</td></tr>
                            <tr><td><x-status-badge tone="info">بانتظار موافقة المدير التنفيذي</x-status-badge></td><td>وُقّع من الموافِقَين الأولين</td><td>المُحال إليه الثالث حصراً</td></tr>
                            <tr><td><x-status-badge tone="brand">معتمد — بانتظار تنفيذ اللوجستي</x-status-badge></td><td>اكتملت الموافقات الثلاث — الطلب <strong>مقفل</strong> عن أي تعديل</td><td>مدير اللوجستي (تعليم البنود)</td></tr>
                            <tr><td><x-status-badge tone="success">منفَّذ</x-status-badge></td><td>عُلّمت كل البنود كمنفذة</td><td>— (رؤية فقط)</td></tr>
                            <tr><td><x-status-badge tone="danger">مرفوض</x-status-badge></td><td>رُفض من إحدى الموافقات مع السبب</td><td>— (رؤية فقط)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-people-fill me-1"></i> من يمكنه ماذا؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>الإنشاء والتعديل:</strong> صلاحية الإنشاء تتيح لمدير المشروع التعبئة والإحالة؛ تعديل البنود متاح <strong>لمنشئه فقط</strong> ما دام الطلب في مرحلة المراجعة الأولى.</li>
                    <li class="mb-1"><strong>الموافقة:</strong> المُحال إليه الحالي <strong>حصراً</strong> — لا حتى مدير النظام (super-admin) يستطيع الاعتماد؛ هذا يضمن أن التوقيع الصادر من صاحبه فعلاً.</li>
                    <li class="mb-1"><strong>تحويل الخطوة:</strong> المستلم الحالي يعيد إحالة خطوته لشخص آخر بدل نفسه بقائمة بحث.</li>
                    <li class="mb-1"><strong>التنفيذ والتعليم:</strong> مدير اللوجستي المحدَّد في الطلب فقط.</li>
                    <li class="mb-1"><strong>الاطلاع والطباعة:</strong> من يملك صلاحية عرض ويرى الطلب ضمن نطاقه.</li>
                    <li class="mb-0"><strong>الحذف:</strong> قبل الاعتماد النهائي فقط.</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-quill me-1"></i> التوقيع الإلكتروني</h5></div>
            <div class="p-3 small">
                <p class="mb-0">كل موافقة ترفع <strong>صورة توقيع</strong> (PNG/JPG حتى 2 ميغا) إلزامياً — تُحفظ باسم الموقّع وصفته وتاريخه، وتظهر في خانتها الخاصة من نموذج الطباعة. يمكن لمنشئ الطلب إرفاق توقيعه أيضاً عند الإنشاء (اختياري).</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-currency-exchange me-1"></i> العملتان</h5></div>
            <div class="p-3 small">
                <p class="mb-0">كل بند يختار عملته: <strong>دولار (USD)</strong> أو <strong>ليرة سورية (SYP)</strong>. لا يجمع النظام العملتين في رقم واحد — النموذج المطبوع يعرض <strong>إجماليين منفصلين</strong>.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-hourglass-split me-1"></i> ماذا يظهر في صفحة الطلب؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>شريط الدورة</strong> الخماسي: من الطلب حتى التنفيذ ومن اسمه في كل مرحلة.</li>
                    <li class="mb-1">بنود الطلب بحالة تنفيذ كل بند (علامة ✓) ومن علّمه ومتى.</li>
                    <li class="mb-1"><strong>بطاقات التوقيع الأربع</strong> بالاسم والصفة والتاريخ وصورة التوقيع.</li>
                    <li class="mb-0"><strong>سجل زمني</strong> كامل بكل إحالة وموافقة ورفض.</li>
                </ul>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>رقم الطلب يدوي</strong> ويجب أن يكون غير مستخدم سابقاً — تحقق من الصيغة قبل الإرسال.</li>
                    <li class="mb-1">بعد توقيع المدير التنفيذي <strong>تُقفل الدورة</strong>: لا تعديل ولا حذف — فقط تنفيذ وطباعة.</li>
                    <li class="mb-1">الرفض يسجّل <strong>سبباً دائماً</strong> ويظهر في سجل الطلب.</li>
                    <li class="mb-0">الطباعة/التصدير يظهران «حالة التنفيذ» و ✓ لكل بند — أرسلها للمنفّذ مبدئياً قبل التعليم وأرشفها بعده.</li>
                </ul>
            </div>
        </div>

    </div>

</div>
@endsection
