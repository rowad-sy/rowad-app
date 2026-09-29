@extends('admin.layouts.master')

@section('title', 'التايم شيت')

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
    {{-- مفتاح الرموز: الحالة تُقرأ بالنص/الرمز وليس باللون وحده (يطابق رموز صفحة الطباعة) --}}
    <span class="small text-muted">المفتاح: ✔ حاضر &nbsp; ✘ غائب &nbsp; ع غياب بعذر &nbsp; — عطلة الموظف &nbsp; ؟ غير مسجّل</span>
</div>

@if ($hasFilter)
    {{-- نفس صفحة الطباعة الحالية داخل الصفحة، بالفلاتر المختارة (لا منطق حساب جديد) --}}
    <div class="table-container">
        <iframe src="{{ route('admin.hr.timesheets.print', request()->query()) }}" title="التايم شيت لشهر {{ $monthLabel }}"
                loading="lazy" style="width:100%; height:70vh; border:0; background:#fff;"></iframe>
    </div>
@else
    <div class="table-container">
        <x-empty-state icon="bi-hourglass-split" title="اختر مركزًا أو مشروعًا أو قسمًا أو اسم موظف"
                       hint="ثم اضغط «تطبيق» لعرض التايم شيت لشهر {{ $monthLabel }}." />
    </div>
@endif
@endsection
