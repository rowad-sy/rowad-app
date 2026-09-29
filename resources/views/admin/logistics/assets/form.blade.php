@extends('admin.logistics.layouts.master')

@section('title', isset($asset) ? 'تعديل أصل' : 'إضافة أصل')

@section('logistics-content')
<x-page-header :title="isset($asset) ? 'تعديل الأصل' : 'إضافة أصل'"
               :breadcrumb="[['label' => 'الأصول', 'url' => route('admin.logistics.assets.index')], ['label' => isset($asset) ? $asset->name : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($asset) ? route('admin.logistics.assets.update', $asset) : route('admin.logistics.assets.store') }}">
                @csrf
                @if (isset($asset))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">كود الأصل <span class="text-danger">*</span></label>
                        <input type="text" name="asset_code"
                               class="form-control @error('asset_code') is-invalid @enderror"
                               value="{{ old('asset_code', $asset->asset_code ?? '') }}" required>
                        @error('asset_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $asset->name ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">— اختر النوع —</option>
                            <option value="أثاث" {{ old('type', $asset->type ?? '') === 'أثاث' ? 'selected' : '' }}>أثاث</option>
                            <option value="أجهزة" {{ old('type', $asset->type ?? '') === 'أجهزة' ? 'selected' : '' }}>أجهزة</option>
                            <option value="أخرى" {{ old('type', $asset->type ?? '') === 'أخرى' ? 'selected' : '' }}>أخرى</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="">— اختر الحالة —</option>
                            <option value="جيد" {{ old('status', $asset->status ?? '') === 'جيد' ? 'selected' : '' }}>جيد</option>
                            <option value="تالف" {{ old('status', $asset->status ?? '') === 'تالف' ? 'selected' : '' }}>تالف</option>
                            <option value="صيانة" {{ old('status', $asset->status ?? '') === 'صيانة' ? 'selected' : '' }}>صيانة</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">— اختر المركز —</option>
                            @foreach ($centers ?? [] as $center)
                                <option value="{{ $center->id }}" {{ old('center_id', $asset->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects ?? [] as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $asset->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الغرفة</label>
                        <input type="text" name="room_number"
                               class="form-control @error('room_number') is-invalid @enderror"
                               value="{{ old('room_number', $asset->room_number ?? '') }}">
                        @error('room_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">المستلم</label>
                    <select name="recipient_id" class="form-select @error('recipient_id') is-invalid @enderror">
                        <option value="">— اختر المستلم —</option>
                        @foreach ($users ?? [] as $user)
                            <option value="{{ $user->id }}" {{ old('recipient_id', $asset->recipient_id ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('recipient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $asset->notes ?? '') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.logistics.assets.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
