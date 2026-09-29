@extends('admin.layouts.master')

@section('title', $equipment->name)

@section('content')
<x-page-header :title="$equipment->name" :breadcrumb="[['label' => 'المعدات التقنية', 'url' => route('admin.tech.equipment.index')], ['label' => '#' . $equipment->id]]">
    <x-audit-history :model="'App\Models\Admin\Tech\TechEquipment'" :model-id="$equipment->id" />
</x-page-header>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <h5 class="mb-3">تفاصيل المعدة</h5>

            <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th style="width:180px;">الاسم</th>
                    <td>{{ $equipment->name }}</td>
                </tr>
                <tr>
                    <th>النوع</th>
                    <td>{{ $equipment->type }}</td>
                </tr>
                <tr>
                    <th>الرقم التسلسلي</th>
                    <td><code>{{ $equipment->serial_number ?? '—' }}</code></td>
                </tr>
                <tr>
                    <th>الحالة الفنية</th>
                    <td>
                        @switch($equipment->condition)
                            @case('a') <x-status-badge tone="success">ممتاز</x-status-badge> @break
                            @case('b') <x-status-badge tone="brand">جيد</x-status-badge> @break
                            @case('c') <x-status-badge tone="warning">متوسط</x-status-badge> @break
                            @case('d') <x-status-badge tone="danger">سيئ</x-status-badge> @break
                            @case('e') <x-status-badge>تالف</x-status-badge> @break
                        @endswitch
                    </td>
                </tr>
                <tr>
                    <th>الغرفة</th>
                    <td>{{ $equipment->room ?? '—' }}</td>
                </tr>
                <tr>
                    <th>المركز</th>
                    <td>{{ $equipment->center?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>المشروع</th>
                    <td>{{ $equipment->project?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>ملاحظات</th>
                    <td>{{ $equipment->notes ?? '—' }}</td>
                </tr>
                <tr>
                    <th>تاريخ الإضافة</th>
                    <td>{{ $equipment->created_at->locale('ar')->translatedFormat('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <th>آخر تحديث</th>
                    <td>{{ $equipment->updated_at->locale('ar')->translatedFormat('d M Y, h:i A') }}</td>
                </tr>
            </table>
            </div>

            <div class="d-flex gap-2">
                @if (\App\Support\RecordAccess::allows(auth()->user(), 'App\Models\Admin\Tech\TechEquipment', 'edit', $equipment->center_id, $equipment->project_id, $equipment->id))
                <a href="{{ route('admin.tech.equipment.edit', $equipment) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> تعديل
                </a>
                @endif
                <a href="{{ route('admin.tech.equipment.index') }}" class="btn btn-outline-secondary">
                    العودة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
