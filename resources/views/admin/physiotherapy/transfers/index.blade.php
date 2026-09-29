@extends('admin.layouts.master')

@section('title', 'مرضى النقل')

@section('content')
<x-page-header :title="'مرضى النقل'"
               :breadcrumb="[['label' => 'العلاج الفيزيائي'], ['label' => 'مرضى النقل']]">
    </div>
    
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                            <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="btn btn-sm btn-outline-info" title="عرض" aria-label="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                    @empty
                    <x-empty-row colspan="7" title="لا يوجد مرضى منقولون ضمن هذا النطاق" />
                    @endforelse
                </tbody>
            </table>
        </div>
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-center_id">المركز</label>
            <select id="f-center_id" name="center_id" class="form-select">
            <option value="">كل المراكز</option>
            @foreach ($centers as $center)
                <option value="{{ $center->id }}" {{ (string) request('center_id') === (string) $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-from_date">من تاريخ</label>
            <input id="f-from_date" type="date" name="from_date" class="form-control" value="{{ $fromDate ?? '' }}">
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-to_date">إلى تاريخ</label>
            <input id="f-to_date" type="date" name="to_date" class="form-control" value="{{ $toDate ?? '' }}">
        </div>
    </x-filter-bar>
</div>

<div class="mt-3">{{ $patients->links() }}</div>
@endsection