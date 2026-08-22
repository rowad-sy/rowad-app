@extends('admin.layouts.master')

@section('title', 'سياسات الإجازات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>سياسات الإجازات</h4>
        <p>إدارة أنواع الإجازات وعدد أيامها السنوية</p>
    </div>
    @canPermission('App\Models\Admin\Hr\LeaveType', 'create')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg me-1"></i> إضافة نوع إجازة
    </button>
    @endcanPermission
</div>

<div class="table-container">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>الأيام السنوية</th>
                <th>يتطلب موافقة</th>
                <th>اللون</th>
                <th>نشط</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaveTypes as $type)
                <tr>
                    <td>{{ $type->id }}</td>
                    <td>
                        <i class="bi {{ $type->icon }} me-1" style="color:{{ $type->color }}"></i>
                        {{ $type->name_ar }}
                    </td>
                    <td class="fw-bold">{{ $type->annual_days }} يوم</td>
                    <td>
                        @if ($type->requires_approval)
                            <span class="badge bg-warning text-dark">نعم</span>
                        @else
                            <span class="badge bg-secondary">لا</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:{{ $type->color }};color:#fff">{{ $type->color }}</span>
                    </td>
                    <td>
                        @if ($type->is_active)
                            <span class="badge bg-success">نشط</span>
                        @else
                            <span class="badge bg-danger">غير نشط</span>
                        @endif
                    </td>
                    <td>
                        <x-audit-history :model="'App\Models\Admin\Hr\LeaveType'" :model-id="$type->id" />
                        @canPermission('App\Models\Admin\Hr\LeaveType', 'edit')
                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                data-id="{{ $type->id }}"
                                data-name_ar="{{ $type->name_ar }}"
                                data-annual_days="{{ $type->annual_days }}"
                                data-requires_approval="{{ $type->requires_approval ? '1' : '0' }}"
                                data-approver_ids="{{ json_encode($type->approver_ids) }}"
                                data-color="{{ $type->color }}"
                                data-icon="{{ $type->icon }}"
                                data-is_active="{{ $type->is_active ? '1' : '0' }}"
                                title="تعديل">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcanPermission
                        @canPermission('App\Models\Admin\Hr\LeaveType', 'delete')
                        <form action="{{ route('admin.hr.leave-policies.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="حذف">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endcanPermission
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">لا توجد أنواع إجازات</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Create Modal --}}
@canPermission('App\Models\Admin\Hr\LeaveType', 'create')
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.hr.leave-policies.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة نوع إجازة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.hr.leave-policies._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcanPermission

{{-- Edit Modal --}}
@canPermission('App\Models\Admin\Hr\LeaveType', 'edit')
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">تعديل نوع الإجازة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.hr.leave-policies._form', ['edit' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcanPermission
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('editForm').action = '{{ url('admin/hr/leave-policies') }}/' + id;
            document.getElementById('edit_name_ar').value = this.dataset.name_ar;
            document.getElementById('edit_annual_days').value = this.dataset.annual_days;
            document.getElementById('edit_requires_approval').checked = this.dataset.requires_approval === '1';
            document.getElementById('edit_color').value = this.dataset.color;
            document.getElementById('edit_icon').value = this.dataset.icon;
            document.getElementById('edit_is_active').checked = this.dataset.is_active === '1';

            const approverIds = JSON.parse(this.dataset.approver_ids || '[]');
            document.querySelectorAll('#editForm .approver-checkbox').forEach(cb => {
                cb.checked = approverIds.includes(parseInt(cb.value));
            });

            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    });
</script>
@endpush
