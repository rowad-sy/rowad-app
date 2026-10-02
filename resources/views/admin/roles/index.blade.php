@extends('admin.layouts.master')

@section('title', 'الأدوار والنطاقات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الأدوار والنطاقات</h4>
        <p>منح الأدوار (المجموعات) للمستخدمين مع تحديد المركز/المشروع/الفوج الذي يسري فيه الدور</p>
    </div>
    @canPermission('App\Models\Admin\Group', 'edit')
    <a href="{{ route('admin.roles.assign') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> منح دور
    </a>
    @endcanPermission
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-1">
    <div class="col-lg-6">
        <div class="table-container h-100">
            <div class="p-3 border-bottom fw-medium">
                <i class="bi bi-briefcase me-1"></i> الأدوار المتاحة (المجموعات)
                <a href="{{ route('admin.groups.index') }}" class="float-end small text-decoration-none">إدارة تعريفات الأدوار</a>
            </div>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الدور</th>
                        <th>ما يشمله</th>
                        <th>الأعضاء</th>
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
                                <span class="badge bg-secondary">{{ $models->count() }} موديل</span>
                                <div class="text-muted small mt-1">{{ $models->map(fn($m) => class_basename($m))->take(5)->implode('، ') }}{{ $models->count() > 5 ? '…' : '' }}</div>
                            </td>
                            <td><span class="badge bg-info text-white">{{ $role->users_count }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">لا توجد مجموعات — أنشئ مجموعة أولاً من شاشة المجموعات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-container h-100">
            <div class="p-3 border-bottom fw-medium"><i class="bi bi-diagram-3 me-1"></i> إسنادات الأدوار الحالية</div>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المستخدم</th>
                        <th>الدور</th>
                        <th>النطاق</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignments as $a)
                        <tr>
                            <td class="fw-medium">{{ $a->user->name }}</td>
                            <td>{{ $a->role->name }}</td>
                            <td>
                                @if ($a->pivot->center_id === null && $a->pivot->project_id === null && $a->pivot->cohort_id === null)
                                    <span class="badge bg-warning text-dark">كل المنظمة</span>
                                @else
                                    @if ($a->pivot->center_id)
                                        <span class="badge bg-primary">🏢 {{ $centerNames[$a->pivot->center_id] ?? 'مركز ' . $a->pivot->center_id }}</span>
                                    @endif
                                    @if ($a->pivot->project_id)
                                        <span class="badge bg-success">📌 {{ $projectNames[$a->pivot->project_id] ?? 'مشروع ' . $a->pivot->project_id }}</span>
                                    @endif
                                    @if ($a->pivot->cohort_id)
                                        <span class="badge bg-info text-dark">🎓 {{ $cohortNames[$a->pivot->cohort_id] ?? 'فوج ' . $a->pivot->cohort_id }}</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @canPermission('App\Models\Admin\Group', 'edit')
                                <a href="{{ route('admin.roles.edit', [$a->role, $a->user]) }}" class="btn btn-sm btn-outline-primary" title="تعديل النطاق">
                                    <i class="bi bi-sliders"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.roles.destroy', [$a->role, $a->user]) }}" class="d-inline"
                                      onsubmit="return confirm('سحب دور {{ $a->role->name }} من {{ $a->user->name }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="سحب الدور">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                                @endcanPermission
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">لا توجد إسنادات بعد — ابدأ بـ «منح دور»</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="note alert alert-light border mt-3 small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    <b>كيف يعمل النظام؟</b> الدور (المجموعة) يُعرّف «ماذا» — الموديلات والأعلام من
    <a href="{{ route('admin.permissions.index') }}">شاشة الصلاحيات</a>.
    والإسناد هنا يُعرّف «أين» — المركز/المشروع/الفوج. عضوية بلا نطاق = يسري الدور على كل المنظمة.
    تركتُ النطاق هنا فارغاً وسجل صلاحية المجموعة يملك نطاقاً؟ يُستخدم نطاق السجل (توافق خلفي).
</div>
@endsection
