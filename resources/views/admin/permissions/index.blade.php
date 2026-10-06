@extends('admin.layouts.master')

@section('title', 'الصلاحيات')

@section('content')
<x-page-header :title="'الصلاحيات'" :description="'إدارة صلاحيات المستخدمين والأدوار والمجموعات — النطاقات تُحدَّد وقت إسناد الدور'"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'الصلاحيات']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-briefcase me-1" aria-hidden="true"></i> الأدوار والنطاقات
        </a>
        @canPermission('App\Models\Admin\Permission', 'create')
        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة صلاحيات
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث عن صلاحية..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-8 d-flex align-items-center flex-wrap gap-1">
                @foreach (['all' => 'الكل', 'user' => 'مستخدمون', 'role' => 'أدوار', 'group' => 'مجموعات'] as $key => $chipLabel)
                    <a href="{{ route('admin.permissions.index', array_filter(['entity' => $key === 'all' ? null : $key, 'search' => $search])) }}"
                       class="btn btn-sm {{ $entityFilter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $chipLabel }} <span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>الكيان</th>
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
                                        <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $entity['user']->name }}
                                        <x-status-badge tone="neutral" class="ms-1">مستخدم</x-status-badge>
                                    @elseif (!$entity['is_user'] && $entity['group'])
                                        <i class="bi {{ $entity['group']->isRole() ? 'bi-briefcase' : 'bi-people' }} me-1" aria-hidden="true"></i>{{ $entity['group']->name }}
                                        <x-status-badge tone="{{ $entity['group']->isRole() ? 'brand' : 'neutral' }}" class="ms-1">{{ $entity['group']->isRole() ? 'دور' : 'مجموعة' }}</x-status-badge>
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
                                    <span class="ltr-cell d-inline-block">{{ class_basename($model) }}</span>@if (!$loop->last), @endif
                                @endforeach
                            </td>
                            <td>
                                @if ($perm->center)
                                    <x-status-badge tone="brand">{{ $perm->center->name }}</x-status-badge>
                                @else
                                    <x-status-badge>جميع المراكز</x-status-badge>
                                @endif
                                @if ($perm->project)
                                    <x-status-badge tone="brand">{{ $perm->project->name }}</x-status-badge>
                                @else
                                    <x-status-badge>جميع المشاريع</x-status-badge>
                                @endif
                                @if ($perm->cohort)
                                    <x-status-badge tone="brand">{{ $perm->cohort->name }}</x-status-badge>
                                @else
                                    <x-status-badge>جميع الأفواج</x-status-badge>
                                @endif
                            </td>
                            <td>
                                @if ($scopeGroup['flags']['can_view']) <x-status-badge tone="info">عرض</x-status-badge> @endif
                                @if ($scopeGroup['flags']['can_create']) <x-status-badge tone="success">إضافة</x-status-badge> @endif
                                @if ($scopeGroup['flags']['can_edit']) <x-status-badge tone="warning">تعديل</x-status-badge> @endif
                                @if ($scopeGroup['flags']['can_delete']) <x-status-badge tone="danger">حذف</x-status-badge> @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.permissions.edit', $perm) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <x-audit-history :model="'App\Models\Admin\Permission'" :model-id="$perm->id" />
                                <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" class="d-inline"
                                      onsubmit="return confirm('سيتم حذف جميع صلاحيات هذا العنصر ضمن هذا النطاق. هل أنت متأكد؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <x-empty-row colspan="6" icon="bi-inbox" title="لا توجد صلاحيات" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $permissions->links() }}
    </div>
</div>
@endsection
