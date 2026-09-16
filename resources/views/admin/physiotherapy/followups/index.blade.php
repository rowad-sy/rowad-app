@extends('admin.layouts.master')

@section('title', 'متابعة المرضى حسب المعالج')

@section('content')
<div class="page-header">
    <h4>متابعة المرضى حسب المعالج</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / العلاج الفيزيائي / المتابعة
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="center_id" class="form-select" style="width: auto;">
            <option value="">كل المراكز</option>
            @foreach ($centers as $center)
                <option value="{{ $center->id }}" {{ (string) request('center_id') === (string) $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
            @endforeach
        </select>
        <select name="therapist_id" class="form-select" style="width: auto;">
            <option value="">كل المعالجين (تجميع)</option>
            @foreach ($therapists as $therapist)
                <option value="{{ $therapist->id }}" {{ (string) request('therapist_id') === (string) $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary">عرض</button>
    </form>
    <p class="text-muted small mb-0">عدد الجلسات + ما فُعل بكل جلسة لكل مريض، مجمّعاً حسب المعالج.</p>
</div>

@forelse ($patients as $groupKey => $groupPatients)

    @php
        if ($groupKey === 'single') {
            $groupLabel = $selectedTherapist?->name ?? 'جميع المرضى';
        } elseif ($groupKey === 'unassigned') {
            $groupLabel = 'بدون معالج';
        } else {
            $groupLabel = $therapists->firstWhere('id', (int) $groupKey)?->name ?? 'معالج #' . $groupKey;
        }
    @endphp

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-person-badge me-1"></i> المعالج: {{ $groupLabel }}</span>
            <span class="badge bg-primary">{{ count($groupPatients) }} مريض</span>
        </div>
        <div class="card-body">
            @forelse ($groupPatients as $patient)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="fw-semibold text-decoration-none">{{ $patient->name }}</a>
                            <span class="badge {{ $patient->gender === 'male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }} ms-1">
                                {{ \App\Models\Admin\Physiotherapy\PhysioPatient::GENDERS[$patient->gender] ?? $patient->gender }}
                            </span>
                            @if ($patient->is_transferred)
                                <span class="badge bg-warning text-dark">منقول</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-secondary">مركز: {{ $patient->center?->name ?? '—' }}</span>
                            <span class="badge bg-secondary">غرفة: {{ $patient->room?->name ?? '—' }}</span>
                            <span class="badge bg-dark">عدد الجلسات: {{ $patient->sessions_count }}</span>
                        </div>
                    </div>

                    @if ($patient->sessions->isNotEmpty())
                        <table class="table table-sm align-middle mb-0 border-top">
                            <thead>
                                <tr>
                                    <th>الرقم</th>
                                    <th>التاريخ</th>
                                    <th>ما فُعل في الجلسة</th>
                                    <th>المعالج</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($patient->sessions as $session)
                                <tr>
                                    <td>{{ $session->session_number }}</td>
                                    <td>{{ $session->session_date?->format('d/m/Y') }}</td>
                                    <td class="small">{{ $session->what_done }}</td>
                                    <td class="small">{{ $session->therapist?->name ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="small text-muted mb-0">لا توجد جلسات مسجلة لهذا المريض بعد.</p>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0 text-center py-3">لا يوجد مرضى في هذه المجموعة</p>
            @endforelse
        </div>
    </div>

@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">لا توجد نتائج مطابقة</div>
    </div>
@endforelse
@endsection