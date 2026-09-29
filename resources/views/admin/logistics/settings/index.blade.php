@extends('admin.logistics.layouts.master')

@section('title', 'إعدادات اللوجستيك')

@section('logistics-content')
<x-page-header title="إعدادات اللوجستيك" description="تخصيص إعدادات وحدة اللوجستيك" :breadcrumb="[['label' => 'اللوجستيك'], ['label' => 'الإعدادات']]" />

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.logistics.settings.update') }}">
                @csrf
                @foreach ($settings ?? [] as $key => $value)
                    <div class="mb-3">
                        <label class="form-label">{{ $key }}</label>
                        <input type="text" name="settings[{{ $key }}]"
                               class="form-control"
                               value="{{ old('settings.' . $key, $value) }}">
                    </div>
                @endforeach

                @if (empty($settings ?? []))
                    <p class="text-muted">لا توجد إعدادات مخصصة بعد.</p>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
