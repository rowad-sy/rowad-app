@extends('admin.layouts.master')

@section('title', isset($role) ? 'تعديل دور' : 'تعريف دور جديد')

@section('content')
<x-page-header :title="isset($role) ? 'تعديل دور: ' . $role->name : 'تعريف دور جديد'"
               :breadcrumb="[['label' => 'الأدوار والنطاقات', 'url' => route('admin.roles.index')], ['label' => isset($role) ? 'تعديل' : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST" action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
                @csrf
                @if (isset($role))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">اسم الدور <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $role->name ?? '') }}" required
                           placeholder="مثال: مدير مشروع، محاسب، مسؤول مخزن">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror"
                              placeholder="ما وظيفة هذا الدور في المؤسسة؟">{{ old('description', $role->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="alert alert-light border small text-muted">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    هنا تُسمّي الدور فقط. صلاحياته (الموديلات والأعلام) تُعرَّف من
                    <a href="{{ route('admin.permissions.create') }}" class="text-decoration-none">شاشة الصلاحيات</a>
                    — والمركز/المشروع/الفوج تُحدَّد وقت الإسناد في هذه الشاشة.
                    @if (isset($role))
                        <div class="mt-1">💡 عند تعديل الاسم تظهر التسمية الجديدة فوراً لكل حاملي هذا الدور في كل النطاقات.</div>
                    @endif
                </div>

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
