@extends('admin.layouts.master')

@section('title', $equipment->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ $equipment->name }}</h4>
        <p>
            <a href="{{ route('admin.tech.equipment.index') }}" class="text-decoration-none">المعدات التقنية</a>
            / #{{ $equipment->id }}
        </p>
    </div>
    <x-audit-history :model="'App\Models\Admin\Tech\TechEquipment'" :model-id="$equipment->id" />
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <h5 class="mb-3">تفاصيل المعدة</h5>

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
                            @case('a') <span class="badge bg-success">ممتاز</span> @break
                            @case('b') <span class="badge bg-primary">جيد</span> @break
                            @case('c') <span class="badge bg-warning text-dark">متوسط</span> @break
                            @case('d') <span class="badge bg-danger">سيئ</span> @break
                            @case('e') <span class="badge bg-dark">تالف</span> @break
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

            <div class="d-flex gap-2">
                <a href="{{ route('admin.tech.equipment.edit', $equipment) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> تعديل
                </a>
                <a href="{{ route('admin.tech.equipment.index') }}" class="btn btn-outline-secondary">
                    العودة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
