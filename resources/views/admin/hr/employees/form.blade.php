@extends('admin.layouts.master')

@section('title', isset($employee) ? 'تعديل موظف' : 'إضافة موظف')

@push('styles')
<style>
    .inline-table { margin-bottom: 1rem; }
    .inline-table th { white-space: nowrap; }
    .tab-content { padding-top: 1.5rem; }
    .schedule-row td { vertical-align: middle; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h4>{{ isset($employee) ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد' }}</h4>
    <p>
        <a href="{{ route('admin.hr.employees.index') }}" class="text-decoration-none">الموظفين</a>
        / {{ isset($employee) ? $employee->first_name_ar . ' ' . $employee->last_name_ar : 'جديد' }}
    </p>
</div>

<form method="POST" action="{{ isset($employee) ? route('admin.hr.employees.update', $employee) : route('admin.hr.employees.store') }}" enctype="multipart/form-data">
    @csrf
    @if (isset($employee))
        @method('PUT')
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
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
                <div class="col-md-3">
                    <label class="form-label">كود الموظف <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                           value="{{ old('employee_code', $employee->employee_code ?? '') }}" required>
                    @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $employee->status ?? 'active') === 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status', $employee->status ?? '') === 'inactive' ? 'selected' : '' }}>غير فعال</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">رقم الهوية / جواز السفر</label>
                    <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror"
                           value="{{ old('id_number', $employee->id_number ?? '') }}">
                    @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">الجنس <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="male" {{ old('gender', $employee->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                        <option value="female" {{ old('gender', $employee->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
                    </select>
                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">الاسم AR <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" name="first_name_ar" class="form-control @error('first_name_ar') is-invalid @enderror"
                                   value="{{ old('first_name_ar', $employee->first_name_ar ?? '') }}" placeholder="الاسم" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="last_name_ar" class="form-control @error('last_name_ar') is-invalid @enderror"
                                   value="{{ old('last_name_ar', $employee->last_name_ar ?? '') }}" placeholder="اللقب" required>
                        </div>
                    </div>
                    @error('first_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @error('last_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">الاسم EN</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" name="first_name_en" class="form-control @error('first_name_en') is-invalid @enderror"
                                   value="{{ old('first_name_en', $employee->first_name_en ?? '') }}" placeholder="First Name" dir="ltr">
                        </div>
                        <div class="col-6">
                            <input type="text" name="last_name_en" class="form-control @error('last_name_en') is-invalid @enderror"
                                   value="{{ old('last_name_en', $employee->last_name_en ?? '') }}" placeholder="Last Name" dir="ltr">
                        </div>
                    </div>
                </div>

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

                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label">مكان الميلاد</label>
                    <input type="text" name="birth_place" class="form-control"
                           value="{{ old('birth_place', $employee->birth_place ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الجنسية</label>
                    <input type="text" name="nationality" class="form-control"
                           value="{{ old('nationality', $employee->nationality ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">المركز</label>
                    <select name="center_id" class="form-select">
                        <option value="">اختر مركز</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" {{ old('center_id', $employee->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الإدارة</label>
                    <select name="department_id" class="form-select">
                        <option value="">اختر إدارة</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select">
                        <option value="">اختر مشروع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $employee->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
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
                $days = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
                $savedSchedules = isset($employee) ? $employee->workSchedules->keyBy('day_of_week') : collect();
            @endphp

            <div class="table-responsive">
                <table class="table table-bordered inline-table">
                    <thead class="table-light">
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
                                    <input type="time" name="work_schedules[{{ $i }}][start_time]"
                                           class="form-control form-control-sm schedule-start"
                                           value="{{ old("work_schedules.$i.start_time", $schedule?->start_time ?? '') }}">
                                </td>
                                <td>
                                    <input type="time" name="work_schedules[{{ $i }}][end_time]"
                                           class="form-control form-control-sm schedule-end"
                                           value="{{ old("work_schedules.$i.end_time", $schedule?->end_time ?? '') }}">
                                </td>
                                <td class="text-center">
                                    <input type="hidden" name="work_schedules[{{ $i }}][day_of_week]" value="{{ $i }}">
                                    <input type="checkbox" name="work_schedules[{{ $i }}][is_day_off]" value="1"
                                           class="form-check-input day-off-check"
                                           {{ old("work_schedules.$i.is_day_off", $schedule?->is_day_off ?? false) ? 'checked' : '' }}>
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
                <table class="table table-bordered inline-table" id="educations-table">
                    <thead class="table-light">
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
                        @php $eduIndex = 0; @endphp
                        @if (isset($employee) && $employee->educations->count())
                            @foreach ($employee->educations as $edu)
                            <tr>
                                <td><input type="text" name="educations[{{ $loop->index }}][qualification]" class="form-control form-control-sm" value="{{ $edu->qualification }}"></td>
                                <td><input type="text" name="educations[{{ $loop->index }}][specialization]" class="form-control form-control-sm" value="{{ $edu->specialization }}"></td>
                                <td><input type="text" name="educations[{{ $loop->index }}][university]" class="form-control form-control-sm" value="{{ $edu->university }}"></td>
                                <td><input type="text" name="educations[{{ $loop->index }}][grade]" class="form-control form-control-sm" value="{{ $edu->grade }}"></td>
                                <td><input type="number" name="educations[{{ $loop->index }}][graduation_year]" class="form-control form-control-sm" value="{{ $edu->graduation_year }}"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                            @php $eduIndex = $loop->index + 1; @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="add-education">
                <i class="bi bi-plus-lg"></i> إضافة مؤهل
            </button>

            <hr>

            <h5 class="fw-bold mb-3">معلومات التواصل</h5>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="contacts-table">
                    <thead class="table-light">
                        <tr>
                            <th>النوع</th>
                            <th>القيمة</th>
                            <th>رئيسي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $contactIndex = 0; @endphp
                        @if (isset($employee) && $employee->contacts->count())
                            @foreach ($employee->contacts as $contact)
                            <tr>
                                <td>
                                    <select name="contacts[{{ $loop->index }}][type]" class="form-select form-select-sm">
                                        <option value="phone" {{ $contact->type === 'phone' ? 'selected' : '' }}>هاتف</option>
                                        <option value="mobile" {{ $contact->type === 'mobile' ? 'selected' : '' }}>جوال</option>
                                        <option value="whatsapp" {{ $contact->type === 'whatsapp' ? 'selected' : '' }}>واتسآب</option>
                                        <option value="emergency" {{ $contact->type === 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                        <option value="email" {{ $contact->type === 'email' ? 'selected' : '' }}>بريد إلكتروني</option>
                                    </select>
                                </td>
                                <td><input type="text" name="contacts[{{ $loop->index }}][value]" class="form-control form-control-sm" value="{{ $contact->value }}"></td>
                                <td class="text-center">
                                    <input type="checkbox" name="contacts[{{ $loop->index }}][is_primary]" value="1" class="form-check-input"
                                           {{ $contact->is_primary ? 'checked' : '' }}>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                            @php $contactIndex = $loop->index + 1; @endphp
                            @endforeach
                        @endif
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
            <h5 class="fw-bold mb-3">التنبيهات</h5>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="warnings-table">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>السبب</th>
                            <th>المستوى</th>
                            <th>مطوي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $warnIndex = 0; @endphp
                        @if (isset($employee) && $employee->warnings->count())
                            @foreach ($employee->warnings as $warn)
                            <tr>
                                <td><input type="date" name="warnings[{{ $loop->index }}][date]" class="form-control form-control-sm" value="{{ $warn->date }}"></td>
                                <td><input type="text" name="warnings[{{ $loop->index }}][reason]" class="form-control form-control-sm" value="{{ $warn->reason }}"></td>
                                <td>
                                    <select name="warnings[{{ $loop->index }}][level]" class="form-select form-select-sm">
                                        <option value="verbal" {{ $warn->level === 'verbal' ? 'selected' : '' }}>شفهي</option>
                                        <option value="written" {{ $warn->level === 'written' ? 'selected' : '' }}>كتابي</option>
                                        <option value="termination" {{ $warn->level === 'termination' ? 'selected' : '' }}>إنذار بالفصل</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    @if ($warn->is_folded)
                                        <span class="badge bg-success">مطوي</span>
                                    @else
                                        <span class="badge bg-warning">غير مطوي</span>
                                    @endif
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                            @php $warnIndex = $loop->index + 1; @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="add-warning">
                <i class="bi bi-plus-lg"></i> إضافة تنبيه
            </button>

            <hr>

            <h5 class="fw-bold mb-3">الملاحظات</h5>
            <div class="table-responsive">
                <table class="table table-bordered inline-table" id="notes-table">
                    <thead class="table-light">
                        <tr>
                            <th>الملاحظة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($employee) && $employee->notesRelation->count())
                            @foreach ($employee->notesRelation as $note)
                            <tr>
                                <td>
                                    <textarea name="notes_list[{{ $loop->index }}][note]" rows="2" class="form-control form-control-sm">{{ $note->note }}</textarea>
                                    <small class="text-muted">{{ $note->user?->name }} - {{ $note->created_at->locale('ar')->diffForHumans() }}</small>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-note">
                <i class="bi bi-plus-lg"></i> إضافة ملاحظة
            </button>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
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

    // Add row helper
    function addRow(tableId, prefix, fields) {
        var tbody = document.querySelector('#' + tableId + ' tbody');
        var rowCount = tbody.querySelectorAll('tr').length;
        var html = '<tr>';
        fields.forEach(function (f) {
            html += '<td>' + f + '</td>';
        });
        html += '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>';
        html += '</tr>';
        // Replace index placeholder
        html = html.replace(/INDEX/g, rowCount);
        tbody.insertAdjacentHTML('beforeend', html);
    }

    // Remove row
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            if (e.target.closest('tbody').querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
            } else {
                e.target.closest('tr').querySelectorAll('input, textarea').forEach(function (el) {
                    if (el.type !== 'button') el.value = '';
                });
            }
        }
    });

    // Add education
    document.getElementById('add-education')?.addEventListener('click', function () {
        var tbody = document.querySelector('#educations-table tbody');
        var idx = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td><input type="text" name="educations[` + idx + `][qualification]" class="form-control form-control-sm" placeholder="المؤهل"></td>
                <td><input type="text" name="educations[` + idx + `][specialization]" class="form-control form-control-sm" placeholder="الاختصاص"></td>
                <td><input type="text" name="educations[` + idx + `][university]" class="form-control form-control-sm" placeholder="الجامعة"></td>
                <td><input type="text" name="educations[` + idx + `][grade]" class="form-control form-control-sm" placeholder="التقدير"></td>
                <td><input type="number" name="educations[` + idx + `][graduation_year]" class="form-control form-control-sm" placeholder="السنة"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
        `);
    });

    // Add contact
    document.getElementById('add-contact')?.addEventListener('click', function () {
        var tbody = document.querySelector('#contacts-table tbody');
        var idx = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <select name="contacts[` + idx + `][type]" class="form-select form-select-sm">
                        <option value="phone">هاتف</option>
                        <option value="mobile">جوال</option>
                        <option value="whatsapp">واتسآب</option>
                        <option value="emergency">طوارئ</option>
                        <option value="email">بريد إلكتروني</option>
                    </select>
                </td>
                <td><input type="text" name="contacts[` + idx + `][value]" class="form-control form-control-sm" placeholder="القيمة"></td>
                <td class="text-center"><input type="checkbox" name="contacts[` + idx + `][is_primary]" value="1" class="form-check-input"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
        `);
    });

    // Add warning
    document.getElementById('add-warning')?.addEventListener('click', function () {
        var tbody = document.querySelector('#warnings-table tbody');
        var idx = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td><input type="date" name="warnings[` + idx + `][date]" class="form-control form-control-sm"></td>
                <td><input type="text" name="warnings[` + idx + `][reason]" class="form-control form-control-sm" placeholder="السبب"></td>
                <td>
                    <select name="warnings[` + idx + `][level]" class="form-select form-select-sm">
                        <option value="verbal">شفهي</option>
                        <option value="written">كتابي</option>
                        <option value="termination">إنذار بالفصل</option>
                    </select>
                </td>
                <td class="text-center">—</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
        `);
    });

    // Add note
    document.getElementById('add-note')?.addEventListener('click', function () {
        var tbody = document.querySelector('#notes-table tbody');
        var idx = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <textarea name="notes_list[` + idx + `][note]" rows="2" class="form-control form-control-sm" placeholder="ملاحظة"></textarea>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
        `);
    });
});
</script>
@endpush
