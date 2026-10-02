@extends('admin.layouts.master')

@section('title', 'منح دور')

@section('content')
<div class="page-header">
    <h4>منح دور</h4>
    <p><a href="{{ route('admin.roles.index') }}" class="text-decoration-none">الأدوار والنطاقات</a> / منح دور جديد</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">الدور (المجموعة) <span class="text-danger">*</span></label>
                    <select name="group_id" class="form-control @error('group_id') is-invalid @enderror" required>
                        <option value="">— اختر الدور —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('group_id') === $role->id)>
                                {{ $role->name }} ({{ $role->description ?: 'بدون وصف' }})
                            </option>
                        @endforeach
                    </select>
                    @error('group_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <div class="form-text">تعريفات الأدوار (الموديلات والأعلام) تُدار من
                        <a href="{{ route('admin.permissions.index') }}">شاشة الصلاحيات</a>.</div>
                </div>

                @include('admin.roles._fields')

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> منح الدور
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
