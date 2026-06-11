@extends('admin.layouts.master')

@section('title', isset($group) ? 'تعديل مجموعة' : 'إضافة مجموعة')

@section('content')
<div class="page-header">
    <h4>{{ isset($group) ? 'تعديل المجموعة' : 'إضافة مجموعة' }}</h4>
    <p>
        <a href="{{ route('admin.groups.index') }}" class="text-decoration-none">المجموعات</a>
        / {{ isset($group) ? $group->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($group) ? route('admin.groups.update', $group) : route('admin.groups.store') }}">
                @csrf
                @if (isset($group))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">اسم المجموعة <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $group->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $group->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الأعضاء</label>
                    <div class="row g-2 mt-1">
                        @foreach ($users as $user)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="users[]" value="{{ $user->id }}"
                                           class="form-check-input"
                                           id="user_{{ $user->id }}"
                                           {{ in_array($user->id, old('users', $group->users->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                        <small class="text-muted">({{ $user->email }})</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
