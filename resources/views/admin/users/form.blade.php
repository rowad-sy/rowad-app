@php
    $studentId = old('student_id', isset($user) ? $user->student?->id : '');
    $employeeId = old('employee_id', isset($user) ? $user->employee?->id : '');
@endphp

@extends('admin.layouts.master')

@section('title', isset($user) ? 'تعديل مستخدم' : 'إضافة مستخدم')

@section('content')
<x-page-header :title="isset($user) ? 'تعديل المستخدم' : 'إضافة مستخدم'"
               :breadcrumb="[['label' => 'المستخدمين', 'url' => route('admin.users.index')], ['label' => isset($user) ? $user->name : 'جديد']]" />

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if (isset($user))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email ?? '') }}" required dir="ltr">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        كلمة المرور
                        @if (!isset($user))
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           {{ isset($user) ? '' : 'required' }}>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if (isset($user))
                        <div class="form-text text-muted">اتركه فارغاً إذا لم ترد تغيير كلمة المرور</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" class="form-control"
                           {{ isset($user) ? '' : 'required' }}>
                </div>

                <div class="mb-3">
                    <label class="form-label">النوع</label>
                    <select name="type" id="userType" class="form-select @error('type') is-invalid @enderror">
                        <option value="">— عادي —</option>
                        <option value="super-admin" {{ old('type', $user->type ?? '') == 'super-admin' ? 'selected' : '' }}>سوبر أدمن</option>
                        <option value="employee" {{ old('type', $user->type ?? '') == 'employee' ? 'selected' : '' }}>موظف</option>
                        <option value="beneficiary" {{ old('type', $user->type ?? '') == 'beneficiary' ? 'selected' : '' }}>مستفيد</option>
                        <option value="student" {{ old('type', $user->type ?? '') == 'student' ? 'selected' : '' }}>طالب</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3 link-select" data-type="student" style="display: none;">
                    <label class="form-label">ربط بطالب</label>
                    <select name="student_id" class="form-select @error('student_id') is-invalid @enderror">
                        <option value="">— اختر طالباً —</option>
                        @foreach (\App\Models\Admin\Student\Student::doesntHave('user')->orderBy('first_name_ar')->get() as $s)
                            <option value="{{ $s->id }}" {{ old('student_id', $studentId ?? '') == $s->id ? 'selected' : '' }}>
                                {{ $s->first_name_ar }} {{ $s->last_name_ar }} ({{ $s->student_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text text-muted">اختر الطالب المراد ربط الحساب به</div>
                </div>

                <div class="mb-3 link-select" data-type="employee" style="display: none;">
                    <label class="form-label">ربط بموظف</label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                        <option value="">— اختر موظفاً —</option>
                        @foreach (\App\Models\Admin\Hr\Employee::doesntHave('user')->orderBy('first_name_ar')->get() as $e)
                            <option value="{{ $e->id }}" {{ old('employee_id', $employeeId ?? '') == $e->id ? 'selected' : '' }}>
                                {{ $e->first_name_ar }} {{ $e->last_name_ar }} ({{ $e->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text text-muted">اختر الموظف المراد ربط الحساب به</div>
                </div>

                <div class="employee-fields" style="display: none;">
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-person-workspace me-1"></i> بيانات الموظف</h6>
                    <p class="text-muted small mb-3">بيانات إضافية للمستخدم من نوع موظف — تُستخدم في البحث والفلترة (مستقلة عن سجل الموارد البشرية).</p>

                    <div class="mb-3">
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            المسمى الوظيفي لم يعد يُطلب هنا — الصلاحيات وتسمية الوظيفة تُحدَّد عبر <b>الأدوار والنطاقات</b> بالأسفل.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">— اختر المركز —</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ old('center_id', $user->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $user->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- الترتيبة v2: إسناد الأدوار مع النطاق من نفس الشاشة --}}
                @if ($canAssignRoles ?? false)
                    <hr>
                    <h6 class="text-primary mb-1"><i class="bi bi-briefcase me-1" aria-hidden="true"></i> الأدوار والنطاقات</h6>
                    <p class="text-muted small mb-3">
                        علّم أدوار هذا المستخدم وحدّد لكل دور نطاقه (مركز/مشروع/فوج) — اتركها = يسري على كل المنظمة.
                        تعريف الأدوار: <a href="{{ route('admin.roles.index') }}" class="text-decoration-none">شاشة الأدوار</a>،
                        وصلاحياتها: <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none">شاشة الصلاحيات</a>.
                    </p>

                    @if ($roles->isEmpty())
                        <div class="alert alert-light border small">لا توجد أدوار بعد — عرّف دوراً أولاً من «الأدوار والنطاقات».</div>
                    @else
                        @foreach ($roles as $role)
                            @php $piv = $roleAssignments[$role->id] ?? null; @endphp
                            <div class="border rounded p-2 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input role-toggle" id="role_{{ $role->id }}"
                                           data-target="role_scopes_{{ $role->id }}"
                                           name="roles[{{ $role->id }}][enabled]" value="1"
                                           {{ ($piv !== null || old("roles.{$role->id}.enabled")) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="role_{{ $role->id }}">{{ $role->name }}</label>
                                    @if ($role->description)
                                        <div class="form-text small">{{ $role->description }}</div>
                                    @endif
                                </div>
                                <div class="row g-2 mt-1" id="role_scopes_{{ $role->id }}"
                                     style="{{ ($piv !== null || old("roles.{$role->id}.enabled")) ? '' : 'display:none;' }}">
                                    <div class="col-4">
                                        <select name="roles[{{ $role->id }}][center_id]" class="form-select form-select-sm" aria-label="مركز دور {{ $role->name }}">
                                            <option value="">🏢 كل المراكز</option>
                                            @foreach ($centers as $center)
                                                <option value="{{ $center->id }}"
                                                    {{ old("roles.{$role->id}.center_id", $piv?->pivot->center_id) == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select name="roles[{{ $role->id }}][project_id]" class="form-select form-select-sm" aria-label="مشروع دور {{ $role->name }}">
                                            <option value="">📌 كل المشاريع</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old("roles.{$role->id}.project_id", $piv?->pivot->project_id) == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select name="roles[{{ $role->id }}][cohort_id]" class="form-select form-select-sm" aria-label="فوج دور {{ $role->name }}">
                                            <option value="">🎓 كل الأفواج</option>
                                            @foreach ($cohorts as $co)
                                                <option value="{{ $co->id }}"
                                                    {{ old("roles.{$role->id}.cohort_id", $piv?->pivot->cohort_id) == $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif

                @if (isset($user))
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">نشط</label>
                    </div>
                    <div class="mb-3 form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        إذا قمت بإدخال كلمة مرور جديدة، سيُجبر المستخدم على تغييرها عند تسجيل الدخول التالي.
                    </div>
                @else
                    <div class="mb-3 alert alert-info py-2" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        سيتم إنشاء المستخدم <strong>غير نشط</strong> بشكل تلقائي ويجب عليه تغيير كلمة المرور عند أول تسجيل دخول.
                        يمكنك تفعيله لاحقاً من صفحة المستخدمين.
                    </div>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('userType');
    const linkSelects = document.querySelectorAll('.link-select');
    const employeeFields = document.querySelector('.employee-fields');

    function toggleLinkFields() {
        const selectedType = typeSelect.value;
        linkSelects.forEach(function (el) {
            el.style.display = el.dataset.type === selectedType ? 'block' : 'none';
        });
        if (employeeFields) {
            employeeFields.style.display = selectedType === 'employee' ? 'block' : 'none';
        }
    }

    typeSelect.addEventListener('change', toggleLinkFields);
    toggleLinkFields();

    document.querySelectorAll('.role-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var box = document.getElementById(cb.dataset.target);
            if (box) box.style.display = cb.checked ? '' : 'none';
        });
    });
});
</script>
@endpush
