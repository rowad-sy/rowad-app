@extends('admin.layouts.master')

@section('title', 'مساحة العمل')

@section('content')
@php
    $user = auth()->user();
@endphp

<x-page-header title="مساحة العمل"
               :description="$user->type === 'super-admin' ? 'نظرة شاملة على كل وحدات النظام حسب صلاحياتك.' : 'كل ما تحتاجه في مكان واحد — تظهر هنا الوحدات المتاحة لصلاحيتك فقط.'"
               :breadcrumb="[['label' => 'مساحة العمل']]">
    <a href="{{ route('admin.home') }}" class="btn btn-outline-primary">
        <i class="bi bi-grid-3x3-gap me-1"></i> كل التطبيقات
    </a>
</x-page-header>

<div class="table-container mb-3">
    <div class="p-3 d-flex flex-wrap align-items-center gap-2">
        <span class="ws-id-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
        <div class="ms-2">
            <div class="fw-bold">{{ $user->name }}</div>
            <div class="d-flex flex-wrap gap-1 mt-1">
                @if ($user->type === 'super-admin')
                    <x-status-badge tone="brand">مدير النظام</x-status-badge>
                @endif
                @foreach ($groupNames as $gn)
                    <x-status-badge tone="info">{{ $gn }}</x-status-badge>
                @endforeach
                @if ($user->jobTitle?->title_ar)
                    <x-status-badge tone="success">{{ $user->jobTitle->title_ar }}</x-status-badge>
                @endif
                @if ($employee?->center ?? $user->center)
                    <x-status-badge tone="neutral"><i class="bi bi-building me-1" aria-hidden="true"></i>{{ ($employee?->center ?? $user->center)->name }}</x-status-badge>
                @endif
                @if ($employee?->project ?? $user->project)
                    <x-status-badge tone="neutral"><i class="bi bi-pin-angle me-1" aria-hidden="true"></i>{{ ($employee?->project ?? $user->project)->name }}</x-status-badge>
                @endif
                @if ($user->type !== 'super-admin' && $groupNames === [] && ! $employee)
                    <x-status-badge tone="warning">بدون أدوار مسندة بعد</x-status-badge>
                @endif
            </div>
        </div>
    </div>
</div>

@if (empty($modules))
    <div class="table-container">
        <div class="p-5 text-center">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-2" aria-hidden="true"></i>
            <h5 class="mb-1">لا توجد وحدات متاحة بعد</h5>
            <p class="text-muted small mb-0">لم تُسند لك صلاحيات على أي وحدة — تواصل مع مدير النظام لتفعيل وصولك.</p>
        </div>
    </div>
@else
    <div class="ws-grid">
        @foreach ($modules as $module)
            <details class="ws-card" data-module="{{ $module['key'] }}">
                <summary>
                    <span class="ws-icon"><i class="bi {{ $module['icon'] }}" aria-hidden="true"></i></span>
                    <div class="ws-meta">
                        <div class="ws-title">{{ $module['title'] }}</div>
                        <div class="ws-sub">{{ $module['desc'] }}</div>
                    </div>
                    <span class="ws-count">{{ count($module['actions']) }}</span>
                    <i class="bi bi-chevron-down ws-caret" aria-hidden="true"></i>
                </summary>
                <div class="ws-actions">
                    @foreach ($module['actions'] as $action)
                        <a href="{{ route($action['route'], $action['params'] ?? []) }}" class="ws-action">
                            <i class="bi {{ $action['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $action['label'] }}</span>
                            <i class="bi bi-arrow-left ws-go" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>
@endif
@endsection
