@extends('admin.layouts.master')

@section('title', 'معلومات ونصائح — إدارة المقررات')

@section('content')
<x-page-header title="معلومات ونصائح — إدارة المقررات" description="دليل شامل لطريقة العمل بصفحة إدارة المقررات: كيف تُضاف المقررات بكل ملحقاتها."
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'إدارة المقررات', 'url' => route('admin.students.courses.index')], ['label' => 'معلومات ونصائح']]">
    <a href="{{ route('admin.students.courses.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right me-1" aria-hidden="true"></i> العودة لإدارة المقررات
    </a>
</x-page-header>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> ما هي صفحة إدارة المقررات؟</h5></div>
            <div class="p-3">
                <p class="mb-2">صفحة واحدة في مكان واحد تجمع <strong>كل مقرر وكل ملحقاته</strong> في مشروعك، بدون تنقّل بين صفحات متفرقة:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-book text-primary"></i> المقرر</strong> — ما يسجّل فيه الطالب (تاسع، ثانوية أدبي، طفولة ثانية، كوافيرة مستوى أول، ICDL...).</p>
                    <p class="mb-1"><strong><i class="bi bi-layers text-primary"></i> المستويات / الأقسام</strong> — أقسام داخل المقرر (اختياري): للتدريب المهني مثل «كوافيرة ← مستوى أول / مستوى ثانٍ».</p>
                    <p class="mb-1"><strong><i class="bi bi-journal-text text-primary"></i> المواد</strong> — المواد التي تُدرس في المقرر (رياضيات، عربية، كوافيرة، خياطة...).</p>
                    <p class="mb-1"><strong><i class="bi bi-clipboard-check text-primary"></i> الامتحانات</strong> — لكل مادة امتحاناتها (قبلي، بعدي، دوري، نهائي، أو أي امتحان آخر) و<strong>العلامة العليا</strong> لكل امتحان.</p>
                    <p class="mb-0"><strong><i class="bi bi-calendar-week text-primary"></i> العروض</strong> — نفس المقرر بأكثر من فترة/مدرّس/نوع تدريب (ICDL صباحاً بأحمد، ICDL مساءً بمحمد).</p>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-lightbulb me-1"></i> أمثلة حسب طبيعة المشروع</h5></div>
            <div class="p-3">
                <div class="mb-3">
                    <h6 class="fw-bold"><i class="bi bi-pencil-square text-success"></i> مشروع أثر (تعليم أكاديمي)</h6>
                    <p class="text-muted small mb-1">الطالب يسجّل ويختار «الصف»، والصف يحتوي المواد، وكل مادة لها امتحانات بعلاماتها.</p>
                    <ul class="small">
                        <li>المقرر: <code>تاسع</code> — المواد: رياضيات، عربية، علوم — الرياضيات لها: امتحان قبلي (علامة 20) وامتحان بعدي (علامة 100) ودوري (علامة 30).</li>
                        <li>أضِف مقرراً جديداً لكل صف تقدمه: تاسع، ثانوية أدبي، ثانوية علمي... ولا حاجة لمستويات.</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold"><i class="bi bi-balloon-heart text-success"></i> الروضة</h6>
                    <p class="text-muted small mb-1">وليّ الأمر يسجّل ابنه في صف روضة، وكل صف له مواده وعلاماته وامتحاناته.</p>
                    <ul class="small">
                        <li>المقرر: <code>طفولة ثانية</code> — المواد: تلوين، حروف، أرقام — وامتحانات لكل مادة.</li>
                        <li>مقرر لكل صف: طفولة ثانية، طفولة ثالثة، دورة المرح... ولا حاجة لمستويات.</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold"><i class="bi bi-tools text-success"></i> التدريب المهني</h6>
                    <p class="text-muted small mb-1">دورات لمهنة واحدة بمستويات متعددة.</p>
                    <ul class="small">
                        <li>المقرر: <code>كوافيرة</code> — أضِف مستوى أول ومستوى ثانٍ في قسم «المستويات». مواد الدورة تُضاف كمواد للمقرر.</li>
                        <li>خياطة، نجارة... مقرر لكل مهنة، ومستويات داخل كل مقرر.</li>
                    </ul>
                </div>
                <div class="mb-0">
                    <h6 class="fw-bold"><i class="bi bi-diagram-2 text-success"></i> التدريبات الإدارية والتقنية</h6>
                    <p class="text-muted small mb-1">دورات منفصلة (ICDL، HR...) وأحياناً دورة واحدة بعدة أنواع تدريب.</p>
                    <ul class="small">
                        <li>المقرر: <code>ICDL</code> — أضِف «عرضاً» لكل نوع تدريب (صباحي بأحمد / مسائي بمحمد) في قسم العروض.</li>
                        <li>مقرر لكل دورة: ICDL، HR، حسابات... </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> خطوة بخطوة</h5></div>
            <div class="p-3">
                <ol class="small">
                    <li class="mb-1"><strong>أضف المقرر:</strong> من زر «إضافة مقرر» في أعلى الصفحة. اختر المشروع واكتب اسم المقرر.</li>
                    <li class="mb-1"><strong>أضف المستويات (اختياري):</strong> من قسم «المستويات / الأقسام» — للدورات المهنية خاصة.</li>
                    <li class="mb-1"><strong>أضف المواد:</strong> من قسم «المواد الدراسية» زر «إضافة مادة» ثم اكتب اسم المادة.</li>
                    <li class="mb-1"><strong>أضف امتحانات كل مادة:</strong> داخل بطاقة المادة اضغط «إضافة امتحان للمادة» وحدد الاسم (قبلي/بعدي/دوري/نهائي/أخرى) و<strong>العلامة العليا</strong> للامتحان.</li>
                    <li class="mb-1"><strong>أضف العروض (اختياري):</strong> من قسم «عروض المقرر» لكل فترة/مدرّس/نوع تدريب.</li>
                    <li class="mb-1"><strong>احفظ:</strong> زر «حفظ» في الأسفل. يظهر المقرر في الصفحة الرئيسية بعدادات مواده وامتحاناته.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-calendar-week me-1"></i> إنشاء خطة تدريبية</h5></div>
            <div class="p-3 small">
                <p>بجانب كل مقرر يوجد زر <span class="badge bg-primary">خطة تدريبية</span>.</p>
                <p>ينقلك إلى صفحة إنشاء الخطة التدريبية <strong>وقد عُدّلت تلقائياً لتناسب مقررك</strong> (المشروع والمواد المنسدلة لعناصر المقرر).</p>
                <p>الخطة التدريبية تفصّل <strong>الجدول الأسبوعي</strong> للدورة: لكل أسبوع، اليوم، الصف/المستوى، المادة، المدرّس، الوقت والمكان.</p>
                <p class="mb-0 text-muted">يمكنك أيضاً الدخول للخطط التدريبية من القائمة الجانبية مباشرة.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-pencil me-1"></i> تعديل أو حذف</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><i class="bi bi-pencil text-primary"></i> زر <strong>قلم التعديل</strong> يجلب كل المقرر وملحقاته في نفس النموذج.</li>
                    <li><i class="bi bi-trash text-danger"></i> زر <strong>الحذف</strong> يحذف المقرر مع مواده وامتحاناته وعروضه.</li>
                </ul>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-check-circle me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">كل ما تضيفه يقتصر على <strong>المشروع</strong> الذي اخترته — المقررات تختلف من مشروع لآخر.</li>
                    <li class="mb-1">لا يدخل الطالب/ولي الأمر إلى هذه الصفحة؛ التسجيل يتم من صفحة الطلاب والاختيار منها المقرر.</li>
                    <li class="mb-1">«العلامة العليا» هي الدرجة العظمى للامتحان وليست نتيجة طالب — نتائج الطلاب تُدخل من ملف الطالب.</li>
                    <li class="mb-0">أضف المقرر <strong>قبل</strong> تسجيل الطلاب به حتى يظهر في خيارات التسجيل.</li>
                </ul>
            </div>
        </div>
    </div>

</div>
@endsection