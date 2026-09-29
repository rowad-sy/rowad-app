@extends('admin.layouts.master')

@section('title', $patient->name)

@section('content')
<x-page-header :title="$patient->name" :breadcrumb="[['label' => 'المرضى', 'url' => route('admin.physiotherapy.patients.index')], ['label' => $patient->name]]">
    @canPermission('App\Models\Admin\Physiotherapy\PhysioPatient', 'edit')
    <a href="{{ route('admin.physiotherapy.patients.edit', $patient) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> تعديل البيانات</a>
    @endcanPermission
</x-page-header>

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <span class="badge {{ $patient->gender === 'male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }}">
        {{ \App\Models\Admin\Physiotherapy\PhysioPatient::GENDERS[$patient->gender] ?? $patient->gender }}
    </span>
    @if ($patient->is_transferred)
        <x-status-badge tone="warning">منقول {{ $patient->transferred_at?->format('d/m/Y') }}</x-status-badge>
    @else
        <x-status-badge tone="success">نشط</x-status-badge>
    @endif
    <x-status-badge>مركز: {{ $patient->center?->name ?? '—' }}</x-status-badge>
    <x-status-badge>المعالج: {{ $patient->therapist?->name ?? '—' }}</x-status-badge>
    <x-status-badge>الغرفة: {{ $patient->room?->name ?? '—' }}</x-status-badge>
    <x-status-badge>عدد الجلسات: {{ $patient->sessions->count() }}</x-status-badge>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">بيانات شخصية</div>
            <div class="card-body small">
                <div class="mb-1"><strong>تاريخ الميلاد:</strong> {{ $patient->birth_date?->format('d/m/Y') ?? '—' }}</div>
                <div class="mb-1"><strong>الهاتف:</strong> {{ $patient->phone ?? '—' }}</div>
                <div class="mb-1"><strong>العنوان:</strong> {{ $patient->address ?? '—' }}</div>
                <div class="mb-1"><strong>تاريخ التسجيل:</strong> {{ $patient->registration_date?->format('d/m/Y') }}</div>
                <div><strong>سجّله:</strong> {{ $patient->creator?->name ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">التاريخ المرضي والملاحظات</div>
            <div class="card-body small">
                <div class="mb-2">{{ $patient->medical_history ?: '—' }}</div>
                <hr>
                <div>{{ $patient->notes ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-pulse me-1"></i> جلسات العلاج</span>
        @canPermission('App\Models\Admin\Physiotherapy\PhysioSession', 'edit')
        <a href="#add-session" class="btn btn-sm btn-primary" data-bs-toggle="collapse">
            <i class="bi bi-plus-lg"></i> إضافة جلسة
        </a>
        @endcanPermission
    </div>
    <div class="card-body table-responsive">
        <div class="collapse mb-3" id="add-session">
            <form method="POST" action="{{ route('admin.physiotherapy.sessions.store') }}" class="border rounded p-3 bg-light">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="session_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">رقم الجلسة <small class="text-muted">(تلقائي إن تُرك)</small></label>
                        <input type="number" name="session_number" min="1" class="form-control form-control-sm"
                               value="{{ $patient->sessions->count() + 1 }}" placeholder="تلقائي">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">المعالج <small class="text-muted">(افتراضي: معالج المريض)</small></label>
                        <select name="therapist_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($therapists as $therapist)
                                <option value="{{ $therapist->id }}" {{ $patient->therapist_id == $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">ملاحظات</label>
                        <input type="text" name="notes" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label small">ما فُعل في الجلسة <span class="text-danger">*</span></label>
                        <textarea name="what_done" rows="2" class="form-control" required
                                  placeholder="أوضح بالتفصيل ما تم إجراؤه في هذه الجلسة (تأهيل، جلسة كهرباء، تمارين...)" ></textarea>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> حفظ الجلسة</button>
            </form>
        </div>

        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>رقم الجلسة</th>
                    <th>التاريخ</th>
                    <th>ما فُعل في الجلسة</th>
                    <th>المعالج</th>
                    <th>ملاحظات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patient->sessions as $session)
                <tr>
                    <td>{{ $session->session_number }}</td>
                    <td>{{ $session->session_date?->format('d/m/Y') }}</td>
                    <td class="small">{{ $session->what_done }}</td>
                    <td>{{ $session->therapist?->name ?? '—' }}</td>
                    <td class="small">{{ $session->notes ?: '—' }}</td>
                    <td class="text-nowrap">
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioSession', 'edit')
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#edit-session-{{ $session->id }}" title="تعديل">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcanPermission
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioSession', 'delete')
                        <form method="POST" action="{{ route('admin.physiotherapy.sessions.destroy', $session) }}"
                              class="d-inline" onsubmit="return confirm('حذف الجلسة نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @canPermission('App\Models\Admin\Physiotherapy\PhysioSession', 'edit')
                <tr class="collapse" id="edit-session-{{ $session->id }}">
                    <td colspan="6" class="bg-light">
                        <form method="POST" action="{{ route('admin.physiotherapy.sessions.update', $session) }}" class="row g-2">
                            @csrf @method('PUT')
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <div class="col-md-2">
                                <label class="form-label small">رقم الجلسة</label>
                                <input type="number" name="session_number" min="1" class="form-control form-control-sm" value="{{ $session->session_number }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">التاريخ</label>
                                <input type="date" name="session_date" class="form-control form-control-sm" value="{{ $session->session_date?->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">ما فُعل في الجلسة</label>
                                <textarea name="what_done" rows="1" class="form-control form-control-sm" required>{{ $session->what_done }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">المعالج</label>
                                <select name="therapist_id" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    @foreach ($therapists as $therapist)
                                        <option value="{{ $therapist->id }}" {{ $session->therapist_id == $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">ملاحظات</label>
                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ $session->notes }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> حفظ</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endcanPermission
                @empty
                <x-empty-row colspan="6" title="لا توجد جلسات بعد — أضف الجلسة الأولى" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection