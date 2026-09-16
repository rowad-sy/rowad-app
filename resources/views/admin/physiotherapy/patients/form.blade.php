@extends('admin.layouts.master')

@section('title', isset($patient) ? 'تعديل مريض' : 'تسجيل مريض')

@section('content')
<div class="page-header">
    <h4>{{ isset($patient) ? 'تعديل بيانات المريض' : 'تسجيل مريض جديد' }}</h4>
    <p>
        <a href="{{ route('admin.physiotherapy.patients.index') }}" class="text-decoration-none">المرضى</a> / {{ isset($patient) ? 'تعديل' : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ isset($patient) ? route('admin.physiotherapy.patients.update', $patient) : route('admin.physiotherapy.patients.store') }}">
                @csrf
                @isset($patient) @method('PUT') @endisset

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم المريض <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $patient->name ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">الجنس <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="male" {{ old('gender', $patient->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ old('gender', $patient->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="birth_date" class="form-control"
                               value="{{ old('birth_date', ($patient ?? null)?->birth_date?->format('Y-m-d') ?? '') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ old('center_id', $patient->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المعالج</label>
                        <select name="therapist_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($therapists as $therapist)
                                <option value="{{ $therapist->id }}" {{ old('therapist_id', $patient->therapist_id ?? '') == $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
                            @endforeach
                        </select>
                        @error('therapist_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الغرفة</label>
                        <select name="room_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id', $patient->room_id ?? '') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $patient->phone ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ التسجيل <span class="text-danger">*</span></label>
                        <input type="date" name="registration_date" class="form-control @error('registration_date') is-invalid @enderror"
                               value="{{ old('registration_date', ($patient ?? null)?->registration_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                        @error('registration_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">العنوان</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $patient->address ?? '') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">التاريخ المرضي</label>
                    <textarea name="medical_history" rows="3" class="form-control"
                              placeholder="السوابق المرضية، التشخيص، ملاحظات طبية...">{{ old('medical_history', $patient->medical_history ?? '') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="2" class="form-control">{{ old('notes', $patient->notes ?? '') }}</textarea>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_transferred" value="1" id="is_transferred"
                               onchange="toggleTransfer()" {{ old('is_transferred', $patient->is_transferred ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_transferred">مريض منقول</label>
                    </div>
                    <div id="transfer-date-wrap" style="{{ (old('is_transferred', $patient->is_transferred ?? false)) ? '' : 'display:none' }}">
                        <label class="form-label">تاريخ النقل <span class="text-danger">*</span></label>
                        <input type="date" name="transferred_at" class="form-control"
                               value="{{ old('transferred_at', ($patient ?? null)?->transferred_at?->format('Y-m-d') ?? '') }}">
                    </div>
                </div>

                <button class="btn btn-primary">{{ isset($patient) ? 'حفظ التعديلات' : 'تسجيل المريض' }}</button>
                <a href="{{ isset($patient) ? route('admin.physiotherapy.patients.show', $patient) : route('admin.physiotherapy.patients.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTransfer() {
    document.getElementById('transfer-date-wrap').style.display =
        document.getElementById('is_transferred').checked ? '' : 'none';
}
</script>
@endsection