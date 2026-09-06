{{-- بطاقات وصول سريع — تُرسم حسب صلاحيات المستخدم الحالي --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-warning" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">وصول سريع</h5>
</div>

<div class="row g-3 mb-4">
    @canPermission('App\Models\Admin\MovementPlan', 'create')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.movement-plans.create') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-truck fs-3 text-primary d-block mb-1"></i>
                <div class="fw-bold">خطة حركة جديدة</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\MovementPlan', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.movement-plans.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-list-check fs-3 text-primary d-block mb-1"></i>
                <div class="fw-bold">خطط الحركة</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\MediaPlan', 'create')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.media-plans.create') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-megaphone fs-3 text-info d-block mb-1"></i>
                <div class="fw-bold">خطة إعلامية جديدة</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Tech\TechIssue', 'create')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.tech.issues.create') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-ticket-perforated fs-3 text-warning d-block mb-1"></i>
                <div class="fw-bold">تذكرة تقنية جديدة</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'create')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.logistics.purchase-requests.create') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-cart-plus fs-3 text-success d-block mb-1"></i>
                <div class="fw-bold">طلب شراء جديد</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Student\Course', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.students.courses.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-journal-bookmark-fill fs-3 text-primary d-block mb-1"></i>
                <div class="fw-bold">إدارة المقررات</div>
            </div>
        </a>
    </div>
    @endcanPermission
</div>