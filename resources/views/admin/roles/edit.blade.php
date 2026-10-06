@extends('admin.layouts.master')

@section('title', 'تعديل نطاق دور')

@section('content')
<x-page-header title="تعديل نطاق الدور"
               :breadcrumb="[['label' => 'الأدوار والنطاقات', 'url' => route('admin.roles.index')], ['label' => $user->name . ' — ' . $group->name]]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <div class="alert alert-light border py-2 small">
                الدور: <b>{{ $group->name }}</b>
                @if ($group->permissions->isNotEmpty())
                    — يشمل {{ $group->permissions->flatMap(fn($p) => $p->model_names ?? [])->unique()->count() }} موديلاً
                @else
                    — <span class="text-danger">هذا الدور لا يملك أي صلاحيات بعد! عرّفه من شاشة الصلاحيات</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.roles.assignment.update', [$group, $user]) }}">
                @csrf
                @method('PUT')

                @include('admin.roles._fields', ['user' => $user])

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ النطاق
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
