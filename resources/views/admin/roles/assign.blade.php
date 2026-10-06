@extends('admin.layouts.master')

@section('title', 'منح دور')

@section('content')
<x-page-header title="منح دور"
               :breadcrumb="[['label' => 'الأدوار والنطاقات', 'url' => route('admin.roles.index')], ['label' => 'منح دور']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.roles.assign.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">الدور (المجموعة) <span class="text-danger">*</span></label>
                    <select name="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                        <option value="">— اختر الدور —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('group_id') === $role->id)>
                                {{ $role->name }} ({{ $role->description ?: 'بدون وصف' }})
                            </option>
                        @endforeach
                    </select>
                    @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">تعريفات الأدوار (الموديلات والأعلام) تُدار من
                        <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none">شاشة الصلاحيات</a>.</div>
                </div>

                @include('admin.roles._fields')

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i> منح الدور
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
