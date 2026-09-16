@extends('admin.layouts.master')

@section('title', 'مرضى النقل')

@section('content')
<div class="page-header">
    <h4>مرضى النقل</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / العلاج الفيزيائي / مرضى النقل
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        <select name="center_id" class="form-select" style="width: auto;">
            <option value="">كل المراكز</option>
            @foreach ($centers as $center)
                <option value="{{ $center->id }}" {{ (string) request('center_id') === (string) $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
            @endforeach
        </select>
        <label class="small text-muted mb-0">من</label>
        <input type="date" name="from_date" class="form-control" style="width: auto;" value="{{ $fromDate ?? '' }}">
        <label class="small text-muted mb-0">إلى</label>
        <input type="date" name="to_date" class="form-control" style="width: auto;" value="{{ $toDate ?? '' }}">
        <button class="btn btn-outline-primary">تصفية</button>
    </form>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>تاريخ النقل</th>
                    <th>الاسم</th>
                    <th>الجنس</th>
                    <th>المركز</th>
                    <th>المعالج</th>
                    <th>الغرفة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                <tr>
                    <td>{{ $patient->transferred_at?->format('d/m/Y') }}</td>
                    <td class="fw-semibold">
                        <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="text-decoration-none">{{ $patient->name }}</a>
                    </td>
                    <td>
                        <span class="badge {{ $patient->gender === 'male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }}">
                            {{ \App\Models\Admin\Physiotherapy\PhysioPatient::GENDERS[$patient->gender] ?? $patient->gender }}
                        </span>
                    </td>
                    <td>{{ $patient->center?->name ?? '—' }}</td>
                    <td>{{ $patient->therapist?->name ?? '—' }}</td>
                    <td>{{ $patient->room?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="btn btn-sm btn-outline-info" title="عرض">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد مرضى منقولون ضمن هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $patients->links() }}</div>
@endsection