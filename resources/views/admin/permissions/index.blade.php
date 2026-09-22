@extends('admin.layouts.master')

@section('title', 'الصلاحيات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>الصلاحيات</h4>
        <p>إدارة صلاحيات المستخدمين والمجموعات</p>
    </div>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة صلاحية
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن صلاحية..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>المستخدم / المجموعة</th>
                    <th>عدد النطاقات</th>
                    <th>الموديل</th>
                    <th>النطاق</th>
                    <th>الصلاحيات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $entity)
                    @foreach ($entity['scopes'] as $si => $scopeGroup)
                        @php $perm = $scopeGroup['representative']; @endphp
                        <tr>
                            @if ($si === 0)
                                <td rowspan="{{ count($entity['scopes']) }}" class="fw-medium align-middle">
                                    @if ($entity['is_user'] && $entity['user'])
                                        <i class="bi bi-person me-1"></i>{{ $entity['user']->name }}
                                    @elseif (!$entity['is_user'] && $entity['group'])
                                        <i class="bi bi-people me-1"></i>{{ $entity['group']->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td rowspan="{{ count($entity['scopes']) }}" class="align-middle text-muted">
                                    {{ $entity['scopes']->count() }} نطاق
                                </td>
                            @endif
                            <td>
                                @foreach ($scopeGroup['models'] as $model)
                                    <code>{{ class_basename($model) }}</code>@if (!$loop->last), @endif
                                @endforeach
                            </td>
                            <td>
                                @if ($perm->center)
                                    <span class="badge bg-primary badge-scope">{{ $perm->center->name }}</span>
                                @else
                                    <span class="badge bg-secondary badge-scope">جميع المراكز</span>
                                @endif
                                @if ($perm->project)
                                    <span class="badge bg-success badge-scope">{{ $perm->project->name }}</span>
                                @else
                                    <span class="badge bg-secondary badge-scope">جميع المشاريع</span>
                                @endif
                                @if ($perm->cohort)
                                    <span class="badge bg-dark badge-scope">{{ $perm->cohort->name }}</span>
                                @else
                                    <span class="badge bg-secondary badge-scope">جميع الأفواج</span>
                                @endif
                            </td>
                            <td>
                                @if ($scopeGroup['flags']['can_view']) <span class="badge bg-info">عرض</span> @endif
                                @if ($scopeGroup['flags']['can_create']) <span class="badge bg-success">إضافة</span> @endif
                                @if ($scopeGroup['flags']['can_edit']) <span class="badge bg-warning text-dark">تعديل</span> @endif
                                @if ($scopeGroup['flags']['can_delete']) <span class="badge bg-danger">حذف</span> @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.permissions.edit', $perm) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <x-audit-history :model="'App\Models\Admin\Permission'" :model-id="$perm->id" />
                                <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" class="d-inline"
                                      onsubmit="return confirm('سيتم حذف جميع صلاحيات هذا العنصر ضمن هذا النطاق. هل أنت متأكد؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد صلاحيات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $permissions->links() }}
    </div>
</div>
@endsection
