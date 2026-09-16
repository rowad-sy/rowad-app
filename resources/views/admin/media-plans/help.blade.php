@extends('admin.layouts.master')

@section('title', 'معلومات ونصائح — الخطة الإعلامية')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-question-circle ms-1"></i> معلومات ونصائح — الخطة الإعلامية</h4>
        <p>دليل شامل لدورة حياة الخطة الإعلامية الشهرية وفعاليات التغطية.</p>
    </div>
    <a href="{{ route('admin.media-plans.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right me-1"></i> العودة للخطة الإعلامية
    </a>
</div>

<div class="row g-3">

    <div class="col-lg-8">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> ما هي الخطة الإعلامية؟</h5></div>
            <div class="p-3">
                <p class="mb-2">خطة شهرية تُعِدّ فريق التواصل للفعاليات والأنشطة الإعلامية والتغطية، تمر بأربع مراحل اعتماد ثم تنفيذ:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-file-earmark-plus text-primary"></i> الإنشاء</strong> — إعداد الخطة مع فعالياتها (التاريخ، الساعة، المكان، المسؤول، ملخص، نوع التغطية).</p>
                    <p class="mb-1"><strong><i class="bi bi-person-check text-primary"></i> المدير المباشر</strong> — موافقة أولى، وعندها <strong>تُقفل الخطة</strong>.</p>
                    <p class="mb-1"><strong><i class="bi bi-diagram-2 text-primary"></i> مدير المشاريع ثم مدير الإعلام</strong> — موافقتان متتاليتان.</p>
                    <p class="mb-0"><strong><i class="bi bi-camera text-primary"></i> المسؤول الإعلامي</strong> — يحدد مصير كل فعالية (نُفِّذت / لم تُنفَّذ) ثم يغلق الخطة كمنجزة.</p>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> خطوة بخطوة</h5></div>
            <div class="p-3">
                <ol class="small mb-0 ps-3">
                    <li class="mb-1"><strong>إنشاء الخطة:</strong> زر «إضافة خطة» ← الشهر، المركز، المشروع، وملاحظات. أضِف <strong>الفعاليات</strong> (التاريخ، الساعة، المكان، المسؤول، نوع التغطية...). حدد المدير المباشر ومدير الإعلام ثم حفظ.</li>
                    <li class="mb-1"><strong>موافقة المدير المباشر:</strong> يوافق فيُقفل كل شي («موافقة وإحالة لمدير المشاريع») أو يرفض مع السبب.</li>
                    <li class="mb-1"><strong>موافقة مدير المشاريع:</strong> يوافق ويحيل لمدير الإعلام.</li>
                    <li class="mb-1"><strong>موافقة مدير الإعلام:</strong> يوافق ويحيل للمسؤول الإعلامي في مركز الخطة.</li>
                    <li class="mb-1"><strong>تنفيذ الفعاليات:</strong> المسؤول الإعلامي يضغط على كل فعالية ويحدد <span class="badge bg-success">نُفِّذت</span> أو <span class="badge bg-danger">لم تُنفَّذ</span> مع ملاحظة.</li>
                    <li class="mb-0"><strong>إغلاق الخطة:</strong> بعد وضع علامات كل الفعاليات ← «إغلاق الخطة كمنجزة».</li>
                </ol>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i> حالات الخطة وسير العمل</h5></div>
            <div class="p-3">
                <div class="row text-center small g-1 mb-3">
                    <div class="col"><span class="badge bg-warning text-dark w-100 py-2">بانتظار موافقة المدير المباشر</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-secondary w-100 py-2">وافق المدير المباشر</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-secondary w-100 py-2">وافق مدير المشاريع</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-secondary w-100 py-2">وافق مدير الإعلام</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-primary w-100 py-2">قيد التنفيذ</span></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><span class="badge bg-success w-100 py-2">منجزة</span></div>
                </div>
                <div class="table-responsive small">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>الحالة</th><th>ماذا تعني؟</th><th>مَن يتصرف؟</th></tr></thead>
                        <tbody>
                            <tr><td><span class="badge bg-warning text-dark">بانتظار موافقة المدير المباشر</span></td><td>أُنشئت الخطة وأُحيلت للموافقة الأولى</td><td>المدير المباشر</td></tr>
                            <tr><td><span class="badge bg-secondary">وافق المدير المباشر</span></td><td>الموافقة الأولى — <strong>أُقفلت الخطة نهائياً عن التعديل</strong></td><td>مدير المشاريع</td></tr>
                            <tr><td><span class="badge bg-secondary">وافق مدير المشاريع</span></td><td>الموافقة الثانية</td><td>مدير الإعلام</td></tr>
                            <tr><td><span class="badge bg-secondary">وافق مدير الإعلام</span></td><td>الموافقة الثالثة — جاهزة للتنفيذ</td><td>المسؤول الإعلامي (وضع علامات الفعاليات)</td></tr>
                            <tr><td><span class="badge bg-primary">قيد التنفيذ</span></td><td>بُدئ بوضع علامات التنفيذ</td><td>المسؤول الإعلامي</td></tr>
                            <tr><td><span class="badge bg-success">منجزة</span></td><td>أُغلقت الخطة بعد التنفيذ</td><td>— (رؤية فقط)</td></tr>
                            <tr><td><span class="badge bg-danger">مرفوضة</span></td><td>رُفضت من أي مرحلة مع السبب</td><td>— (رؤية فقط)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-lock me-1"></i> القفل والتعديل</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">بعد موافقة <strong>المدير المباشر</strong> تُقفل الخطة تلقائياً: لا تعديل، لا حذف، لا إضافة/حذف فعاليات.</li>
                    <li class="mb-1">قبل القفل يمكن تعديل الخطة وفعالياتها بحرية.</li>
                    <li class="mb-1">منع <strong>تعارض المواعيد</strong>: لا يمكن إضافة فعاليتين بنفس التاريخ والساعة في الخطة نفسها، ويُفحص التعارض أيضاً عند الحفظ الجماعي للفعاليات.</li>
                    <li class="mb-0">يمكن إضافة <strong>تعليقات</strong> على كل فعالية لأي مستخدم مخوّل.</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-hourglass-split me-1"></i> ماذا يظهر في صفحة الخطة؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">بيانات الخطة وحالتها والجهة التي بيدها حالياً.</li>
                    <li class="mb-1"><strong>جدول الفعاليات</strong> بوضع تنفيذ كل واحدة (نُفِّذت / لم تُنفَّذ / لم يحدَّد).</li>
                    <li class="mb-1"><strong>الشريط الزمني</strong> بكل موافقة وإحالة وتعليق.</li>
                    <li class="mb-0"><strong>سجل الإحالات</strong> بين الجهات على طول الدورة.</li>
                </ul>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-bell me-1"></i> الإشعارات</h5></div>
            <div class="p-3 small">
                <p class="mb-0">كل مرحلة تمرر الخطة للجهة التالية تلقائياً، فتصل إليها في <strong>بريدها</strong> داخل النظام جاهزة للتصرف.</p>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">لا يوافق المدير المباشر إلا وهو محدد أمامه <strong>الجهة التالية</strong> (مدير المشاريع) — تظهر تلقائياً ويمكن تغييرها.</li>
                    <li class="mb-1">الخطة المقفلة لا تعني توقف التنفيذ — الوضع يُحدَّد لكل فعالية.</li>
                    <li class="mb-1">رفض أي مرحلة يرجع الخطة «مرفوضة» ويسجل السبب، ولا يعيد إرسالها إلا بإنشاء جديدة.</li>
                    <li class="mb-1">«لم تُنفَّذ» تتطلب <strong>ملاحظة</strong> توضح السبب.</li>
                    <li class="mb-0">كل الإجراءات <strong>موثقة في الشريط الزمني</strong> ولا يمكن حذفها.</li>
                </ul>
            </div>
        </div>

    </div>

</div>
@endsection