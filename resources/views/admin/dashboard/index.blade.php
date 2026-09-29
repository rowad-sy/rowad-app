@extends('admin.layouts.master')

@section('title', 'لوحة التحكم')

@section('content')
<x-page-header title="لوحة مسؤول الموقع" description="مؤسسة الرواد للتعاون والتنمية"
               :breadcrumb="[['label' => 'لوحة التحكم']]" />

@php
    // نفس الأعداد والصلاحيات السابقة: يظهر المؤشر فقط إذا حسبه المتحكم للمستخدم الحالي
    $kpis = array_values(array_filter([
        ['label' => 'المراكز', 'value' => $centersCount, 'icon' => 'bi-geo-alt', 'url' => route('admin.centers.index')],
        ['label' => 'المشاريع', 'value' => $projectsCount, 'icon' => 'bi-briefcase', 'url' => route('admin.projects.index')],
        ['label' => 'المستخدمين', 'value' => $usersCount, 'icon' => 'bi-people', 'url' => route('admin.users.index')],
        ['label' => 'المجموعات', 'value' => $groupsCount, 'icon' => 'bi-shield-check', 'url' => route('admin.groups.index')],
    ], fn ($k) => $k['value'] !== null));
@endphp

<section class="dash-section" aria-labelledby="core-title">
    <h2 class="section-title" id="core-title">النظام الأساسي</h2>

    @if (count($kpis))
        <div class="row g-3">
            @foreach ($kpis as $kpi)
                <div class="col-6 col-lg-3">
                    <x-kpi :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :href="$kpi['url']" tone="brand" hint="عرض التفاصيل" />
                </div>
            @endforeach
        </div>
    @else
        <div class="table-container">
            <x-empty-state icon="bi-lock" title="لا توجد مؤشرات متاحة لحسابك"
                           hint="استخدم القائمة الجانبية للوصول إلى الأقسام المتاحة لك." />
        </div>
    @endif
</section>
@endsection
