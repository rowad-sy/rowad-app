@extends('admin.layouts.master')

@section('title', 'مرضى العلاج الفيزيائي')

@section('content')
<div class="page-header">
    <h4>مرضى العلاج الفيزيائي</h4>
    <p>
        <a href="{{ route('admin.home') }}" class="text-decoration-none">التطبيقات</a> / العلاج الفيزيائي / المرضى
    </p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control" style="width: auto;" placeholder="بحث بالاسم أو الهاتف" value="{{ request('search') }}">
        <select name="gender" class="form-select" style="width: auto;">
            <option value="">كل الجنسين</option>
            <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>ذكر</option>
            <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>أنثى</option>
        </select>
        <select name="center_id" class="form-select" style="width: auto;">
            <option value="">كل المراكز</option>
            @foreach ($centers as $center)
                <option value="{{ $center->id }}" {{ (string) request('center_id') === (string) $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
            @endforeach
        </select>
        <select name="therapist_id" class="form-select" style="width: auto;">
            <option value="">كل المعالجين</option>
            @foreach ($therapists as $therapist)
                <option value="{{ $therapist->id }}" {{ (string) request('therapist_id') === (string) $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
            @endforeach
        </select>
        <select name="room_id" class="form-select" style="width: auto;">
            <option value="">كل الغرف</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" {{ (string) request('room_id') === (string) $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
        </select>
        <select name="transferred" class="form-select" style="width: auto;">
            <option value="">الكل</option>
            <option value="0" {{ request('transferred') === '0' ? 'selected' : '' }}>غير منقول</option>
            <option value="1" {{ request('transferred') === '1' ? 'selected' : '' }}>منقول</option>
        </select>
        <button class="btn btn-outline-primary">تصفية</button>
    </form>
    @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'create')
    <a href="{{ route('admin.physiotherapy.patients.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> تسجيل مريض
    </a>
    @endcanPermission
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
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
                        <span class="badge {{ $patient->sessions_count > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $patient->sessions_count }}</span>
                    </td>
                    <td>
                        @if ($patient->is_transferred)
                            <span class="badge bg-warning text-dark">منقول</span>
                        @else
                            <span class="badge bg-success">نشط</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.physiotherapy.patients.show', $patient) }}" class="btn btn-sm btn-outline-info" title="عرض ومتابعة">
                            <i class="bi bi-eye"></i>
                        </a>
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'edit')
                        <a href="{{ route('admin.physiotherapy.patients.edit', $patient) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'delete')
                        <form method="POST" action="{{ route('admin.physiotherapy.patients.destroy', $patient) }}"
                              class="d-inline" onsubmit="return confirm('حذف المريض مع جلساته نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">لا يوجد مرضى بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $patients->links() }}</div>
@endsection