@extends('admin.layouts.master')

@section('title', isset($set) ? 'تعديل مجموعة توقيعات' : 'إضافة مجموعة توقيعات')

@section('content')
<x-page-header :title="isset($set) ? 'تعديل المجموعة: ' . $set->name : 'إضافة مجموعة توقيعات جديدة'"
               :breadcrumb="[['label' => 'مجموعات التوقيع', 'url' => route('admin.students.certificates.signatory-sets.index')], ['label' => isset($set) ? $set->name : 'جديد']]" />

<form method="POST"
      action="{{ isset($set) ? route('admin.students.certificates.signatory-sets.update', $set) : route('admin.students.certificates.signatory-sets.store') }}">
    @csrf
    @if (isset($set))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">اسم المجموعة <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $set->name ?? '') }}" placeholder="مثال: دورة الحاسب - الفترة الأولى - مركز المدينة" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">المقرر</label>
            <select name="course_id" class="form-select">
                <option value="">أي مقرر</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ (int) old('course_id', $set->course_id ?? '') === $course->id ? 'selected' : '' }}>{{ $course->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">الفترة</label>
            <select name="period_id" class="form-select">
                <option value="">أي فترة</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" {{ (int) old('period_id', $set->period_id ?? '') === $period->id ? 'selected' : '' }}>{{ $period->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">المركز</label>
            <select name="center_id" class="form-select">
                <option value="">أي مركز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ (int) old('center_id', $set->center_id ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
        @foreach ([
            ['key' => 'instructor', 'label' => 'المدرب', 'field' => 'instructor_signer_id'],
            ['key' => 'center_manager', 'label' => 'مدير المركز', 'field' => 'center_manager_signer_id'],
            ['key' => 'project_manager', 'label' => 'مسؤول المشروع', 'field' => 'project_manager_signer_id'],
        ] as $slot)
        <div class="col-md-4">
            <label class="form-label">{{ $slot['label'] }}</label>
            <select name="{{ $slot['field'] }}" class="form-select">
                <option value="">— بدون —</option>
                @foreach ($signersByRole[$slot['key']] as $signer)
                    <option value="{{ $signer->id }}" {{ (int) old($slot['field'], $set->{$slot['field']} ?? '') === $signer->id ? 'selected' : '' }}>
                        {{ $signer->name_ar }}{{ $signer->signature_path ? ' ✓توقيع' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($signersByRole[$slot['key']]->isEmpty())
                <div class="form-text text-danger">
                    لا يوجد موقعون بهذا الدور. <a href="{{ route('admin.students.certificates.signers.create') }}">أضف موقعاً</a> أولاً.
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.certificates.signatory-sets.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
