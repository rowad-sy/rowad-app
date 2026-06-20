@extends('admin.layouts.master')

@section('title', $student->first_name_ar . ' ' . $student->last_name_ar)

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .info-item { padding: 0.5rem 0.75rem; background: #f8f9fa; border-radius: 6px; }
    .info-item .label { font-size: 0.75rem; color: #6c757d; display: block; }
    .info-item .value { font-size: 0.9rem; font-weight: 500; }
</style>
@endpush

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ $student->first_name_ar }} {{ $student->last_name_ar }}</h4>
        <p>
            <a href="{{ route('admin.students.index') }}" class="text-decoration-none">الطلاب</a>
            / {{ $student->student_code }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> تعديل
        </a>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i> عودة
        </a>
    </div>
</div>

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
                <span class="badge bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'primary' : ($student->status === 'suspended' ? 'warning' : 'secondary')) }} fs-6">
                    {{ $student->status === 'active' ? 'نشط' : ($student->status === 'inactive' ? 'غير نشط' : ($student->status === 'graduated' ? 'متخرج' : 'موقوف')) }}
                </span>
            </div>
            <div class="p-3">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">الكود</span>
                        <span class="value"><code>{{ $student->student_code }}</code></span>
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
                        <span class="label">المشروع</span>
                        <span class="value">{{ $student->project?->name ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">تاريخ التسجيل</span>
                        <span class="value">{{ $student->enrollment_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                </div>

                @if ($student->address)
                    <div class="mt-2 p-2" style="background:#f8f9fa;border-radius:6px;">
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
                        <span class="badge bg-success fs-6">مرتبط</span>
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
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">موقوف</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.edit', $student->user) }}" class="btn btn-sm btn-outline-primary mt-2 w-100">
                        <i class="bi bi-pencil me-1"></i> إدارة حساب المستخدم
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
                    <thead class="table-light">
                        <tr>
                            <th>المقرر</th>
                            <th>الفترة</th>
                            <th>تاريخ التسجيل</th>
                            <th>الحالة</th>
                            <th>الدرجة</th>
                            <th>شهادة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->enrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->course?->name_ar ?? '—' }}</td>
                                <td>{{ $enrollment->period?->name_ar ?? '—' }}</td>
                                <td>{{ $enrollment->enrollment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($enrollment->status === 'enrolled')
                                        <span class="badge bg-success">مسجل</span>
                                    @elseif ($enrollment->status === 'completed')
                                        <span class="badge bg-primary">مكتمل</span>
                                    @elseif ($enrollment->status === 'dropped')
                                        <span class="badge bg-danger">منسحب</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $enrollment->grade ?? '—' }}</td>
                                <td>
                                    @if ($enrollment->is_certificate_eligible)
                                        <span class="badge bg-success">مؤهل</span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    لا يوجد تسجيلات
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                    <thead class="table-light">
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
                                        <span class="badge bg-success">حاضر</span>
                                    @elseif ($att->status === 'absent')
                                        <span class="badge bg-danger">غائب</span>
                                    @else
                                        <span class="badge bg-warning text-dark">متعذر</span>
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
