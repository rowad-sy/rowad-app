@extends('admin.layouts.master')

@section('title', 'مرضى العلاج الفيزيائي')

@section('content')
<x-page-header :title="'مرضى العلاج الفيزيائي'"
               :breadcrumb="[['label' => 'العلاج الفيزيائي'], ['label' => 'المرضى']]">
    @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'create')
        <a href="{{ route('admin.physiotherapy.patients.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> تسجيل مريض
        </a>
        @endcanPermission
</x-page-header>

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input id="f-search" type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الهاتف" value="{{ request('search') }}">
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-gender">الجنس</label>
            <select id="f-gender" name="gender" class="form-select">
            <option value="">كل الجنسين</option>
            <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>ذكر</option>
            <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>أنثى</option>
        </select>
        </div>
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
            <label class="form-label" for="f-therapist_id">المعالج</label>
            <select id="f-therapist_id" name="therapist_id" class="form-select">
            <option value="">كل المعالجين</option>
            @foreach ($therapists as $therapist)
                <option value="{{ $therapist->id }}" {{ (string) request('therapist_id') === (string) $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-room_id">الغرفة</label>
            <select id="f-room_id" name="room_id" class="form-select">
            <option value="">كل الغرف</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" {{ (string) request('room_id') === (string) $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-transferred">حالة النقل</label>
            <select id="f-transferred" name="transferred" class="form-select">
            <option value="">الكل</option>
            <option value="0" {{ request('transferred') === '0' ? 'selected' : '' }}>غير منقول</option>
            <option value="1" {{ request('transferred') === '1' ? 'selected' : '' }}>منقول</option>
        </select>
        </div>
    </x-filter-bar>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الجنس</th>
                    <th>المركز</th>
                    <th>المعالج</th>
                    <th>الغرفة</th>
                    <th>تاريخ التسجيل</th>
                    <th>الجلسات</th>
                    <th>الوضع</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                <tr>
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
                    <td>{{ $patient->registration_date?->format('d/m/Y') }}</td>
                    <td>
                        <x-status-badge :tone="$patient->sessions_count > 0 ? 'success' : 'neutral'">{{ $patient->sessions_count }}</x-status-badge>
                    </td>
                    <td>
                        @if ($patient->is_transferred)
                            <x-status-badge tone="warning">منقول</x-status-badge>
                        @else
                            <x-status-badge tone="success">نشط</x-status-badge>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="btn btn-sm btn-outline-info" title="عرض ومتابعة" aria-label="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'edit')
                        <a href="{{ route('admin.physiotherapy.patients.edit', $patient) }}" class="btn btn-sm btn-outline-primary" title="تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'delete')
                        <form method="POST" action="{{ route('admin.physiotherapy.patients.destroy', $patient) }}"
                              class="d-inline" onsubmit="return confirm('حذف المريض مع جلساته نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <x-empty-row colspan="9" title="لا يوجد مرضى بعد" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $patients->links() }}</div>
@endsection