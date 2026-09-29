@extends('admin.logistics.layouts.master')

@section('title', $asset->name)

@section('logistics-content')
<x-page-header :title="$asset->name" :breadcrumb="[['label' => 'الأصول', 'url' => route('admin.logistics.assets.index')], ['label' => $asset->name]]">
    @canPermission('App\Models\Admin\Logistics\Asset', 'edit')
        <a href="{{ route('admin.logistics.assets.edit', $asset) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
    @endcanPermission
    <x-audit-history :model="'App\Models\Admin\Logistics\Asset'" :model-id="$asset->id" />
    <a href="{{ route('admin.logistics.assets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</x-page-header>

<div class="row">
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">تفاصيل الأصل</h5></div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tr><th style="width:200px">كود الأصل</th><td dir="ltr" class="text-end"><code>{{ $asset->asset_code }}</code></td></tr>
                        <tr><th>الاسم</th><td>{{ $asset->name }}</td></tr>
                        <tr><th>النوع</th><td>{{ $asset->type ?: '—' }}</td></tr>
                        <tr><th>الحالة</th><td>{{ $asset->status ?: '—' }}</td></tr>
                        <tr><th>المركز</th><td>{{ $asset->center?->name ?? '—' }}</td></tr>
                        <tr><th>المشروع</th><td>{{ $asset->project?->name ?? '—' }}</td></tr>
                        <tr><th>رقم الغرفة</th><td>{{ $asset->room_number ?: '—' }}</td></tr>
                        <tr><th>المستلم</th><td>{{ $asset->recipient?->name ?? '—' }}</td></tr>
                        <tr><th>ملاحظات</th><td>{!! $asset->notes ? nl2br(e($asset->notes)) : '—' !!}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
