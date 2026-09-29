@extends('admin.layouts.master')

@section('title', 'إدارة المقررات')

@section('content')
<x-page-header :title="'إدارة المقررات'" :description="'كل المقررات وملحقاتها (مواد، امتحانات، مستويات، عروض) في مكان واحد لكل مشروع.'"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'إدارة المقررات']]">
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.courses.help') }}" class="btn btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> معلومات ونصائح
        </a>
        @canPermission('App\Models\Admin\Student\Course', 'create')
        <a href="{{ route('admin.students.courses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مقرر
        </a>
        @endcanPermission
    </div>
</x-page-header>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-4">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">كل المشاريع</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

    @forelse ($courses as $course)
        <div class="card border-0 border-bottom rounded-0">
            <div class="card-body py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary bg-opacity-10 rounded p-2">
                                <i class="bi bi-book fs-4 text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $course->name_ar }}
                                    @if ($course->name_en)
                                        <small class="text-muted">({{ $course->name_en }})</small>
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-dark border">{{ $course->project?->name ?? '—' }}</span>
                                    @if ($course->duration)
                                        <span class="ms-1">{{ $course->duration }} يوم</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="d-flex flex-wrap gap-2 small">
                            <span class="badge bg-light text-dark border"><i class="bi bi-journal-text me-1"></i>{{ $course->subjects_count }} مواد</span>
                            <span class="badge bg-light text-dark border"><i class="bi bi-clipboard-check me-1"></i>{{ $examsTotal[$course->id] ?? 0 }} امتحان</span>
                            <span class="badge bg-light text-dark border"><i class="bi bi-layers me-1"></i>{{ $course->levels_count }} مستويات</span>
                            <span class="badge bg-light text-dark border"><i class="bi bi-calendar-week me-1"></i>{{ $course->offerings_count }} عروض</span>
                            @if ($course->periods->count())
                                <span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>{{ $course->periods->pluck('name_ar')->implode('، ') }}</span>
                            @endif
                        </div>
                        @if ($course->description)
                            <div class="text-muted small mt-1">{{ Str::limit($course->description, 90) }}</div>
                        @endif
                    </div>

                    <div class="col-lg-2 text-lg-end">
                        <div class="d-flex gap-1 justify-content-lg-end flex-wrap">
                            @canPermission('App\Models\Admin\Student\Course', 'create')
                            <a href="{{ route('admin.students.training-plans.create', ['course_id' => $course->id]) }}"
                               class="btn btn-sm btn-primary" title="إنشاء خطة تدريبية لهذا المقرر">
                                <i class="bi bi-calendar-week me-1"></i> خطة تدريبية
                            </a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\Student\Course', 'edit')
                            <a href="{{ route('admin.students.courses.edit', $course) }}" class="btn btn-sm btn-outline-primary" title="تعديل" aria-label="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            <x-audit-history :model="'App\Models\Admin\Student\Course'" :model-id="$course->id" />
                            @canPermission('App\Models\Admin\Student\Course', 'delete')
                            <form method="POST" action="{{ route('admin.students.courses.destroy', $course) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المقرر؟ سيُحذف معه مواده وامتحاناته وعروضه.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف" aria-label="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            لا توجد مقررات
            @canPermission('App\Models\Admin\Student\Course', 'create')
            <div class="mt-3">
                <a href="{{ route('admin.students.courses.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> أضف أول مقرر
                </a>
            </div>
            @endcanPermission
        </div>
    @endforelse

    <div class="p-3 d-flex justify-content-between align-items-center border-top">
        <div class="text-muted small">إجمالي: {{ $courses->count() }} مقرر</div>
        <a href="{{ route('admin.students.courses.help') }}" class="btn btn-sm btn-outline-info">
            <i class="bi bi-question-circle me-1"></i> كيف أستخدم هذه الصفحة؟
        </a>
    </div>
</div>
@endsection