@extends('admin.layouts.master')

@section('title', 'المستخدمين')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المستخدمين</h4>
        <p>إدارة مستخدمي النظام</p>
    </div>
    @canPermission('App\Models\User', 'create')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مستخدم
    </a>
    <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-outline-success">
        <i class="bi bi-download me-1"></i> تصدير
    </a>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="bi bi-upload me-1"></i> استيراد
    </button>
    @endcanPermission
</div>

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
    <div class="p-3 border-bottom d-flex flex-wrap gap-2">
        @foreach ($tabs as $tabType => $tab)
            @php
                $tabQuery = array_filter([
                    'type' => $tabType !== '' ? $tabType : null,
                    'status' => ($status ?? 'all') !== 'all' ? $status : null,
                    'search' => !empty($search) ? $search : null,
                ]);
            @endphp
            <a href="{{ route('admin.users.index', $tabQuery) }}"
               class="btn btn-sm {{ ($currentType === 'all' ? '' : $currentType) === $tabType ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="bi {{ $tab['icon'] }} me-1"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن مستخدم/مسمى..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>غير مفعّل / مغلق</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المسمى الوظيفي</label>
                <select name="job_title_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($jobTitles as $jt)
                        <option value="{{ $jt->id }}" {{ (int)($jobTitleId ?? '') === $jt->id ? 'selected' : '' }}>{{ $jt->title_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
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
                    <td dir="ltr">{{ $user->email }}</td>
                    <td>
                        @if ($user->type === 'super-admin')
                            <span class="badge bg-danger text-white">سوبر أدمن</span>
                        @elseif ($user->type === 'employee')
                            <span class="badge bg-info text-white">موظف</span>
                        @elseif ($user->type === 'beneficiary')
                            <span class="badge bg-secondary text-white">مستفيد</span>
                        @elseif ($user->type === 'student')
                            <span class="badge bg-primary text-white">طالب</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    @if ($type === 'employee' || !$type || $type === 'all')
                    <td>
                        @if ($user->type === 'employee' && $user->jobTitle)
                            <span class="badge bg-secondary">{{ $user->jobTitle->title_ar }}</span>
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
                            <span class="badge bg-success">نشط</span>
                        @elseif (blank($user->email_verified_at))
                            <span class="badge bg-warning text-dark">غير مفعّل</span>
                            <div class="small text-muted mt-1">لم يفعّل حسابه بعد</div>
                        @else
                            <span class="badge bg-danger">مغلق</span>
                            <div class="small text-danger mt-1">تم إيقاف الحساب من قبل الإدارة</div>
                        @endif
                        @if ($user->must_change_password)
                            <div class="small text-warning mt-1">
                                <i class="bi bi-key"></i> يجب تغيير كلمة المرور
                            </div>
                        @endif
                    </td>
                    <td>
                        @foreach ($user->groups as $group)
                            <span class="badge bg-info text-white me-1">{{ $group->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        @canPermission('App\Models\User', 'edit')
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}">
                                <i class="bi bi-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        @endcanPermission
                        <x-audit-history :model="'App\Models\User'" :model-id="$user->id" />
                        @canPermission('App\Models\User', 'delete')
                        @if ($user->type === 'super-admin')
                            <button class="btn btn-sm btn-outline-danger" disabled title="لا يمكن حذف مستخدم من نوع سوبر أدمن">
                                <i class="bi bi-trash"></i>
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                        @endcanPermission
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        لا توجد مستخدمين
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

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
