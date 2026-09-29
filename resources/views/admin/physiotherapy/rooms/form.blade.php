@extends('admin.layouts.master')

@section('title', isset($room) ? 'تعديل غرفة' : 'غرفة جديدة')

@section('content')
<x-page-header :title="isset($room) ? 'تعديل الغرفة' : 'إضافة غرفة'"
               :breadcrumb="[['label' => 'الغرف', 'url' => route('admin.physiotherapy.rooms.index')], ['label' => isset($room) ? 'تعديل' : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ isset($room) ? route('admin.physiotherapy.rooms.update', $room) : route('admin.physiotherapy.rooms.store') }}">
                @csrf
                @isset($room) @method('PUT') @endisset

                <div class="mb-3">
                    <label class="form-label">اسم الغرفة <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $room->name ?? '') }}" placeholder="مثال: أطفال / نساء / كهرباء" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">المركز</label>
                    <select name="center_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" {{ old('center_id', $room->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="3" class="form-control"
                              placeholder="وصف الغرفة واستخداماتها...">{{ old('description', $room->description ?? '') }}</textarea>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $room->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">الغرفة نشطة</label>
                </div>

                <button class="btn btn-primary">{{ isset($room) ? 'حفظ التعديلات' : 'إضافة الغرفة' }}</button>
                <a href="{{ route('admin.physiotherapy.rooms.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection