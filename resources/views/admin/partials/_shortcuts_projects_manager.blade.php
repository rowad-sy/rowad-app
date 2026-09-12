{{-- اختصارات لوحة مدير المشاريع — تُرسم حسب صلاحيات المستخدم الحالي --}}
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

    @canPermission('App\Models\Admin\MediaPlan', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.media-plans.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-calendar-week fs-3 text-info d-block mb-1"></i>
                <div class="fw-bold">الخطط الإعلامية</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.project-docs.documents.index', ['status' => 'under_review']) }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-file-earmark-check fs-3 text-warning d-block mb-1"></i>
                <div class="fw-bold">وثائق بانتظار اعتماد</div>
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

    @canPermission('App\Models\Admin\Logistics\PurchaseRequest', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.logistics.purchase-requests.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-cart3 fs-3 text-success d-block mb-1"></i>
                <div class="fw-bold">طلبات الشراء</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\ProjectTask', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.projects.tasks.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-list-task fs-3 text-primary d-block mb-1"></i>
                <div class="fw-bold">المهام</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\ProjectTask', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.projects.calendar') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-calendar3 fs-3 text-warning d-block mb-1"></i>
                <div class="fw-bold">التقويم الزمني</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\ProjectTask', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.projects.statistics') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-bar-chart fs-3 text-info d-block mb-1"></i>
                <div class="fw-bold">إحصائيات المهام</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Student\Student', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.students.statistics') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-mortarboard fs-3 text-secondary d-block mb-1"></i>
                <div class="fw-bold">إحصائيات الطلاب</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\Hr\Employee', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.hr.employees.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-people fs-3 text-secondary d-block mb-1"></i>
                <div class="fw-bold">الموظفون</div>
            </div>
        </a>
    </div>
    @endcanPermission

    @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
    <div class="col-xl-2 col-md-3 col-6">
        <a href="{{ route('admin.project-docs.documents.index') }}" class="text-decoration-none">
            <div class="table-container text-center p-3 h-100">
                <i class="bi bi-file-earmark-text fs-3 text-primary d-block mb-1"></i>
                <div class="fw-bold">وثائق المشاريع</div>
            </div>
        </a>
    </div>
    @endcanPermission
</div>