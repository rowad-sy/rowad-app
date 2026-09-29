@extends('admin.layouts.master')

@section('title', 'درجات مواد - ' . $enrollment->course->name_ar)

@section('content')
<x-page-header :title="'درجات المواد'"
               :breadcrumb="[['label' => 'الطلاب', 'url' => route('admin.students.index')], ['label' => $enrollment->student->first_name_ar . ' ' . $enrollment->student->last_name_ar, 'url' => route('admin.students.show', $enrollment->student)], ['label' => 'درجات المواد']]" />

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        <strong>يرجى تصحيح الأخطاء التالية:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">الطالب</small>
                <strong>{{ $enrollment->student->first_name_ar }} {{ $enrollment->student->last_name_ar }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">المقرر</small>
                <strong>{{ $enrollment->course->name_ar }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">الفترة</small>
                <strong>{{ $enrollment->period?->name_ar ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">الدرجة الكلية</small>
                <strong>{{ $enrollment->grade ?? '—' }}</strong>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.students.enrollments.grades.update', $enrollment) }}">
    @csrf
    @method('PUT')

    @if ($subjects->isEmpty())
        <div class="alert alert-info">لا توجد مواد مسجلة لهذا المقرر. أضف المواد من صفحة تحرير المقرر أولاً.</div>
    @else
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">مواد المقرر {{ $enrollment->course->name_ar }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المادة</th>
                            <th>الوزن</th>
                            <th>الدرجة (0-100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $index => $subject)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $subject->name_ar }}
                                    @if ($subject->name_en)
                                        <small class="text-muted">({{ $subject->name_en }})</small>
                                    @endif
                                </td>
                                <td>{{ $subject->weight ?? '—' }}</td>
                                <td style="width: 200px;">
                                    <input type="number" name="grades[{{ $subject->id }}]" value="{{ old('grades.' . $subject->id, $grades[$subject->id] ?? '') }}"
                                           class="form-control" min="0" max="100" step="0.01">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <p class="text-muted small mb-0">تُحسب الدرجة الكلية كمتوسط موزون حسب وزن كل مادة. اترك الحقل فارغاً لإزالة الدرجة.</p>
            </div>
        </div>
    @endif

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ الدرجات
        </button>
        <a href="{{ route('admin.students.show', $enrollment->student) }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
