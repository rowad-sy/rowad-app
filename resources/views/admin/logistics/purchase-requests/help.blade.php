@extends('admin.logistics.layouts.master')

@section('title', 'معلومات ونصائح — طلبات الشراء')

@section('logistics-content')
<x-page-header :title="'معلومات ونصائح — طلبات الشراء'" :description="'دليل شامل لدورة حياة طلب الشراء: الإنشاء، التسعير، الموافقات، والتنفيذ.'"
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
                <p class="mb-2">طلب رسمي لشراء مواد أو خدمات، يمر بمسار تسعير وموافقات متعدد الجهات قبل تنفيذه:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-person-plus text-primary"></i> المسؤول (المنشئ)</strong> — يكتب البنود (الوصف، الكمية، الوحدة، السعر التقديري) ويوقّع إلكترونياً.</p>
                    <p class="mb-1"><strong><i class="bi bi-calculator text-primary"></i> اللوجستي</strong> — يحدد الأسعار النهائية ورقم الميزانية.</p>
                    <p class="mb-1"><strong><i class="bi bi-person-check text-primary"></i> المدير المباشر</strong> — موافقة تُقفل الطلب نهائياً.</p>
                    <p class="mb-1"><strong><i class="bi bi-diagram-2 text-primary"></i> مدير المشاريع ثم المسؤول المالي</strong> — موافقتان متتاليتان.</p>
                    <p class="mb-1"><strong><i class="bi bi-shield-lock text-primary"></i> المدير التنفيذي</strong> — الاعتماد النهائي.</p>
                    <p class="mb-0"><strong><i class="bi bi-box-seam text-primary"></i> اللوجستي</strong> — ينفّذ الطلب بعد الاعتماد.</p>
                </div>
                <p class="small text-muted mt-2 mb-0">كل طلب يحمل <strong>رقماً تلقائياً</strong> بصيغة <code>PR-YYYY-NNNNN</code>.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> خطوة بخطوة</h5></div>
            <div class="p-3">
                <ol class="small mb-0 ps-3">
                    <li class="mb-1"><strong>إنشاء الطلب:</strong> زر «إضافة طلب شراء» ← المركز والمشروع، ثم البنود (بند واحد على الأقل)، و<strong>التوقيع الإلكتروني</strong> (قلم على الشاشة أو رفع صورة توقيع). يُحال تلقائياً للوجستي.</li>
                    <li class="mb-1"><strong>التسعير (اللوجستي):</strong> يفتح واجهة التسعير ويحدد لكل بند <strong>السعر النهائي</strong> ورقم الميزانية ← «حفظ وإحالة للمدير المباشر».</li>
                    <li class="mb-1"><strong>موافقة المدير المباشر:</strong> موافقة ← <strong>يُقفل الطلب نهائياً</strong> ويُحال لمدير المشاريع. أو رفض.</li>
                    <li class="mb-1"><strong>موافقة مدير المشاريع:</strong> يحيل للمسؤول المالي.</li>
                    <li class="mb-1"><strong>موافقة المسؤول المالي:</strong> يحيل للمدير التنفيذي.</li>
                    <li class="mb-1"><strong>الاعتماد النهائي (المدير التنفيذي):</strong> اعتماد ← الطلب <x-status-badge tone="success">معتمد</x-status-badge>.</li>
                    <li class="mb-0"><strong>التنفيذ (اللوجستي):</strong> بعد الاعتماد يضغط اللوجستي «تنفيذ الطلب» ← <x-status-badge>منفَّذ</x-status-badge>.</li>
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
                            <tr><td><x-status-badge tone="warning">بانتظار التسعير</x-status-badge></td><td>أُنشئ الطلب ومعه البنود والسعر التقديري</td><td>اللوجستي (تسعير + رقم الميزانية)</td></tr>
                            <tr><td><x-status-badge tone="info">مُسعَّر</x-status-badge></td><td>حُددت الأسعار النهائية وجاهز للتوقيع</td><td>المدير المباشر (موافقة/رفض)</td></tr>
                            <tr><td><x-status-badge>وافق مدير المشروع</x-status-badge></td><td>الموافقة الأولى — <strong>أُقفل الطلب عن التعديل والحذف</strong></td><td>مدير المشاريع</td></tr>
                            <tr><td><x-status-badge>وافق مدير المشاريع</x-status-badge></td><td>الموافقة الثانية</td><td>المسؤول المالي</td></tr>
                            <tr><td><x-status-badge>وافق المسؤول المالي</x-status-badge></td><td>الموافقة المالية</td><td>المدير التنفيذي</td></tr>
                            <tr><td><x-status-badge tone="success">معتمد</x-status-badge></td><td>الاعتماد النهائي — جاهز للتنفيذ</td><td>اللوجستي (تنفيذ)</td></tr>
                            <tr><td><x-status-badge>منفَّذ</x-status-badge></td><td>اكتمل تنفيذ الطلب</td><td>— (رؤية فقط)</td></tr>
                            <tr><td><x-status-badge tone="danger">مرفوض</x-status-badge></td><td>رُفض في أي مرحلة (يظهر سبب الرفض)</td><td>— (رؤية فقط)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-people-fill me-1"></i> من يمكنه ماذا؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>الإنشاء والتوقيع:</strong> من يملك صلاحية إنشاء لطلبات الشراء (يُحدد مسبقاً لوجستي المركز ومدير المشروع الافتراضيان في النموذج).</li>
                    <li class="mb-1"><strong>التسعير:</strong> اللوجستي المحدَّد لهذا الطلب فقط (أو الإدارة العليا).</li>
                    <li class="mb-1"><strong>كل موافقة:</strong> صاحب الخطوة الحالية (المتسلّم عبر الإحالة) فقط — تتحقق الصلاحية من الإحالة النشطة.</li>
                    <li class="mb-1"><strong>إعادة الإحالة:</strong> المتسلّم الحالي لأي خطوة يعيد إحالتها لشخص آخر بدل نفسه.</li>
                    <li class="mb-1"><strong>التنفيذ:</strong> لوجستي الطلب فقط بعد الاعتماد.</li>
                    <li class="mb-1"><strong>الحذف:</strong> متاح فقط قبل قفل الطلب (قبل موافقة مدير المشروع).</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-pencil-square me-1"></i> التوقيع الإلكتروني</h5></div>
            <div class="p-3 small">
                <p class="mb-0">عند إنشاء الطلب يمكنك التوقيع بطريقتين: <strong>الرسم بالقلم على اللوحة</strong> أو رفع <strong>صورة توقيع</strong>. يظهر التوقيع في طباعة الطلب.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-cash-coin me-1"></i> قواعد الاعتماد المالي</h5></div>
            <div class="p-3 small">
                <p class="mb-0">قد يخضع الطلب لمراجعة موافقات إضافية (Approval Rules) حسب قيمة الطلب — تُنشأ هذه القواعد من صفحة «قواعد موافقات الشراء» في اللوجستيك.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-hourglass-split me-1"></i> ماذا يظهر في صفحة الطلب؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">بيانات الطلب والبنود و<strong>الإجمالي</strong> ورقم الميزانية.</li>
                    <li class="mb-1"><strong>سجل الموافقات</strong> بجهة كل موافقة وتاريخها وتعليقها.</li>
                    <li class="mb-1"><strong>الشريط الزمني</strong> بكل إجراء من الإنشاء حتى التنفيذ.</li>
                    <li class="mb-0"><strong>زر الطباعة</strong> لنسخة A4 بالحالة والتوقيع.</li>
                </ul>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">بعد موافقة <strong>المدير المباشر</strong> لا يمكن تعديل الطلب أو حذفه — راجع البنود جيداً قبل الإرسال.</li>
                    <li class="mb-1">الرفض في أي مرحلة يوقف الدورة ويُظهر <strong>سبب الرفض</strong> بشكل دائم.</li>
                    <li class="mb-1">التنفيذ مسؤولية اللوجستي <strong>بعد الاعتماد فقط</strong>.</li>
                    <li class="mb-0">كل الإجراءات <strong>موثقة في سجل العمل</strong> ولا يمكن حذفها.</li>
                </ul>
            </div>
        </div>

    </div>

</div>
@endsection