@extends('admin.layouts.master')

@section('title', 'معلومات ونصائح — خطة الحركة')

@section('content')
<x-page-header :title="'معلومات ونصائح — خطة الحركة'" :description="'دليل شامل لدورة حياة خطة الحركة: الإنشاء، المراجعة، التوزيع، والمتابعة.'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'معلومات ونصائح — خطة الحركة']]">
    <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right me-1"></i> العودة لخطة الحركة
    </a>
</x-page-header>

<div class="row g-3">

    <div class="col-lg-8">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-diagram-3 me-1"></i> ما هي خطة الحركة؟</h5></div>
            <div class="p-3">
                <p class="mb-2">خطة الحركة هي <strong>طلب حركة بين المراكز</strong> — انتقال فريق/موظفين من مكان إلى مكان (أو ميداني) لغاية محددة، تمر بسلسلة موافقات قبل التنفيذ:</p>
                <div class="bg-light rounded p-3 mb-0">
                    <p class="mb-1"><strong><i class="bi bi-person-plus text-primary"></i> مدير المشروع / الموظف</strong> — ينشئ الخطة ويحدد التاريخ والمسار والغاية.</p>
                    <p class="mb-1"><strong><i class="bi bi-person-check text-primary"></i> إدارة المشاريع</strong> — تراجع وتصادق أو ترفض مع ذكر السبب.</p>
                    <p class="mb-1"><strong><i class="bi bi-people text-primary"></i> مسؤول الحركة</strong> — يوزّع الخطة على المتابِعين (من ينفّذ المهمة فعلياً).</p>
                    <p class="mb-0"><strong><i class="bi bi-flag text-primary"></i> المتابِعون</strong> — ينفّذون الحركة ثم يغلقها مسؤول الحركة كمنجزة.</p>
                </div>
                <p class="small text-muted mt-2 mb-0">كل خطة تحمل <strong>رقم طلب تلقائياً</strong> بصيغة <code>MOV-YYYY-NNNN</code>.</p>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-list-check me-1"></i> خطوة بخطوة</h5></div>
            <div class="p-3">
                <ol class="small mb-0 ps-3">
                    <li class="mb-1"><strong>إنشاء:</strong> زر «خطة حركة جديدة» ← التاريخ، وقت الانطلاق والعودة، المسار (من/إلى)، الغاية وملاحظات. تُحال الخطة تلقائياً لإدارة المشاريع.</li>
                    <li class="mb-1"><strong>مراجعة إدارة المشاريع:</strong> تفتح صفحة الخطة وتراجعها، ثم «اعتماد» مع اختيار <strong>مسؤول الحركة</strong>، أو «رفض» مع كتابة السبب.</li>
                    <li class="mb-1"><strong>توزيع مسؤول الحركة:</strong> يحدد «المتابِعون» (فريق التنفيذ) ووظيفة كل واحد، فتغدو الخطة <x-status-badge tone="brand">قيد المتابعة</x-status-badge>.</li>
                    <li class="mb-1"><strong>المتابعة:</strong> يتابع المتابِعون الخطة من بريد كل منهم (<x-status-badge tone="info">خطة حركة</x-status-badge>), ويمكنهم إعادة إحالتها لمسؤول الحركة إن لزم.</li>
                    <li class="mb-1"><strong>الإنجاز:</strong> بعد اكتمال المهمة يضغط مسؤول الحركة «إنجاز الخطة» فتغدو <x-status-badge tone="success">منجزة</x-status-badge>.</li>
                </ol>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i> حالات الخطة وسير العمل</h5></div>
            <div class="p-3">
                <div class="row text-center small g-1 mb-3">
                    <div class="col"><x-status-badge tone="warning">بانتظار مراجعة إدارة المشاريع</x-status-badge></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><x-status-badge tone="info">أُحيلت لمسؤول الحركة</x-status-badge></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><x-status-badge tone="brand">قيد المتابعة</x-status-badge></div>
                    <div class="col-auto align-self-center"><i class="bi bi-arrow-left"></i></div>
                    <div class="col"><x-status-badge tone="success">منجزة</x-status-badge></div>
                </div>
                <div class="table-responsive small">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>الحالة</th><th>ماذا تعني؟</th><th>مَن يتصرف؟</th></tr></thead>
                        <tbody>
                            <tr><td><x-status-badge tone="warning">بانتظار مراجعة إدارة المشاريع</x-status-badge></td><td>أُنشئت الخطة وأُحيلت للمراجعة</td><td>إدارة المشاريع (اعتماد/رفض)</td></tr>
                            <tr><td><x-status-badge tone="info">أُحيلت لمسؤول الحركة</x-status-badge></td><td>اعتُمدت وحدد فيها مسؤول الحركة</td><td>مسؤول الحركة (توزيع على المتابِعين)</td></tr>
                            <tr><td><x-status-badge tone="brand">قيد المتابعة</x-status-badge></td><td>وُزِّعت على المتابِعين وجارٍ التنفيذ</td><td>مسؤول الحركة (إنجاز)، المتابِعون (متابعة)</td></tr>
                            <tr><td><x-status-badge tone="success">منجزة</x-status-badge></td><td>اكتمل تنفيذ الحركة</td><td>— (رؤية فقط)</td></tr>
                            <tr><td><x-status-badge tone="danger">مرفوضة</x-status-badge></td><td>رُفضت مع تسجيل السبب</td><td>— (رؤية فقط)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-shield-check me-1"></i> من يمكنه ماذا؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>الإنشاء:</strong> من يملك صلاحية إنشاء لخطة الحركة.</li>
                    <li class="mb-1"><strong>المراجعة والتوزيع:</strong> صاحب الخطوة الحالية فقط (إدارة المشاريع عند «المراجعة»، مسؤول الحركة بعد «الاعتماد») — تتحقق الصلاحية من الإحالة النشطة.</li>
                    <li class="mb-1"><strong>المتابعة:</strong> المتابِعون الذين وُزِّعت عليهم الخطة.</li>
                    <li class="mb-1"><strong>إعادة الإحالة:</strong> المتسلّم الحالي للخطوة يمكنه إعادة إحالتها لشخص آخر بدل نفسه (مثال: مسؤول حركة آخر).</li>
                    <li class="mb-1"><strong>الحذف:</strong> من يملك صلاحية حذف (يُسجَّل الحذف في سجل التدقيق).</li>
                    <li class="mb-0"><strong>الإدارة العليا (Super Admin):</strong> تمر على جميع الخطوات مباشرة.</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-hourglass-split me-1"></i> ماذا يظهر في صفحة الخطة؟</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">بيانات الخطة <strong>ورقم الطلب</strong> وحالته.</li>
                    <li class="mb-1"><strong>الشريط الزمني</strong> بكل إجراء (إنشاء، اعتماد، توزيع، إنجاز...) ومن قام به ومتى.</li>
                    <li class="mb-1"><strong>جدول المتابِعين</strong> بوظيفة كل واحد.</li>
                    <li class="mb-0"><strong>سجل المراجعات</strong> بأسباب الرفض عند الحاجة.</li>
                </ul>
            </div>
        </div>

        <div class="table-container mb-3">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-bell me-1"></i> الإشعارات</h5></div>
            <div class="p-3 small">
                <p class="mb-0">عند وصول الخطة إليك تظهر في <strong>بريدك</strong> داخل النظام، ويمكنك فتحها والتصرف فيها مباشرة — متى وصلت الخطة لدورك تابع التصرف فيها من نفس الصفحة.</p>
            </div>
        </div>

        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> ملاحظات مهمة</h5></div>
            <div class="p-3 small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">وقت العودة يجب أن يكون <strong>بعد</strong> وقت الانطلاق — وإلا يرفض النظام الحفظ.</li>
                    <li class="mb-1">لا تستطيع المراجعة/التوزيع/الإنجاز إلا إذا كانت الخطة <strong>بيدك</strong> حالياً.</li>
                    <li class="mb-1">الرفض يُسجل <strong>السبب</strong> ويظهر إلى الأبد في سجل الخطة.</li>
                    <li class="mb-0">كل الإجراءات <strong>موثقة في الشريط الزمني</strong> ولا يمكن حذفها.</li>
                </ul>
            </div>
        </div>

    </div>

</div>
@endsection