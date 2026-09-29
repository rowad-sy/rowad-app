@extends('admin.layouts.master')

@section('title', 'الأفواج')

@section('content')
<x-page-header title="الأفواج" description="إدارة أفواج الطلاب داخل كل مشروع (مثال: فوج صباحي / فوج مسائي) ومسؤول كل فوج"
               :breadcrumb="[['label' => 'الإدارة'], ['label' => 'الأفواج']]">
    <a href="{{ route('admin.cohorts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة فوج
    </a>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
        <div class="col-12 col-md-4 filter-field">
            <label class="form-label" for="f-search">بحث</label>
            <input type="search" id="f-search" name="search" class="form-control" placeholder="الاسم أو الكود..." value="{{ $search }}">
        </div>
        <div class="col-12 col-md-3 filter-field">
            <label class="form-label" for="f-project_id">المشروع</label>
            <select id="f-project_id" name="project_id" class="form-select">
                <option value="">كل المشاريع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الفوج</th>
                    <th>المشروع</th>
                    <th>الفترة</th>
                    <th>الكود</th>
                    <th>المسؤول</th>
                    <th>الطلاب</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cohorts as $cohort)
                    <tr>
                        <td class="num">{{ $cohort->id }}</td>
                        <td class="fw-medium">{{ $cohort->name }}</td>
                        <td>{{ $cohort->project?->name ?? '—' }}</td>
                        <td>{{ $cohort->shift ?? '—' }}</td>
                        <td class="text-muted ltr-cell">{{ $cohort->code ?? '—' }}</td>
                        <td>
                            @if ($cohort->manager)
                                {{ $cohort->manager->first_name_ar }} {{ $cohort->manager->last_name_ar }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="num"><x-status-badge tone="info">{{ $cohort->students_count }}</x-status-badge></td>
                        <td>
                            <x-status-badge :tone="$cohort->is_active ? 'success' : 'neutral'">{{ $cohort->is_active ? 'نشط' : 'موقوف' }}</x-status-badge>
                        </td>
                        <td class="text-nowrap">
                            <div class="row-actions">
                                <a href="{{ route('admin.cohorts.edit', $cohort) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل {{ $cohort->name }}" title="تعديل">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.cohorts.destroy', $cohort) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الفوج؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="حذف {{ $cohort->name }}" title="حذف">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="9" title="لا توجد أفواج بعد" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $cohorts->links() }}
    </div>
</div>
@endsection
