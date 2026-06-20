@extends('admin.layouts.master')

@section('title', 'تصاميم الشهادات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>تصاميم الشهادات</h4>
        <p>إدارة تصاميم الشهادات</p>
    </div>
    <a href="{{ route('admin.students.certificates.designer.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> تصميم جديد
    </a>
</div>

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
                        <div><i class="bi bi-file-earmark me-1"></i> {{ $design->certificates()->count() }} شهادة مصدرة</div>
                        <div><i class="bi bi-layers me-1"></i> {{ count($design->fields_config ?? []) }} حقل</div>
                    </div>
                </div>
                <div class="p-3 border-top d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.students.certificates.designer.edit', $design) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> تعديل
                    </a>
                    @if ($design->certificates()->count() > 0)
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
@endsection
