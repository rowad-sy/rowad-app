@extends('admin.layouts.master')

@section('title', isset($employee) ? 'تعديل موظف' : 'إضافة موظف')

@push('styles')
<style>
    .inline-table { margin-bottom: 1rem; }
    .inline-table th { white-space: nowrap; }
    .tab-content { padding-top: 1.5rem; }
    .schedule-row td { vertical-align: middle; }

    .user-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        max-height: 220px;
        overflow-y: auto;
        background: var(--color-card);
        border: 1px solid var(--color-border);
        border-top: none;
        border-radius: 0 0 6px 6px;
        box-shadow: 0 4px 16px rgba(0,0,0,.12);
        display: none;
    }
    .user-search-results.show { display: block; }
    .user-search-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background .12s;
    }
    .user-search-item:last-child { border-bottom: none; }
    .user-search-item:hover,
    .user-search-item.highlighted { background: var(--color-hover); }
    .user-search-item.selected { background: var(--color-surface-muted); font-weight: 500; }
    .user-search-item small { color: #6c757d; }
    .user-search-clear {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        display: none;
        padding: 2px 8px;
        font-size: 20px;
        line-height: 1;
        z-index: 2;
    }
    .user-search-clear:hover { color: #dc3545; }
</style>
@endpush

@section('content')
<x-page-header :title="isset($employee) ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد'"
               :breadcrumb="[['label' => 'الموظفين', 'url' => route('admin.hr.employees.index')], ['label' => isset($employee) ? $employee->first_name_ar . ' ' . $employee->last_name_ar : 'جديد']]" />

@php
    // بعد فشل التحقق تُستعاد الصفوف من المُدخلات القديمة بمفاتيحها (حتى لو حذف المستخدم كل الصفوف)،
    // ولا تُقرأ من قاعدة البيانات إلا عند فتح الصفحة أول مرة (بلا محاولة إرسال سابقة).
    $attempted = session()->hasOldInput();
    $dynRows = function (string $key, $dbRows) use ($attempted) {
        if ($attempted) {
            return is_array(old($key)) ? old($key) : [];
        }
        return $dbRows;
    };
    $eduRows = $dynRows('educations', isset($employee) ? $employee->educations->map(fn ($e) => $e->only(['qualification', 'specialization', 'university', 'grade', 'graduation_year']))->all() : []);
    $contactRows = $dynRows('contacts', isset($employee) ? $employee->contacts->map(fn ($c) => ['type' => $c->type, 'value' => $c->value, 'is_primary' => (bool) $c->is_primary])->all() : []);
    // التنبيهات والملاحظات إضافية فقط: المحفوظة تُعرض للقراءة (Warning/EmployeeNote) والحقول للإضافات الجديدة وحدها
    $warningRows = $dynRows('warnings', []);
    $noteRows = $dynRows('notes_list', []);
    $savedWarnings = isset($employee) ? $employee->warnings : collect();
    $savedNotes = isset($employee) ? $employee->notesRelation : collect();
    $nextIdx = fn (array $rows) => $rows ? (max(array_map('intval', array_keys($rows))) + 1) : 0;
@endphp

<form method="POST" action="{{ isset($employee) ? route('admin.hr.employees.update', $employee) : route('admin.hr.employees.store') }}" enctype="multipart/form-data">
    @csrf
    @if (isset($employee))
        @method('PUT')
    @endif

    <p class="text-muted small mb-2">الحقول المميّزة بعلامة <span class="text-danger" aria-hidden="true">*</span> مطلوبة. التبويبات لا تفرض تسلسلًا: يمكن الحفظ من أي تبويب.</p>

    {{-- Tabs --}}
    <ul class="nav nav-tabs tabs-scroll" id="employeeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">المعلومات الأساسية</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="job-tab" data-bs-toggle="tab" data-bs-target="#job" type="button">الوظيفة والدوام</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="edu-tab" data-bs-toggle="tab" data-bs-target="#edu" type="button">المؤهلات والاتصال</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contract-tab" data-bs-toggle="tab" data-bs-target="#contract" type="button">العقد والراتب</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button">الملفات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="warn-tab" data-bs-toggle="tab" data-bs-target="#warn" type="button">التنبيهات والملاحظات</button>
        </li>
    </ul>

    <div class="tab-content" id="employeeTabsContent">
        {{-- TAB 1: BASIC INFO --}}
        <div class="tab-pane fade show active" id="basic" role="tabpanel">
            <div class="row g-3">
                {{-- User --}}
                <div class="col-md-4" id="userSelectWrapper">
                    <label class="form-label">المستخدم (اختياري)</label>
                    <input type="hidden" name="user_id" id="userId" value="{{ old('user_id', $employee->user_id ?? '') }}">
                    <div class="position-relative">
                        <input type="text" class="form-control" id="userSearchInput" placeholder="ابحث عن مستخدم..."
                               autocomplete="off"
                               value="{{ old('user_name', $employee->user->name ?? '') }}">
                        <button type="button" class="user-search-clear" id="userSearchClear">&times;</button>
                        <div class="user-search-results" id="userSearchResults">
                            <div class="user-search-item" data-value="">— بدون مستخدم —</div>
                            @foreach ($users as $user)
                                <div class="user-search-item" data-value="{{ $user->id }}">{{ $user->name }} <small>{{ $user->email }}</small></div>
                            @endforeach
                        </div>
                    </div>
                    @error('user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Employee Code --}}
                <div class="col-md-2">
                    <label class="form-label">كود الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                           value="{{ old('employee_code', $employee->employee_code ?? '') }}" required>
                    @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $employee->status ?? 'active') === 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status', $employee->status ?? '') === 'inactive' ? 'selected' : '' }}>غير فعال</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">رقم الهوية / جواز السفر</label>
                    <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror"
                           value="{{ old('id_number', $employee->id_number ?? '') }}">
                    @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Name AR --}}
                <div class="col-md-3">
                    <label class="form-label" for="f-first_name_ar">الاسم بالعربية <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(مطلوب)</span></label>
                    <input id="f-first_name_ar" type="text" name="first_name_ar" class="form-control @error('first_name_ar') is-invalid @enderror"
                                   value="{{ old('first_name_ar', $employee->first_name_ar ?? '') }}" placeholder="الاسم" required>
                    @error('first_name_ar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="f-last_name_ar">اللقب بالعربية <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(مطلوب)</span></label>
                    <input id="f-last_name_ar" type="text" name="last_name_ar" class="form-control @error('last_name_ar') is-invalid @enderror"
                                   value="{{ old('last_name_ar', $employee->last_name_ar ?? '') }}" placeholder="اللقب" required>
                    @error('last_name_ar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Name EN --}}
                <div class="col-md-3">
                    <label class="form-label" for="f-first_name_en">الاسم بالإنجليزية</label>
                    <input id="f-first_name_en" type="text" name="first_name_en" class="form-control @error('first_name_en') is-invalid @enderror"
                                   value="{{ old('first_name_en', $employee->first_name_en ?? '') }}" placeholder="First Name" dir="ltr">
                    @error('first_name_en') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="f-last_name_en">اللقب بالإنجليزية</label>
                    <input id="f-last_name_en" type="text" name="last_name_en" class="form-control @error('last_name_en') is-invalid @enderror"
                                   value="{{ old('last_name_en', $employee->last_name_en ?? '') }}" placeholder="Last Name" dir="ltr">
                    @error('last_name_en') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Parents --}}
                <div class="col-md-3">
                    <label class="form-label">اسم الأب AR</label>
                    <input type="text" name="father_name_ar" class="form-control"
                           value="{{ old('father_name_ar', $employee->father_name_ar ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">اسم الأب EN</label>
                    <input type="text" name="father_name_en" class="form-control"
                           value="{{ old('father_name_en', $employee->father_name_en ?? '') }}" dir="ltr">
                </div>
                <div class="col-md-3">
                    <label class="form-label">اسم الأم AR</label>
                    <input type="text" name="mother_name_ar" class="form-control"
                           value="{{ old('mother_name_ar', $employee->mother_name_ar ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">اسم الأم EN</label>
                    <input type="text" name="mother_name_en" class="form-control"
                           value="{{ old('mother_name_en', $employee->mother_name_en ?? '') }}" dir="ltr">
                </div>

                {{-- Personal details --}}
                <div class="col-md-2">
                    <label class="form-label">الجنس <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="male" {{ old('gender', $employee->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                        <option value="female" {{ old('gender', $employee->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
                    </select>
                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة الاجتماعية</label>
                    <select name="marital_status" class="form-select">
                        <option value="">اختر</option>
                        <option value="single" {{ old('marital_status', $employee->marital_status ?? '') === 'single' ? 'selected' : '' }}>أعزب</option>
                        <option value="married" {{ old('marital_status', $employee->marital_status ?? '') === 'married' ? 'selected' : '' }}>متزوج</option>
                        <option value="divorced" {{ old('marital_status', $employee->marital_status ?? '') === 'divorced' ? 'selected' : '' }}>مطلق</option>
                        <option value="widowed" {{ old('marital_status', $employee->marital_status ?? '') === 'widowed' ? 'selected' : '' }}>أرمل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">عدد الأولاد</label>
                    <input type="number" name="children_count" class="form-control" min="0"
                           value="{{ old('children_count', $employee->children_count ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تاريخ الميلاد</label>
                    <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                           value="{{ old('birth_date', $employee->birth_date ?? '') }}">
                    @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">مكان الميلاد</label>
                    <input type="text" name="birth_place" class="form-control"
                           value="{{ old('birth_place', $employee->birth_place ?? '') }}">
                </div>

                {{-- Nationality + Center + Department + Project --}}
                <div class="col-md-3">
                    <label class="form-label">الجنسية</label>
                    <input type="text" name="nationality" class="form-control"
                           value="{{ old('nationality', $employee->nationality ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">المركز</label>
                    <select name="center_id" class="form-select">
                        <option value="">اختر مركز</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" {{ old('center_id', $employee->center_id ?? $defaultCenterId ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الإدارة</label>
                    <select name="department_id" class="form-select">
                        <option value="">اختر إدارة</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select">
                        <option value="">اختر مشروع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $employee->project_id ?? $defaultProjectId ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $employee->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- TAB 2: JOB INFO + WORK SCHEDULES --}}
        <div class="tab-pane fade" id="job" role="tabpanel">
            <h5 class="fw-bold mb-3">أوقات الدوام</h5>
            <p class="text-muted small mb-3">حدد أوقات الدوام لكل يوم من أيام الأسبوع</p>

            @php
                $days = \App\Models\Admin\Hr\WorkSchedule::DAY_NAMES;   // الفهرس المرسَل 0=السبت ... 6=الجمعة
                $savedSchedules = isset($employee) ? $employee->workSchedules->keyBy('day_of_week') : collect([
                    0 => (object)['start_time' => null,    'end_time' => null,    'is_day_off' => true],
                    1 => (object)['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                    2 => (object)['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                    3 => (object)['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                    4 => (object)['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                    5 => (object)['start_time' => '08:00', 'end_time' => '16:00', 'is_day_off' => false],
                    6 => (object)['start_time' => null,    'end_time' => null,    'is_day_off' => true],
                ]);
            @endphp

            <div class="table-responsive">
                <table class="table table-bordered inline-table">
                    <thead>
                        <tr>
                            <th>اليوم</th>
                            <th>بداية الدوام</th>
                            <th>نهاية الدوام</th>
                            <th>إجازة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $i => $day)
                            @php $schedule = $savedSchedules->get($i); @endphp
                            <tr class="schedule-row">
                                <td class="fw-medium">{{ $day }}</td>
                                <td>
                                    <input type="time" name="work_schedules[{{ $i }}][start_time]" aria-label="بداية دوام {{ $day }}"
                                           class="form-control form-control-sm schedule-start"
                                           value="{{ $attempted ? old("work_schedules.$i.start_time") : ($schedule?->start_time ?? '') }}">
                                </td>
                                <td>
                                    <input type="time" name="work_schedules[{{ $i }}][end_time]" aria-label="نهاية دوام {{ $day }}"
                                           class="form-control form-control-sm schedule-end"
                                           value="{{ $attempted ? old("work_schedules.$i.end_time") : ($schedule?->end_time ?? '') }}">
                                </td>
                                <td class="text-center">
                                    <input type="hidden" name="work_schedules[{{ $i }}][day_of_week]" value="{{ $i }}">
                                    <input type="checkbox" name="work_schedules[{{ $i }}][is_day_off]" value="1" aria-label="{{ $day }} إجازة أسبوعية"
                                           class="form-check-input day-off-check"
                                           {{ ($attempted ? old("work_schedules.$i.is_day_off") : ($schedule?->is_day_off ?? false)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: EDUCATION + CONTACTS --}}
        <div class="tab-pane fade" id="edu" role="tabpanel">
            <h5 class="fw-bold mb-3">المؤهلات العلمية</h5>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="educations-table" data-next-index="{{ $nextIdx($eduRows) }}">
                    <thead>
                        <tr>
                            <th>المؤهل</th>
                            <th>الاختصاص</th>
                            <th>الجامعة</th>
                            <th>التقدير</th>
                            <th>سنة التخرج</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eduRows as $i => $row)
                            @include('admin.hr.employees.rows._education', ['idx' => $i, 'row' => $row])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="add-education">
                <i class="bi bi-plus-lg"></i> إضافة مؤهل
            </button>

            <hr>

            <h5 class="fw-bold mb-3">معلومات التواصل</h5>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="contacts-table" data-next-index="{{ $nextIdx($contactRows) }}">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>القيمة</th>
                            <th>رئيسي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contactRows as $i => $row)
                            @include('admin.hr.employees.rows._contact', ['idx' => $i, 'row' => $row])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-contact">
                <i class="bi bi-plus-lg"></i> إضافة وسيلة اتصال
            </button>
        </div>

        {{-- TAB 4: CONTRACT + SALARY --}}
        <div class="tab-pane fade" id="contract" role="tabpanel">
            <h5 class="fw-bold mb-3">العقد</h5>
            @php $contract = isset($employee) ? $employee->contracts->first() : null; @endphp
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">نوع العقد</label>
                    <select name="contracts[0][contract_type]" class="form-select">
                        <option value="">اختر</option>
                        <option value="permanent" {{ old('contracts.0.contract_type', $contract->contract_type ?? '') === 'permanent' ? 'selected' : '' }}>دائم</option>
                        <option value="temporary" {{ old('contracts.0.contract_type', $contract->contract_type ?? '') === 'temporary' ? 'selected' : '' }}>مؤقت</option>
                        <option value="seasonal" {{ old('contracts.0.contract_type', $contract->contract_type ?? '') === 'seasonal' ? 'selected' : '' }}>موسمي</option>
                        <option value="probation" {{ old('contracts.0.contract_type', $contract->contract_type ?? '') === 'probation' ? 'selected' : '' }}>تجريبي</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المنصب الوظيفي</label>
                    <select name="contracts[0][job_position_id]" class="form-select">
                        <option value="">اختر</option>
                        @foreach ($positions as $pos)
                            <option value="{{ $pos->id }}" {{ old('contracts.0.job_position_id', $contract->job_position_id ?? '') == $pos->id ? 'selected' : '' }}>{{ $pos->title_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">تاريخ المباشرة</label>
                    <input type="date" name="contracts[0][start_date]" class="form-control form-control-sm"
                           value="{{ old('contracts.0.start_date', $contract->start_date ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بداية العقد</label>
                    <input type="date" name="contracts[0][contract_start]" class="form-control form-control-sm"
                           value="{{ old('contracts.0.contract_start', $contract->contract_start ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">نهاية العقد</label>
                    <input type="date" name="contracts[0][contract_end]" class="form-control form-control-sm"
                           value="{{ old('contracts.0.contract_end', $contract->contract_end ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">تاريخ ترك العمل</label>
                    <input type="date" name="contracts[0][leave_date]" class="form-control form-control-sm"
                           value="{{ old('contracts.0.leave_date', $contract->leave_date ?? '') }}">
                </div>
            </div>

            <hr>

            <h5 class="fw-bold mb-3">الراتب</h5>
            @php $salary = isset($employee) ? $employee->salaries->first() : null; @endphp
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">العملة</label>
                    <select name="salaries[0][currency]" class="form-select">
                        <option value="SYP" {{ old('salaries.0.currency', $salary->currency ?? 'SYP') === 'SYP' ? 'selected' : '' }}>ل.س</option>
                        <option value="USD" {{ old('salaries.0.currency', $salary->currency ?? '') === 'USD' ? 'selected' : '' }}>$</option>
                        <option value="EUR" {{ old('salaries.0.currency', $salary->currency ?? '') === 'EUR' ? 'selected' : '' }}>€</option>
                        <option value="TRY" {{ old('salaries.0.currency', $salary->currency ?? '') === 'TRY' ? 'selected' : '' }}>₺</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">وحدة الراتب</label>
                    <input type="text" name="salaries[0][salary_unit]" class="form-control"
                           value="{{ old('salaries.0.salary_unit', $salary->salary_unit ?? '') }}" placeholder="شهري">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الراتب الأساسي</label>
                    <input type="number" name="salaries[0][base_salary]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.base_salary', $salary->base_salary ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">تعويض دراسات</label>
                    <input type="number" name="salaries[0][study_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.study_allowance', $salary->study_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">تعويض زواج</label>
                    <input type="number" name="salaries[0][marriage_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.marriage_allowance', $salary->marriage_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل خبرة</label>
                    <input type="number" name="salaries[0][experience_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.experience_allowance', $salary->experience_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل نقل</label>
                    <input type="number" name="salaries[0][transport_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.transport_allowance', $salary->transport_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل طعام</label>
                    <input type="number" name="salaries[0][food_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.food_allowance', $salary->food_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل سكن</label>
                    <input type="number" name="salaries[0][housing_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.housing_allowance', $salary->housing_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل هاتف</label>
                    <input type="number" name="salaries[0][mobile_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.mobile_allowance', $salary->mobile_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بدل خطورة</label>
                    <input type="number" name="salaries[0][risk_allowance]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.risk_allowance', $salary->risk_allowance ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">قيمة ساعة إضافية</label>
                    <input type="number" name="salaries[0][overtime_rate]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.overtime_rate', $salary->overtime_rate ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">خصومات</label>
                    <input type="number" name="salaries[0][deduction]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.deduction', $salary->deduction ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الراتب الإجمالي</label>
                    <input type="number" name="salaries[0][total_salary]" step="0.01" class="form-control"
                           value="{{ old('salaries.0.total_salary', $salary->total_salary ?? 0) }}">
                </div>
            </div>
        </div>

        {{-- TAB 5: DOCUMENTS --}}
        <div class="tab-pane fade" id="docs" role="tabpanel">
            <h5 class="fw-bold mb-3">الوثائق والملفات</h5>
            @if ($attempted)
                <div class="alert alert-info py-2 small" role="note">المتصفح لا يحتفظ بالملفات المختارة بعد فشل الحفظ؛ أعد اختيار أي ملف تريد رفعه.</div>
            @endif
            <p class="text-muted small mb-3">حدد أنواع الوثائق الموجودة وارفع الملفات</p>

            @php
                $docFields = [
                    'has_photo' => 'صورة شخصية',
                    'has_cv' => 'السيرة الذاتية',
                    'has_id_copy' => 'الهوية الشخصية',
                    'has_qualification' => 'المؤهل العلمي',
                    'has_experience_certs' => 'شهادات الخبرة',
                    'has_offer_letter' => 'عرض العمل',
                    'has_contract_doc' => 'عقد العمل',
                    'has_employee_data' => 'بيانات الموظف',
                    'has_job_description' => 'التوصيف الوظيفي',
                    'has_signature_movements' => 'حركات توقيع الموظف',
                    'has_security_audit' => 'التدقيق الأمني',
                    'has_reference_audit' => 'تدقيق المراجع',
                    'has_code_of_conduct' => 'مدونة السلوك',
                    'has_clearance' => 'مخالصة وبراءة ذمة',
                    'has_receipt' => 'استلام عهدة',
                    'has_resignation' => 'استقالة / إنهاء عقد',
                    'has_verbal_warning_doc' => 'تنبيه شفهي',
                    'has_written_warning_doc' => 'تنبيه خطي',
                    'has_termination_warning_doc' => 'إنذار بالفصل',
                    'has_termination_doc' => 'فصل من العمل',
                    'has_blacklist_doc' => 'القائمة السوداء',
                ];
            @endphp

            <div class="row g-3">
                @foreach ($docFields as $field => $label)
                    <div class="col-md-4">
                        <div class="form-card p-3">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" id="{{ $field }}"
                                       {{ old($field, $employee->$field ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="{{ $field }}">{{ $label }}</label>
                            </div>
                            <input type="file" name="documents[{{ $field }}]" class="form-control form-control-sm">
                            @if (isset($employee) && $employee->documents->where('document_type', $field)->count())
                                <div class="mt-1">
                                    @foreach ($employee->documents->where('document_type', $field) as $doc)
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="small text-decoration-none">
                                            <i class="bi bi-paperclip"></i> {{ $doc->original_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 6: WARNINGS + NOTES --}}
        <div class="tab-pane fade" id="warn" role="tabpanel">
            <h5 class="fw-bold mb-1">التنبيهات</h5>
            <p class="text-muted small mb-2">التنبيهات المسجّلة للقراءة فقط ولا تُعدَّل أو تُكرَّر عند الحفظ؛ استخدم «إضافة تنبيه» لتنبيه جديد.</p>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="warnings-table" data-next-index="{{ $nextIdx($warningRows) }}">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>السبب</th>
                            <th>المستوى</th>
                            <th>مطوي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($savedWarnings as $warning)
                            @include('admin.hr.employees.rows._warning_saved', ['warning' => $warning])
                        @endforeach
                        @foreach ($warningRows as $i => $row)
                            @include('admin.hr.employees.rows._warning', ['idx' => $i, 'row' => $row])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="add-warning">
                <i class="bi bi-plus-lg"></i> إضافة تنبيه
            </button>

            <hr>

            <h5 class="fw-bold mb-1">الملاحظات</h5>
            <p class="text-muted small mb-2">الملاحظات المسجّلة للقراءة فقط بصاحبها وتاريخها؛ استخدم «إضافة ملاحظة» لملاحظة جديدة.</p>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="notes-table" data-next-index="{{ $nextIdx($noteRows) }}">
                    <thead>
                        <tr>
                            <th>الملاحظة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($savedNotes as $note)
                            @include('admin.hr.employees.rows._note_saved', ['note' => $note])
                        @endforeach
                        @foreach ($noteRows as $i => $row)
                            @include('admin.hr.employees.rows._note', ['idx' => $i, 'row' => $row])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-note">
                <i class="bi bi-plus-lg"></i> إضافة ملاحظة
            </button>
        </div>
    </div>

    <div class="d-grid d-sm-flex gap-2 mt-4 mb-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ
        </button>
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary px-4">إلغاء</a>
    </div>
</form>

{{-- قوالب الصفوف الجديدة: نفس الأجزاء المستخدمة لصفوف الخادم، فتحمل التسميات وaria من لحظة الإنشاء --}}
<template id="tpl-educations">@include('admin.hr.employees.rows._education', ['idx' => '__IDX__', 'row' => []])</template>
<template id="tpl-contacts">@include('admin.hr.employees.rows._contact', ['idx' => '__IDX__', 'row' => []])</template>
<template id="tpl-warnings">@include('admin.hr.employees.rows._warning', ['idx' => '__IDX__', 'row' => []])</template>
<template id="tpl-notes">@include('admin.hr.employees.rows._note', ['idx' => '__IDX__', 'row' => []])</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Day off toggle — disable time inputs
    document.querySelectorAll('.day-off-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var row = this.closest('tr');
            row.querySelector('.schedule-start').disabled = this.checked;
            row.querySelector('.schedule-end').disabled = this.checked;
            if (this.checked) {
                row.querySelector('.schedule-start').value = '';
                row.querySelector('.schedule-end').value = '';
            }
        });
        if (cb.checked) cb.dispatchEvent(new Event('change'));
    });

    // حذف صف (حتى الأخير): بعد فشل التحقق لا تعود الصفوف المحذوفة من قاعدة البيانات
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-row');
        if (!btn) return;
        var row = btn.closest('tr');
        var next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        if (next) { var f = next.querySelector('input, select, textarea, button'); if (f) f.focus(); }
    });

    // إضافة صف من قالب الخادم بمفتاح جديد لا يصطدم بالمفاتيح الموجودة (data-next-index)
    function addDynamicRow(tableId, templateId) {
        var table = document.getElementById(tableId);
        var tbody = table.querySelector('tbody');
        var idx = parseInt(table.dataset.nextIndex || '0', 10);
        table.dataset.nextIndex = idx + 1;
        var html = document.getElementById(templateId).innerHTML.split('__IDX__').join(idx);
        tbody.insertAdjacentHTML('beforeend', html);
        var first = tbody.lastElementChild.querySelector('input:not([type=checkbox]), select, textarea');
        if (first) first.focus();
    }
    [['add-education', 'educations-table', 'tpl-educations'], ['add-contact', 'contacts-table', 'tpl-contacts'],
     ['add-warning', 'warnings-table', 'tpl-warnings'], ['add-note', 'notes-table', 'tpl-notes']].forEach(function (c) {
        document.getElementById(c[0])?.addEventListener('click', function () { addDynamicRow(c[1], c[2]); });
    });

    // ---- User search ----
    (function() {
        var input = document.getElementById('userSearchInput');
        if (!input) return;
        var hidden = document.getElementById('userId');
        var results = document.getElementById('userSearchResults');
        var clearBtn = document.getElementById('userSearchClear');
        var items = results.querySelectorAll('.user-search-item');
        var selectedVal = hidden.value;

        function selectItem(item) {
            items.forEach(function(i) { i.classList.remove('selected'); });
            item.classList.add('selected');
            selectedVal = item.dataset.value;
            hidden.value = selectedVal;
            input.value = item.textContent.trim().replace(/\s+/g, ' ').split(' <')[0];
            clearBtn.style.display = 'block';
        }

        // Pre-select
        items.forEach(function(item) {
            if (item.dataset.value === selectedVal) {
                item.classList.add('selected');
                input.value = item.textContent.trim().replace(/\s+/g, ' ').split(' <')[0];
                clearBtn.style.display = 'block';
            }
        });

        input.addEventListener('focus', function () {
            results.classList.add('show');
            filterItems(this.value);
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('userSelectWrapper').contains(e.target)) {
                results.classList.remove('show');
            }
        });

        input.addEventListener('input', function () {
            filterItems(this.value);
            results.classList.add('show');
            clearBtn.style.display = this.value ? 'block' : 'none';
        });

        function filterItems(q) {
            q = q.toLowerCase().trim();
            items.forEach(function (item) {
                item.style.display = (!q || item.textContent.toLowerCase().includes(q)) ? 'block' : 'none';
            });
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                selectItem(this);
                results.classList.remove('show');
            });
        });

        clearBtn.addEventListener('click', function () {
            hidden.value = '';
            input.value = '';
            items.forEach(function (i) { i.classList.remove('selected'); });
            clearBtn.style.display = 'none';
            input.focus();
            filterItems('');
            results.classList.add('show');
        });

        input.addEventListener('keydown', function (e) {
            var visible = Array.from(items).filter(function (i) { return i.style.display !== 'none'; });
            if (!visible.length) return;
            var hl = results.querySelector('.highlighted');
            var idx = hl ? visible.indexOf(hl) : -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var next = (idx + 1) % visible.length;
                if (hl) hl.classList.remove('highlighted');
                visible[next].classList.add('highlighted');
                visible[next].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var prev = (idx - 1 + visible.length) % visible.length;
                if (hl) hl.classList.remove('highlighted');
                visible[prev].classList.add('highlighted');
                visible[prev].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (hl) { selectItem(hl); results.classList.remove('show'); }
            } else if (e.key === 'Escape') {
                results.classList.remove('show');
            }
        });
    })();
});
</script>
@endpush
