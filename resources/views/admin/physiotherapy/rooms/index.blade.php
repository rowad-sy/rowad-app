@extends('admin.layouts.master')

@section('title', 'غرف العلاج الفيزيائي')

@section('content')
<x-page-header :title="'غرف العلاج الفيزيائي'"
               :breadcrumb="[['label' => 'العلاج الفيزيائي'], ['label' => 'الغرف']]" />

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <p class="text-muted mb-0 small">غرف الجلسات (أطفال، نساء، كهرباء، ...) تمارس فيها جلسات العلاج.</p>
    @canPermission('App\Models\Admin\Physiotherapy\PhysioRoom', 'create')
    <a href="{{ route('admin.physiotherapy.rooms.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> إضافة غرفة
    </a>
    @endcanPermission
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الغرفة</th>
                    <th>الوصف</th>
                    <th>المركز</th>
                    <th>عدد المرضى</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                <tr>
                    <td class="fw-semibold">{{ $room->name }}</td>
                    <td class="small">{{ Str::limit($room->description, 60) ?: '—' }}</td>
                    <td>{{ $room->center?->name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $room->patients_count > 0 ? 'bg-primary' : 'bg-secondary' }}">{{ $room->patients_count }}</span>
                    </td>
                    <td>
                        <x-status-badge :tone="$room->is_active ? 'success' : 'neutral'">{{ $room->is_active ? 'نشطة' : 'متوقفة' }}</x-status-badge>
                    </td>
                    <td class="text-nowrap">
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioRoom', 'edit')
                        <a href="{{ route('admin.physiotherapy.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary" title="تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        @endcanPermission
                        @canPermission('App\Models\Admin\Physiotherapy\PhysioRoom', 'delete')
                        <form method="POST" action="{{ route('admin.physiotherapy.rooms.destroy', $room) }}"
                              class="d-inline" onsubmit="return confirm('حذف الغرفة نهائياً؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
                @empty
                <x-empty-row colspan="6" title="لا توجد غرف بعد" />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection