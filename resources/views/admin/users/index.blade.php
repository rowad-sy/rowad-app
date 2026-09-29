@extends('admin.layouts.master')

@section('title', 'المستخدمين')

@section('content')
<x-page-header title="المستخدمين" description="إدارة مستخدمي النظام" :breadcrumb="[['label' => 'الإدارة'], ['label' => 'المستخدمين']]">
    @canPermission('App\Models\User', 'create')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة مستخدم
    </a>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots" aria-hidden="true"></i> المزيد
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('admin.users.export', request()->query()) }}"><i class="bi bi-download me-2" aria-hidden="true"></i>تصدير</a></li>
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-2" aria-hidden="true"></i>استيراد</button></li>
        </ul>
    </div>
    @endcanPermission
</x-page-header>

<div class="table-container">
    @php
        $tabs = [
            '' => ['label' => 'الكل', 'icon' => 'bi-people'],
            'super-admin' => ['label' => 'سوبر أدمن', 'icon' => 'bi-shield-lock'],
            'employee' => ['label' => 'موظف', 'icon' => 'bi-person-workspace'],
            'beneficiary' => ['label' => 'مستفيد', 'icon' => 'bi-person-heart'],
            'student' => ['label' => 'طالب', 'icon' => 'bi-mortarboard'],
        ];
        $currentType = $type ?? 'all';
    @endphp
    <div class="p-3 border-bottom d-flex flex-wrap gap-2" role="navigation" aria-label="تصفية حسب نوع المستخدم">
        @foreach ($tabs as $tabType => $tab)
            @php
                $tabQuery = array_filter([
                    'type' => $tabType !== '' ? $tabType : null,
                    'status' => ($status ?? 'all') !== 'all' ? $status : null,
                    'search' => !empty($search) ? $search : null,
                    'center_id' => $centerId ?? null,
                    'project_id' => $projectId ?? null,
                    'job_title_id' => $jobTitleId ?? null,
                    'per_page' => ($perPage ?? 10) != 10 ? $perPage : null,
                ]);
            @endphp
            <a href="{{ route('admin.users.index', $tabQuery) }}"
               class="btn btn-sm {{ ($currentType === 'all' ? '' : $currentType) === $tabType ? 'btn-primary' : 'btn-outline-secondary' }}"
               @if (($currentType === 'all' ? '' : $currentType) === $tabType) aria-current="page" @endif>
                <i class="bi {{ $tab['icon'] }} me-1" aria-hidden="true"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
    @php
        $activeFilters = collect([!empty($search), ($status ?? 'all') !== 'all', !empty($centerId), !empty($projectId), !empty($jobTitleId)])->filter()->count();
    @endphp
    <x-filter-bar :active="$activeFilters" :clear="route('admin.users.index', array_filter(['type' => $type && $type !== 'all' ? $type : null]))">
        @if ($type && $type !== 'all') <input type="hidden" name="type" value="{{ $type }}"> @endif
            <div class="col-12 col-md-3">
                <label class="form-label" for="f-search">بحث</label>
                <div class="input-group">
                    <input type="text" id="f-search" name="search" class="form-control" placeholder="بحث عن مستخدم/مسمى..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit" aria-label="بحث">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="f-status">الحالة</label>
                <select id="f-status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير مفعّل / مغلق</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="f-center_id">المركز</label>
                <select id="f-center_id" name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="f-project_id">المشروع</label>
                <select id="f-project_id" name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="f-job_title_id">المسمى الوظيفي</label>
                <select id="f-job_title_id" name="job_title_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($jobTitles as $jt)
                        <option value="{{ $jt->id }}" {{ (int)($jobTitleId ?? '') === $jt->id ? 'selected' : '' }}>{{ $jt->title_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex align-items-end">
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
    </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>النوع</th>
                @if ($type === 'employee' || !$type || $type === 'all')
                <th>المسمى الوظيفي</th>
                <th>المركز / المشروع</th>
                @endif
                <th>الحالة</th>
                <th>المجموعات</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td class="fw-medium">{{ $user->name }}</td>
                    <td class="ltr-cell">{{ $user->email }}</td>
                    <td>
                        @if ($user->type === 'super-admin')
                            <x-status-badge tone="danger">سوبر أدمن</x-status-badge>
                        @elseif ($user->type === 'employee')
                            <x-status-badge tone="info">موظف</x-status-badge>
                        @elseif ($user->type === 'beneficiary')
                            <x-status-badge>مستفيد</x-status-badge>
                        @elseif ($user->type === 'student')
                            <x-status-badge tone="brand">طالب</x-status-badge>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    @if ($type === 'employee' || !$type || $type === 'all')
                    <td>
                        @if ($user->type === 'employee' && $user->jobTitle)
                            <x-status-badge>{{ $user->jobTitle->title_ar }}</x-status-badge>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($user->type === 'employee')
                            <div class="small">
                                <span>{{ $user->center?->name ?? '—' }}</span>
                                @if ($user->project)
                                    <span class="text-muted">/ {{ $user->project->name }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        @if ($user->is_active)
                            <x-status-badge tone="success">نشط</x-status-badge>
                        @elseif (blank($user->email_verified_at))
                            <x-status-badge tone="warning">غير مفعّل</x-status-badge>
                            <div class="small text-muted mt-1">لم يفعّل حسابه بعد</div>
                        @else
                            <x-status-badge tone="danger">مغلق</x-status-badge>
                            <div class="small text-danger mt-1">تم إيقاف الحساب من قبل الإدارة</div>
                        @endif
                        @if ($user->must_change_password)
                            <div class="small mt-1" style="color: var(--status-warning)">
                                <i class="bi bi-key"></i> يجب تغيير كلمة المرور
                            </div>
                        @endif
                    </td>
                    <td>
                        @foreach ($user->groups as $group)
                            <x-status-badge tone="info" class="me-1">{{ $group->name }}</x-status-badge>
                        @endforeach
                    </td>
                    <td class="text-nowrap"><div class="row-actions">
                        @canPermission('App\Models\User', 'edit')
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل {{ $user->name }}" title="تعديل">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}"
                                    aria-label="{{ $user->is_active ? 'إيقاف' : 'تفعيل' }} {{ $user->name }}" title="{{ $user->is_active ? 'إيقاف الحساب' : 'تفعيل الحساب' }}">
                                <i class="bi bi-{{ $user->is_active ? 'pause' : 'play' }}" aria-hidden="true"></i>
                            </button>
                        </form>
                        @endcanPermission
                        <x-audit-history :model="'App\Models\User'" :model-id="$user->id" />
                        @canPermission('App\Models\User', 'delete')
                        @if ($user->type === 'super-admin')
                            <button class="btn btn-sm btn-outline-danger" disabled title="لا يمكن حذف مستخدم من نوع سوبر أدمن" aria-label="لا يمكن حذف مستخدم من نوع سوبر أدمن">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $user->name }}" title="حذف">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif
                        @endcanPermission
                    </div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-empty-state icon="bi-people" title="لا يوجد مستخدمون مطابقون"
                                       :hint="$activeFilters ? 'جرّب تعديل الفلاتر أو مسحها.' : null" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $users->total() }} مستخدم
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-1"></i> استيراد مستخدمين
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ملف إكسل</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    الأعمدة المطلوبة (بعناوين إنجليزية أو عربية): الاسم، البريد الإلكتروني، النوع، المسمى الوظيفي، المركز، المشروع، الحالة.
                </p>
                <p class="text-muted small mt-2 mb-0">
                    يُنشأ المستخدم بصلاحية غير مفعّلة وكلمة مرور افتراضية <code>Password@123</code> (يُجبر على تغييرها).
                </p>
                <p class="text-muted small mt-2 mb-0">
                    <a href="{{ route('admin.users.export') }}" target="_blank">تصدير ملف نموذجي</a> لتعديله ثم إعادة استيراده.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">استيراد</button>
            </div>
        </form>
    </div>
</div>
@endsection
