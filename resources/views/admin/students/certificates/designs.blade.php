@extends('admin.layouts.master')

@section('title', 'تصاميم الشهادات')

@section('content')
<x-page-header title="تصاميم الشهادات" description="إدارة تصاميم الشهادات"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الشهادات', 'url' => route('admin.students.certificates.index')], ['label' => 'التصاميم']]">
    <a href="{{ route('admin.students.certificates.designer.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> تصميم جديد
    </a>
</x-page-header>

<div class="table-container mb-4">
    <form method="GET" action="{{ route('admin.students.certificates.designs') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">بحث بالاسم</label>
                <input type="text" name="search" class="form-control" placeholder="اسم التصميم..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">المقرر</label>
                <select name="course_id" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ ($courseId ?? '') == $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">السنة</label>
                <select name="year" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" {{ ($year ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">عدد النتائج</label>
                <select name="per_page" class="form-select">
                    @foreach ([12, 24, 48, 100] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 12) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> بحث</button>
                <a href="{{ route('admin.students.certificates.designs') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
        <input type="hidden" name="view" value="{{ $view ?? 'table' }}">
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
        إجمالي النتائج: {{ $designs->total() }}
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
           class="btn btn-{{ ($view ?? 'table') === 'grid' ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-grid-3x3-gap"></i> شبكة
        </a>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}"
           class="btn btn-{{ ($view ?? 'table') === 'table' ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-list-ul"></i> جدول
        </a>
    </div>
</div>

@if (($view ?? 'table') === 'table')
    {{-- Table View --}}
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم التصميم</th>
                        <th>المقرر</th>
                        <th>السنة</th>
                        <th>الحقول</th>
                        <th>التوقيعات</th>
                        <th>الشهادات</th>
                        <th>الخط</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($designs as $design)
                        <tr>
                            <td>{{ $design->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($design->template_image)
                                        <img src="{{ asset('storage/' . $design->template_image) }}" class="rounded" style="width:40px;height:30px;object-fit:cover;">
                                    @endif
                                    <span>{{ $design->name }}</span>
                                </div>
                            </td>
                            <td>{{ $design->course?->name_ar ?? 'عام' }}</td>
                            <td><span class="badge bg-{{ $design->year == date('Y') ? 'success' : 'secondary' }}">{{ $design->year }}</span></td>
                            <td>{{ count($design->fields_config ?? []) }}</td>
                            <td>{{ count($design->signatures_config ?? []) }}</td>
                            <td>{{ $design->certificates_count }}</td>
                            <td><small class="text-muted">{{ $design->font_family ?? 'Tajawal' }}</small></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('admin.students.certificates.designer.edit', $design) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.students.certificates.designs.duplicate', $design) }}" class="d-inline"
                                          title="نسخ التصميم">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-files"></i></button>
                                    </form>
                                    @if ($design->certificates_count > 0)
                                        <a href="{{ route('admin.students.certificates.print-batch', ['design_id' => $design->id]) }}"
                                           class="btn btn-sm btn-outline-success" target="_blank" title="طباعة">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.students.certificates.designs.destroy', $design) }}" class="d-inline"
                                          onsubmit="return confirm('سيتم حذف التصميم وجميع الشهادات المرتبطة. هل أنت متأكد؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-file-earmark-image fs-1 d-block mb-2"></i>
                                لا يوجد تصاميم بعد. <a href="{{ route('admin.students.certificates.designer.create') }}">أنشئ أول تصميم</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    {{-- Grid View --}}
    <div class="row g-3">
        @forelse ($designs as $design)
            <div class="col-md-4">
                <div class="table-container">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">{{ $design->name }}</h6>
                        <span class="badge bg-{{ $design->year == date('Y') ? 'success' : 'secondary' }}">{{ $design->year }}</span>
                    </div>
                    <div class="p-3">
                        @if ($design->template_image)
                            <img src="{{ asset('storage/' . $design->template_image) }}" class="img-fluid rounded mb-2" style="max-height:120px;width:100%;object-fit:cover;">
                        @endif
                        <div class="small text-muted">
                            <div><i class="bi bi-book me-1"></i> {{ $design->course?->name_ar ?? 'عام' }}</div>
                            <div><i class="bi bi-file-earmark me-1"></i> {{ $design->certificates_count }} شهادة مصدرة</div>
                            <div><i class="bi bi-layers me-1"></i> {{ count($design->fields_config ?? []) }} حقل</div>
                        </div>
                    </div>
                    <div class="p-3 border-top d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.students.certificates.designer.edit', $design) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> تعديل
                        </a>
                        <form method="POST" action="{{ route('admin.students.certificates.designs.duplicate', $design) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-files"></i> نسخ</button>
                        </form>
                        @if ($design->certificates_count > 0)
                            <a href="{{ route('admin.students.certificates.print-batch', ['design_id' => $design->id]) }}"
                               class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="bi bi-printer"></i> طباعة
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.students.certificates.designs.destroy', $design) }}" class="d-inline"
                              onsubmit="return confirm('سيتم حذف التصميم وجميع الشهادات المرتبطة. هل أنت متأكد؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="table-container text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-image fs-1 d-block mb-2"></i>
                    لا يوجد تصاميم بعد. <a href="{{ route('admin.students.certificates.designer.create') }}">أنشئ أول تصميم</a>
                </div>
            </div>
        @endforelse
    </div>
@endif

<div class="d-flex justify-content-center mt-4">
    {{ $designs->links() }}
</div>
@endsection
