@extends('admin.layouts.master')

@section('title', 'تعديل نطاق دور')

@section('content')
<div class="page-header">
    <h4>تعديل نطاق الدور</h4>
    <p>
        <a href="{{ route('admin.roles.index') }}" class="text-decoration-none">الأدوار والنطاقات</a>
        / {{ $user->name }} — {{ $group->name }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="alert alert-secondary py-2">
                الدور: <b>{{ $group->name }}</b>
                @if ($group->permissions->isNotEmpty())
                    — يشمل {{ $group->permissions->flatMap(fn($p) => $p->model_names ?? [])->unique()->count() }} موديلاً
                @else
                    — <span class="text-danger">هذا الدور لا يملك أي صلاحيات بعد! عرّفه من شاشة الصلاحيات</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.roles.update', [$group, $user]) }}">
                @csrf
                @method('PUT')

                @include('admin.roles._fields', ['user' => $user])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ النطاق
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
