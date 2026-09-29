@extends('admin.layouts.master')

@section('title', $student->first_name_ar . ' ' . $student->last_name_ar)

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: var(--color-surface-muted); border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: var(--color-text-muted); display: block; }
    .info-item { min-width: 0; overflow-wrap: anywhere; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
</style>
@endpush

@section('content')
@php
    $statusMap = ['active' => ['نشط', 'success'], 'inactive' => ['غير نشط', 'neutral'], 'graduated' => ['متخرج', 'brand'], 'suspended' => ['موقوف', 'warning']];
    [$statusLabel, $statusTone] = $statusMap[$student->status] ?? [$student->status, 'neutral'];
@endphp
<x-page-header :title="$student->first_name_ar . ' ' . $student->last_name_ar"
               :breadcrumb="[['label' => 'الطلاب', 'url' => route('admin.students.index')], ['label' => $student->first_name_ar . ' ' . $student->last_name_ar]]">
    <x-slot:meta>
        {{-- ملخص الهوية والحالة (بيانات موجودة في الملف نفسه) --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <x-status-badge><i class="bi bi-person-vcard" aria-hidden="true"></i> <span class="ltr-cell d-inline-block">{{ $student->student_code }}</span></x-status-badge>
            <x-status-badge :tone="$statusTone">{{ $statusLabel }}</x-status-badge>
            @if ($student->center)<x-status-badge><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $student->center->name }}</x-status-badge>@endif
            @if ($student->cohort)<x-status-badge><i class="bi bi-people" aria-hidden="true"></i> {{ $student->cohort->name }}</x-status-badge>@endif
        </div>
    </x-slot:meta>
    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary">
        <i class="bi bi-pencil me-1" aria-hidden="true"></i> تعديل
    </a>
    <x-audit-history :model="'App\Models\Admin\Student\Student'" :model-id="$student->id" />
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1" aria-hidden="true"></i> قائمة الطلاب
    </a>
</x-page-header>

{{-- Alert for create-user success shows password --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">
    {{-- Student Info Card --}}
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bi bi-person me-1"></i> بيانات الطالب</h5>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">الكود</span>
                        <span class="value"><code>{{ $student->student_code }}</code></span>
                    </div>
                    <div class="info-item">
                        <span class="label">نوع الهوية</span>
                        <span class="value">
                            @if ($student->identity_type)
                                @php $typeMap = ['national_id' => 'بطاقة هوية', 'passport' => 'جواز سفر', 'resident_id' => 'إقامة', 'other' => 'أخرى']; @endphp
                                {{ $typeMap[$student->identity_type] ?? $student->identity_type }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">رقم الهوية</span>
                        <span class="value" dir="ltr">{{ $student->identity_number ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الاسم AR</span>
                        <span class="value">{{ $student->first_name_ar }} {{ $student->last_name_ar }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الاسم EN</span>
                        <span class="value">{{ $student->first_name_en && $student->last_name_en ? $student->first_name_en . ' ' . $student->last_name_en : '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الجنس</span>
                        <span class="value">{{ $student->gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">تاريخ الميلاد</span>
                        <span class="value">{{ $student->birth_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">مكان الميلاد</span>
                        <span class="value">{{ $student->birth_place ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الجنسية</span>
                        <span class="value">{{ $student->nationality ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الأب</span>
                        <span class="value">{{ $student->father_name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الأم</span>
                        <span class="value">{{ $student->mother_name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">الهاتف</span>
                        <span class="value" dir="ltr">{{ $student->phone ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">البريد</span>
                        <span class="value">{{ $student->email ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المركز</span>
                        <span class="value">{{ $student->center?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">المشاريع</span>
                        <span class="value">
                            @if ($student->projects->isNotEmpty())
                                @foreach ($student->projects as $project)
                                    <x-status-badge tone="info" class="me-1">{{ $project->name }}</x-status-badge>
                                @endforeach
                            @else
                                {{ $student->project?->name ?? '—' }}
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">تاريخ التسجيل</span>
                        <span class="value">{{ $student->enrollment_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                </div>

                @if ($student->address)
                    <div class="mt-2 p-2" style="background:var(--color-surface-muted);border-radius:6px;">
                        <small class="text-muted d-block">العنوان</small>
                        <span>{{ $student->address }}</span>
                    </div>
                @endif
                @if ($student->notes)
                    <div class="mt-2 p-2" style="background:#fff3cd;border-radius:6px;">
                        <small class="text-muted d-block">ملاحظات</small>
                        <span>{{ $student->notes }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- User Account Card --}}
    <div class="col-lg-4">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-person-badge me-1"></i> حساب المستخدم</h5>
            </div>
            <div class="p-3">
                @if ($student->user)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <x-status-badge tone="success">مرتبط</x-status-badge>
                    </div>
                    <div class="info-grid" style="grid-template-columns:1fr;">
                        <div class="info-item">
                            <span class="label">الاسم</span>
                            <span class="value">{{ $student->user->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">البريد</span>
                            <span class="value" dir="ltr">{{ $student->user->email }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">الحالة</span>
                            <span class="value">
                                @if ($student->user->is_active)
                                    <x-status-badge tone="success">نشط</x-status-badge>
                                @else
                                    <x-status-badge>موقوف</x-status-badge>
                                @endif
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('admin.profile') }}" class="btn btn-sm btn-outline-primary mt-2 w-100">
                        <i class="bi bi-key me-1"></i> تغيير كلمة المرور
                    </a>
                @else
                    <p class="text-muted small mb-3">لا يوجد حساب مستخدم مرتبط بهذا الطالب.</p>
                    <form method="POST" action="{{ route('admin.students.create-user', $student) }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('سيتم إنشاء حساب مستخدم للطالب. هل تريد المتابعة؟')">
                            <i class="bi bi-person-plus me-1"></i> إنشاء حساب مستخدم
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Enrollments --}}
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-journal-text me-1"></i> التسجيلات في المقررات</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>المشروع</th>
                            <th>المقرر</th>
                            <th>الفترة</th>
                            <th>تاريخ التسجيل</th>
                            <th>الحالة</th>
                            <th>الدرجة</th>
                            <th>شهادة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->enrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->course?->project?->name ?? '—' }}</td>
                                <td>{{ $enrollment->course?->name_ar ?? '—' }}</td>
                                <td>{{ $enrollment->period?->name_ar ?? '—' }}</td>
                                <td>{{ $enrollment->enrollment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($enrollment->status === 'enrolled')
                                        <x-status-badge tone="success">مسجل</x-status-badge>
                                    @elseif ($enrollment->status === 'completed')
                                        <x-status-badge tone="brand">مكتمل</x-status-badge>
                                    @elseif ($enrollment->status === 'dropped')
                                        <x-status-badge tone="danger">منسحب</x-status-badge>
                                    @else
                                        <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $enrollment->grade ?? '—' }}</td>
                                <td>
                                    @if ($enrollment->is_certificate_eligible)
                                        <x-status-badge tone="success">مؤهل</x-status-badge>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.students.enrollments.grades.edit', $enrollment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i> درجات المواد
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row colspan="7" icon="bi-inbox" title="لا يوجد تسجيلات" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Certificates --}}
    @if ($student->certificates->isNotEmpty())
    <div class="col-12">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-award me-1"></i> الشهادات</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>رقم الشهادة</th>
                            <th>التصميم</th>
                            <th>المقرر</th>
                            <th>تاريخ الإصدار</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($student->certificates as $cert)
                            <tr>
                                <td><code>{{ $cert->certificate_number }}</code></td>
                                <td>{{ $cert->design?->name ?? '—' }}</td>
                                <td>{{ $cert->enrollment?->course?->name_ar ?? '—' }}</td>
                                <td>{{ $cert->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($cert->is_verified)
                                        <x-status-badge tone="success">موثقة</x-status-badge>
                                    @else
                                        <x-status-badge tone="warning">غير موثقة</x-status-badge>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.students.certificates.preview', $cert) }}" class="btn btn-sm btn-outline-primary" target="_blank" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Attendance Summary + Recent --}}
    <div class="col-md-5">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-1"></i> ملخص الحضور</h5>
            </div>
            <div class="p-3">
                <div class="d-flex gap-3 flex-wrap">
                    <div class="text-center p-3 rounded" style="background:#d1e7dd;min-width:100px;">
                        <div class="fs-3 fw-bold text-success">{{ $attendanceSummary['present'] ?? 0 }}</div>
                        <small class="text-muted">حاضر</small>
                    </div>
                    <div class="text-center p-3 rounded" style="background:#f8d7da;min-width:100px;">
                        <div class="fs-3 fw-bold text-danger">{{ $attendanceSummary['absent'] ?? 0 }}</div>
                        <small class="text-muted">غائب</small>
                    </div>
                    <div class="text-center p-3 rounded" style="background:#fff3cd;min-width:100px;">
                        <div class="fs-3 fw-bold text-warning">{{ $attendanceSummary['excused'] ?? 0 }}</div>
                        <small class="text-muted">متعذر</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="table-container">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> آخر 30 تسجيل حضور</h5>
            </div>
            <div class="table-responsive" style="max-height:300px;">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>ملاحظة</th>
                            <th>بواسطة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendance as $att)
                            <tr>
                                <td>{{ $att->date?->format('Y-m-d') }}</td>
                                <td>
                                    @if ($att->status === 'present')
                                        <x-status-badge tone="success">حاضر</x-status-badge>
                                    @elseif ($att->status === 'absent')
                                        <x-status-badge tone="danger">غائب</x-status-badge>
                                    @else
                                        <x-status-badge tone="warning">متعذر</x-status-badge>
                                    @endif
                                </td>
                                <td>{{ $att->note ?? '—' }}</td>
                                <td>{{ $att->createdBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">لا يوجد تسجيلات حضور</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
