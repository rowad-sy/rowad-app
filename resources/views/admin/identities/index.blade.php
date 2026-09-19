@extends('admin.layouts.master')

@section('title', 'المعرفات الرئيسية')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-share me-2 text-danger"></i>المعرفات الرئيسية</h4>
        <p>حسابات ومنصات مؤسسة الرواد للتعاون والتنمية الرسمية</p>
    </div>
    <a href="{{ route('admin.portal') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="text-center mb-4">
    <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة" style="width: 92px; height: 92px; object-fit: contain; background: #fff; border-radius: 22px; padding: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.10);">
    <h5 class="fw-bold mt-2 mb-0">مؤسسة الرُّؤاد للتعاون والتنمية</h5>
</div>

<div class="row g-3">
    @foreach ($identities as $identity)
        <div class="col-md-6 col-lg-4">
            <a href="{{ $identity['url'] }}" target="_blank" rel="noopener" class="identity-card">
                <div class="identity-icon" style="background: {{ $identity['color'] }};">
                    <i class="bi {{ $identity['icon'] }}"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold">{{ $identity['label'] }}</div>
                    <div class="text-muted small text-truncate">{{ $identity['value'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
