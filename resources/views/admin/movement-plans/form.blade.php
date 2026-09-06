@extends('admin.layouts.master')

@section('title', 'خطة حركة جديدة')

@section('content')
<div class="page-header">
    <h4>خطة حركة جديدة</h4>
    <p>
        <a href="{{ route('admin.movement-plans.index') }}" class="text-decoration-none">خطة الحركة</a>
        / جديد
    </p>
    <small class="text-muted">تُنشأ عادةً من مدير المشروع ثم تُمرَّر إلى إدارة المشاريع فمسؤول الحركة.</small>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.movement-plans.store') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الحركة <span class="text-danger">*</span></label>
                        <input type="date" name="movement_date" class="form-control @error('movement_date') is-invalid @enderror"
                               value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                        @error('movement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">وقت الانطلاق</label>
                        <input type="time" name="departure_time" class="form-control @error('departure_time') is-invalid @enderror"
                               value="{{ old('departure_time') }}">
                        @error('departure_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">وقت العودة</label>
                        <input type="time" name="return_time" class="form-control @error('return_time') is-invalid @enderror"
                               value="{{ old('return_time') }}">
                        @error('return_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">من</label>
                        <input type="text" name="from_location" class="form-control" value="{{ old('from_location') }}" placeholder="المركز / المدينة">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إلى</label>
                        <input type="text" name="to_location" class="form-control" value="{{ old('to_location') }}" placeholder="المركز / المدينة">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الغاية من الحركة <span class="text-danger">*</span></label>
                    <textarea name="purpose" rows="3" class="form-control @error('purpose') is-invalid @enderror" required>{{ old('purpose') }}</textarea>
                    @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select">
                            <option value="">— اختر —</option>
                            @foreach ($centers as $c)
                                <option value="{{ $c->id }}" {{ old('center_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— اختر —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> إنشاء وإحالة لإدارة المشاريع</button>
                    <a href="{{ route('admin.movement-plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection