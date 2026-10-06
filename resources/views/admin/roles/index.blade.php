@extends('admin.layouts.master')

@section('title', 'الأدوار والنطاقات')

@section('content')
<x-page-header title="الأدوار والنطاقات" description="عرّف الأدوار وسمّها ثم أسندها للمستخدمين مع تحديد المركز/المشروع/الفوج الذي يسري فيه كل دور"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'الأدوار والنطاقات']]">
    <div class="d-flex gap-2">
        @canPermission('App\Models\Admin\Group', 'create')
        <a href="{{ route('admin.roles.create') }}" class="btn btn-outline-primary">
            <i class="bi bi-briefcase me-1" aria-hidden="true"></i> تعريف دور جديد
        </a>
        @endcanPermission
        @canPermission('App\Models\Admin\Group', 'edit')
        <a href="{{ route('admin.roles.assign') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i> منح دور
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="alert alert-light border small py-2 mb-3">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    أسماء الأدوار <b>فريدة على مستوى النظام كله</b> — الاسم نفسه لا يمكن أن يتكرر بين الأدوار أو المجموعات؛
    إن رفض النظام اسماً فهو مستخدم في <a href="{{ route('admin.groups.index') }}" class="text-decoration-none">شاشة المجموعات</a>.
</div>

<div class="row g-3 mb-1">
    <div class="col-lg-6">
        <div class="table-container h-100">
            <div class="p-3 border-bottom fw-medium">
                <i class="bi bi-briefcase me-1" aria-hidden="true"></i> الأدوار (قوالب الوظائف)
                <a href="{{ route('admin.groups.index') }}" class="float-end small text-decoration-none">المجموعات الاعتيادية</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الدور</th>
                            <th>ما يشمله</th>
                            <th>الأعضاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            @php
                                $models = $role->permissions->flatMap(fn($p) => $p->model_names ?? [])->unique();
                            @endphp
                            <tr>
                                <td class="fw-medium">
                                    {{ $role->name }}
                                    <div class="text-muted small">{{ $role->description ?? '—' }}</div>
                                </td>
                                <td>
                                    <x-status-badge tone="neutral">{{ $models->count() }} موديل</x-status-badge>
                                    <div class="text-muted small mt-1">{{ $models->map(fn($m) => class_basename($m))->take(5)->implode('، ') }}{{ $models->count() > 5 ? '…' : '' }}</div>
                                </td>
                                <td class="num"><x-status-badge tone="info">{{ $role->users_count }}</x-status-badge></td>
                                <td class="text-nowrap">
                                    <div class="row-actions">
                                        @canPermission('App\Models\Admin\Group', 'edit')
                                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-sm btn-outline-success" aria-label="تصيير صلاحيات دور {{ $role->name }}" title="تعريف الصلاحيات من المصفوفة">
                                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل دور {{ $role->name }}" title="تعديل التسمية/الوصف">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        @endcanPermission
                                        @canPermission('App\Models\Admin\Group', 'delete')
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                              onsubmit="return confirm('حذف الدور «{{ $role->name }}» بكل إسناداته وصلاحياته؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف دور {{ $role->name }}" title="حذف الدور">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        @endcanPermission
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row colspan="4" title="لا توجد أدوار بعد" hint="اضغط «تعريف دور جديد» وابدأ بدور مثل «مدير مشروع»" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-container h-100">
            <div class="p-3 border-bottom fw-medium"><i class="bi bi-diagram-3 me-1" aria-hidden="true"></i> إسنادات الأدوار الحالية</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الدور</th>
                            <th>النطاق</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $a)
                            <tr>
                                <td class="fw-medium">{{ $a->user->name }}</td>
                                <td>{{ $a->role->name }}</td>
                                <td>
                                    @if ($a->pivot->center_id === null && $a->pivot->project_id === null && $a->pivot->cohort_id === null)
                                        <x-status-badge tone="warning">كل المنظمة</x-status-badge>
                                    @else
                                        @if ($a->pivot->center_id)
                                            <x-status-badge tone="brand"><i class="bi bi-building me-1" aria-hidden="true"></i>{{ $centerNames[$a->pivot->center_id] ?? 'مركز ' . $a->pivot->center_id }}</x-status-badge>
                                        @endif
                                        @if ($a->pivot->project_id)
                                            <x-status-badge tone="success"><i class="bi bi-pin-angle me-1" aria-hidden="true"></i>{{ $projectNames[$a->pivot->project_id] ?? 'مشروع ' . $a->pivot->project_id }}</x-status-badge>
                                        @endif
                                        @if ($a->pivot->cohort_id)
                                            <x-status-badge tone="info"><i class="bi bi-mortarboard me-1" aria-hidden="true"></i>{{ $cohortNames[$a->pivot->cohort_id] ?? 'فوج ' . $a->pivot->cohort_id }}</x-status-badge>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <div class="row-actions">
                                        @canPermission('App\Models\Admin\Group', 'edit')
                                        <a href="{{ route('admin.roles.assignment.edit', [$a->role, $a->user]) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل نطاق {{ $a->user->name }}" title="تعديل النطاق">
                                            <i class="bi bi-sliders" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.roles.assignment.destroy', [$a->role, $a->user]) }}"
                                              onsubmit="return confirm('سحب دور {{ $a->role->name }} من {{ $a->user->name }}؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" aria-label="سحب دور {{ $a->role->name }} من {{ $a->user->name }}" title="سحب الدور">
                                                <i class="bi bi-person-dash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        @endcanPermission
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row colspan="4" title="لا توجد إسنادات بعد" hint="ابدأ بـ «منح دور»" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border mt-3 small text-muted">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    <b>كيف يعمل النظام؟</b> الدور يُعرّف «ماذا» — الموديلات والأعلام من
    <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none">شاشة الصلاحيات</a>
    (بلا نطاق هناك الترتيبة الجديدة). والإسناد هنا يُعرّف «أين» — المركز/المشروع/الفوج.
    عضوية بلا نطاق = يسري الدور على كل المنظمة. السجلات القديمة الحاملة لنطاق تُحترم حتى تُنقَّى (توافق خلفي).
    المجموعات الاعتيادية (حزم بلا نطاق) تُدار من شاشة المجموعات.
</div>
@endsection
