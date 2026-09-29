@extends('admin.layouts.master')

@section('title', $plan->name_ar)

@php
    $days = [1 => 'السبت', 2 => 'الأحد', 3 => 'الاثنين', 4 => 'الثلاثاء', 5 => 'الأربعاء', 6 => 'الخميس', 7 => 'الجمعة'];
@endphp

@section('content')
<x-page-header :title="$plan->name_ar"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الخطط التدريبية', 'url' => route('admin.students.training-plans.index')], ['label' => $plan->name_ar]]">
    @canPermission('App\Models\Admin\Student\Course', 'edit')
    <a href="{{ route('admin.students.training-plans.edit', $plan) }}" class="btn btn-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i> تعديل</a>
    @endcanPermission
    <a href="{{ route('admin.students.training-plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1" aria-hidden="true"></i> رجوع</a>
</x-page-header>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">المشروع</small>
                <strong>{{ $plan->project?->name ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">المدة</small>
                <strong>{{ $plan->start_date?->format('Y-m-d') ?? '—' }} → {{ $plan->end_date?->format('Y-m-d') ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">الحالة</small>
                <span class="badge {{ $plan->status === 'active' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $plan->status }}</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">الوصف</small>
                <span>{{ $plan->description ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>

@if ($plan->lessons()->exists())
    <div class="d-flex align-items-center justify-content-between mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#allWeeks" aria-expanded="true">
            <i class="bi bi-list-ul me-1"></i> توسيع / طي كل الأسابيع
        </button>
    </div>

    @foreach ($lessons as $week => $weekLessons)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <button class="btn btn-link p-0 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#week-{{ $week }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                    <h6 class="mb-0"><i class="bi bi-calendar-week me-1"></i> الأسبوع {{ $week }}</h6>
                </button>
                <span class="badge bg-light text-dark">{{ $weekLessons->count() }} دروس</span>
            </div>
            <div class="collapse {{ $loop->first ? 'show' : '' }}" id="week-{{ $week }}">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>الوقت</th>
                                    <th>الصف / المستوى</th>
                                    <th>المادة</th>
                                    <th>اسم الدرس</th>
                                    <th>المدرّس</th>
                                    <th>المكان</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($weekLessons as $lesson)
                                    <tr>
                                        <td class="fw-medium">{{ $days[$lesson->day_of_week] ?? $lesson->day_of_week }}</td>
                                        <td>{{ $lesson->start_time ? substr($lesson->start_time, 0, 5) . ' - ' . substr($lesson->end_time, 0, 5) : '—' }}</td>
                                        <td>{{ $lesson->level?->name_ar ?? '—' }}</td>
                                        <td>{{ $lesson->subject?->name_ar ?? '—' }}</td>
                                        <td>{{ $lesson->lesson_name ?? '—' }}</td>
                                        <td>
                                            @if ($lesson->instructor)
                                                <span class="badge bg-secondary-subtle text-dark">{{ $lesson->instructor->first_name_ar }} {{ $lesson->instructor->last_name_ar }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $lesson->location ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="alert alert-info">لا توجد دروس في هذه الخطة بعد.</div>
@endif
@endsection
