@extends('admin.layouts.master')

@section('title', 'التايم شيت')

@push('styles')
<style>
    .ts-table { font-size: 0.85rem; }
    .ts-table th, .ts-table td { text-align: center; white-space: nowrap; }
    .ts-table th.ts-employee { text-align: start; white-space: normal; min-width: 150px; max-width: 200px; position: sticky; inset-inline-start: 0; z-index: 2; background: var(--color-card); }
    .ts-table thead th.ts-employee { background: var(--color-surface-muted); }
    .ts-table .ts-day { min-width: 34px; padding: 0.25rem 0.15rem; }
    .ts-table .ts-dayname { display: block; font-size: 0.65rem; font-weight: 400; color: var(--color-text-muted); }
    .ts-table .ts-weekend { background: var(--status-neutral-bg); }
    .ts-table .ts-cell { font-weight: 700; }
    .ts-table .ts-present { color: var(--status-success); background: var(--status-success-bg); }
    .ts-table .ts-absent { color: var(--status-danger); background: var(--status-danger-bg); }
    .ts-table .ts-excused { color: var(--status-warning); background: var(--status-warning-bg); }
    .ts-table .ts-off { color: var(--status-neutral); background: var(--status-neutral-bg); }
    .ts-table .ts-details { white-space: normal; min-width: 140px; text-align: start; }
    .ts-table .ts-total { min-width: 56px; }
    @media (max-width: 575.98px) { .ts-table th.ts-employee { min-width: 120px; max-width: 130px; } .ts-table .ts-employee .small { font-size: 0.72rem; } }
</style>
@endpush

@section('content')
@php
    $hasFilter = $search || $centerId || $projectId || $departmentId;
    $monthLabel = \Carbon\Carbon::parse($month . '-01')->locale('ar')->translatedFormat('F Y');
@endphp
<x-page-header title="التايم شيت" description="سجل حضور وغياب الموظفين الشهري"
               :breadcrumb="[['label' => 'الموارد البشرية'], ['label' => 'التايم شيت']]">
    <a href="{{ route('admin.hr.timesheets.print', request()->query()) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
        <i class="bi bi-printer me-1" aria-hidden="true"></i> فتح للطباعة
    </a>
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-month">الشهر</label>
            <input type="month" id="f-month" name="month" class="form-control" value="{{ $month }}">
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-center_id">المركز</label>
            <select id="f-center_id" name="center_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ $centerId == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-project_id">المشروع</label>
            <select id="f-project_id" name="project_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 filter-field">
            <label class="form-label" for="f-department_id">القسم</label>
            <select id="f-department_id" name="department_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="search" id="f-search" name="search" class="form-control" placeholder="اسم الموظف..." value="{{ $search }}">
        </div>
    </x-filter-bar>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <x-status-badge tone="brand"><i class="bi bi-calendar3" aria-hidden="true"></i> الفترة: {{ $monthLabel }}</x-status-badge>
    {{-- مفتاح الرموز: الحالة تُقرأ بالرمز والنص المتاح للقارئ وليس باللون وحده (يطابق رموز صفحة الطباعة) --}}
    <span class="small text-muted">المفتاح: ✔ حاضر (يشمل اليوم غير المسجَّل، ويُحتسب حاضرًا افتراضيًا) · ✘ غائب · ع غياب بعذر · — عطلة الموظف</span>
</div>

@if ($hasFilter)
    @php
        $timesheets = $sheet['timesheets'];
        $daysInMonth = $sheet['daysInMonth'];
        $symbols = [
            'present' => ['✔', 'حاضر', 'ts-present'],
            'absent' => ['✘', 'غائب', 'ts-absent'],
            'excused' => ['ع', 'غياب بعذر', 'ts-excused'],
            'off' => ['—', 'عطلة', 'ts-off'],
        ];
        $headDays = [];
        foreach (($timesheets[0]['daily'] ?? []) as $d) {
            $headDays[] = $d + ['weekend' => in_array(\Carbon\Carbon::parse($d['date'])->dayOfWeek, [5, 6], true)];
        }
    @endphp
    <div class="table-container">
        @if (count($timesheets))
            <div class="table-responsive" tabindex="0" role="region" aria-label="جدول التايم شيت لشهر {{ $monthLabel }}">
                <table class="table table-bordered table-sm align-middle mb-0 ts-table">
                    <caption class="visually-hidden">التايم شيت لشهر {{ $monthLabel }}: حالة كل يوم لكل موظف وإجماليات الحضور والغياب والغياب بعذر</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="ts-employee">الموظف</th>
                            @foreach ($headDays as $d)
                                <th scope="col" class="ts-day {{ $d['weekend'] ? 'ts-weekend' : '' }}">
                                    <span class="num">{{ $d['day'] }}</span>
                                    <span class="ts-dayname">{{ $d['day_name'] }}</span>
                                </th>
                            @endforeach
                            <th scope="col" class="ts-total">حضور</th>
                            <th scope="col" class="ts-total">غياب</th>
                            <th scope="col" class="ts-total">بعذر</th>
                            <th scope="col" class="ts-details">ملخص الإجازات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($timesheets as $ts)
                            <tr>
                                <th scope="row" class="ts-employee">
                                    <div class="fw-bold">{{ $ts['employee']->first_name_ar }} {{ $ts['employee']->last_name_ar }}</div>
                                    <div class="small text-muted"><span class="ltr-cell d-inline-block">{{ $ts['employee']->employee_code }}</span>@if ($ts['position']) · {{ $ts['position'] }}@endif</div>
                                    @if ($ts['employee']->center)<div class="small text-muted">{{ $ts['employee']->center->name }}</div>@endif
                                </th>
                                @foreach ($ts['daily'] as $day)
                                    @php
                                        $key = $day['is_off'] ? 'off' : (isset($symbols[$day['status']]) ? $day['status'] : null);
                                        [$sym, $label, $cls] = $key ? $symbols[$key] : ['؟', 'غير معروف', 'ts-unknown'];
                                    @endphp
                                    <td class="ts-cell {{ $cls }}" title="{{ $day['day'] }} {{ $monthLabel }}: {{ $label }}{{ $key === 'present' && ! $day['recorded'] ? ' (افتراضي: غير مسجّل)' : '' }}">
                                        <span aria-hidden="true">{{ $sym }}</span><span class="visually-hidden">{{ $day['day'] }}: {{ $label }}</span>
                                    </td>
                                @endforeach
                                <td class="ts-total ts-present num fw-bold">{{ $ts['total_present'] }}</td>
                                <td class="ts-total ts-absent num fw-bold">{{ $ts['total_absent'] }}</td>
                                <td class="ts-total ts-excused num fw-bold">{{ $ts['total_excused'] }}</td>
                                <td class="ts-details small">{{ $ts['leave_details'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state icon="bi-search" title="لا يوجد موظفون نشطون يطابقون الفلاتر"
                           hint="جرّب تعديل الفلاتر أو مسحها.">
                <a href="{{ route('admin.hr.timesheets.index', ['month' => $month]) }}" class="btn btn-sm btn-outline-secondary mt-2">مسح الفلاتر</a>
            </x-empty-state>
        @endif
    </div>
@else
    <div class="table-container">
        <x-empty-state icon="bi-hourglass-split" title="اختر مركزًا أو مشروعًا أو قسمًا أو اسم موظف"
                       hint="ثم اضغط «تطبيق» لعرض التايم شيت لشهر {{ $monthLabel }}." />
    </div>
@endif
@endsection
